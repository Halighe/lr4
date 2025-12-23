<?php
// src/Controller/Moderator/StatisticsController.php

namespace App\Controller\Moderator;

use App\Entity\Payment;
use App\Entity\PaymentCheck;
use App\Entity\Student;
use App\Entity\Employee;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class StatisticsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
    }

    #[Route('/moderator/statistics', name: 'moderator_statistics')]
    #[IsGranted("ROLE_MODERATOR")]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_MODERATOR');

        // Получаем статистику по платежам
        $totalPaid = $this->em->getRepository(Payment::class)
            ->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.status = :paidStatus')
            ->setParameter('paidStatus', 'завершена')
            ->getQuery()
            ->getSingleScalarResult();

        $totalUnpaid = $this->em->getRepository(Payment::class)
            ->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.status = :unpaidStatus')
            ->setParameter('unpaidStatus', 'ожидает')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPayments = $totalPaid + $totalUnpaid;

        // Получаем студентов с их платежами
        $studentsData = $this->em->getRepository(Student::class)
            ->createQueryBuilder('s')
            ->leftJoin('s.payments', 'p')
            ->leftJoin('s.institutes', 'i')
            ->addSelect('p')
            ->addSelect('i')
            ->orderBy('p.date', 'DESC')
            ->getQuery()
            ->getResult();

        // Преобразуем данные студентов
        $students = [];
        foreach ($studentsData as $student) {
            $payments = $student->getPayments();

            // Получаем направление из института
            $direction = null;
            $institutes = $student->getInstitutes();
            if ($institutes && $institutes->count() > 0) {
                $institute = $institutes->first();
                if ($institute && method_exists($institute, 'getDirection')) {
                    $direction = $institute->getDirection();
                }
            }

            if ($payments->count() > 0) {
                foreach ($payments as $payment) {
                    // Получаем проверку для этого платежа
                    $paymentCheck = $payment->getPaymentCheck(); // Используем метод из сущности

                    $checkedBy = null;
                    if ($paymentCheck && $paymentCheck->getEmployee()) {
                        $checkedBy = $paymentCheck->getEmployee()->getFullName();
                    }

                    $students[] = [
                        'id' => $student->getId(),
                        'fullName' => $student->getFullName(),
                        'direction' => $direction ?: 'Не указано',
                        'paymentId' => $payment->getId(),
                        'paymentAmount' => $payment->getSumm(), // Используем getSumm()
                        'paymentDate' => $payment->getDate(),
                        'paymentStatus' => $payment->getStatus(),
                        'checkedBy' => $checkedBy,
                        'checkedAt' => $paymentCheck && $paymentCheck->getEmployee() ?
                            (new \DateTime())->format('d.m.Y H:i') : null,
                    ];
                }
            } else {
                // Студенты без платежей
                $students[] = [
                    'id' => $student->getId(),
                    'fullName' => $student->getFullName(),
                    'direction' => $direction ?: 'Не указано',
                    'paymentId' => null,
                    'paymentAmount' => null,
                    'paymentDate' => null,
                    'paymentStatus' => null,
                    'checkedBy' => null,
                    'checkedAt' => null,
                ];
            }
        }

        // Получаем статистику по направлениям
        $statsData = $this->em->getRepository(Student::class)
            ->createQueryBuilder('s')
            ->leftJoin('s.payments', 'p')
            ->leftJoin('s.institutes', 'i')
            ->select([
                'COALESCE(i.direction, \'Без направления\') as direction',
                'SUM(CASE WHEN p.status = \'завершена\' THEN 1 ELSE 0 END) as paidCount',
                'SUM(CASE WHEN p.status = \'ожидает\' THEN 1 ELSE 0 END) as unpaidCount',
                'COUNT(p.id) as totalCount'
            ])
            ->groupBy('direction')
            ->having('COUNT(p.id) > 0')
            ->getQuery()
            ->getResult();

        // Преобразуем статистику
        $stats = [];
        foreach ($statsData as $statData) {
            $totalCount = (int) $statData['totalCount'];
            $paidCount = (int) $statData['paidCount'];
            $paidPercentage = $totalCount > 0 ? round(($paidCount / $totalCount) * 100, 1) : 0;

            $stats[] = [
                'direction' => $statData['direction'],
                'paidCount' => $paidCount,
                'unpaidCount' => (int) $statData['unpaidCount'],
                'totalCount' => $totalCount,
                'paidPercentage' => $paidPercentage,
            ];
        }

        return $this->render('moderator/statistics/index.html.twig', [
            'total_paid' => $totalPaid,
            'total_unpaid' => $totalUnpaid,
            'total_payments' => $totalPayments,
            'stats' => $stats,
            'students' => $students,
        ]);
    }

    #[Route('/moderator/payment/toggle', name: 'moderator_payment_toggle', methods: ['POST'])]
    public function togglePaymentStatus(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_MODERATOR');

        $data = json_decode($request->getContent(), true);
        $paymentId = $data['payment_id'] ?? null;
        $newStatus = $data['new_status'] ?? null;

        if (!$paymentId || !$newStatus) {
            return $this->json([
                'success' => false,
                'message' => 'Неверные данные'
            ], 400);
        }

        $payment = $this->em->getRepository(Payment::class)->find($paymentId);

        if (!$payment) {
            return $this->json([
                'success' => false,
                'message' => 'Платеж не найден'
            ], 404);
        }

        // Проверяем валидность статуса
        if (!in_array($newStatus, ['завершена', 'ожидает'])) {
            return $this->json([
                'success' => false,
                'message' => 'Неверный статус. Допустимые значения: завершена, ожидает'
            ], 400);
        }

        try {
            // Обновляем статус платежа
            $payment->setStatus($newStatus);

            // Получаем текущего пользователя
            $currentUser = $this->getUser();

            // Находим Employee по связи с User
            $employee = $this->em->getRepository(Employee::class)
                ->findOneBy(['user' => $currentUser]);

            // Если Employee не найден по связи, пробуем найти по email
            if (!$employee && method_exists($currentUser, 'getEmail')) {
                $employee = $this->em->getRepository(Employee::class)
                    ->createQueryBuilder('e')
                    ->leftJoin('e.user', 'u')
                    ->where('u.email = :email')
                    ->setParameter('email', $currentUser->getEmail())
                    ->getQuery()
                    ->getOneOrNullResult();
            }

            // Создаем или обновляем проверку
            $paymentCheck = $payment->getPaymentCheck();
            if (!$paymentCheck) {
                $paymentCheck = new PaymentCheck();
                $paymentCheck->setPayment($payment);
            }

            if ($employee) {
                $paymentCheck->setEmployee($employee);
                $checkedByName = $employee->getFullName() ?: $currentUser->getUserIdentifier();
            } else {
                // Если Employee не найден, создаем временную запись
                $employee = new Employee();
                $employee->setFullName($currentUser->getUserIdentifier());
                $employee->setPost('Модератор');
                $employee->setUser($currentUser);
                $this->em->persist($employee);

                $paymentCheck->setEmployee($employee);
                $checkedByName = $currentUser->getUserIdentifier();
            }

            $this->em->persist($paymentCheck);
            $this->em->persist($payment);
            $this->em->flush();

            return $this->json([
                'success' => true,
                'checked_by' => $checkedByName,
                'checked_at' => (new \DateTime())->format('d.m.Y H:i'),
                'new_status' => $newStatus,
                'new_status_text' => $newStatus === 'завершена' ? 'Оплачено' : 'Ожидает'
            ]);

        } catch (\Exception $e) {
            // Логируем ошибку
            error_log('Payment toggle error: ' . $e->getMessage());

            return $this->json([
                'success' => false,
                'message' => 'Ошибка при изменении статуса: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString() // Только для отладки
            ], 500);
        }
    }
}
