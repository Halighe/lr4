<?php

namespace App\Controller\Student;

use App\Entity\Payment;
use App\Repository\PaymentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/student/payments')]
#[IsGranted('ROLE_STUDENT')]
class PaymentController extends AbstractController
{
    #[Route('/', name: 'student_payments_index')]
    public function index(PaymentRepository $paymentRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $student = $user->getStudent();

        if (!$student) {
            throw $this->createAccessDeniedException('У вас нет профиля студента');
        }

        // Получаем только ожидающие оплаты текущего студента
        $pendingPayments = $paymentRepository->findPendingByStudent($student);

        return $this->render('student/payment/index.html.twig', [
            'payments' => $pendingPayments,
            'student' => $student,
        ]);
    }

    #[Route('/{id}', name: 'student_payment_show', methods: ['GET'])]
    public function show(Payment $payment): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $student = $user->getStudent();

        // Проверяем, что оплата принадлежит текущему студенту
        if ($payment->getStudent() !== $student) {
            throw $this->createAccessDeniedException('У вас нет доступа к этой оплате');
        }

        return $this->render('student/payment/show.html.twig', [
            'payment' => $payment,
        ]);
    }
    #[Route('/{id}/complete', name: 'student_payment_complete', methods: ['GET'])]
    public function complete(Payment $payment, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $student = $user->getStudent();

        // Проверяем, что оплата принадлежит текущему студенту
        if ($payment->getStudent() !== $student) {
            throw $this->createAccessDeniedException('У вас нет доступа к этой оплате');
        }

        // Проверяем, что статус "ожидает"
        if ($payment->getStatus() !== 'ожидает') {
            $this->addFlash('warning', 'Эта оплата уже обработана.');
            return $this->redirectToRoute('student_payments_index');
        }

        // Меняем статус на "завершена"
        $payment->setStatus('завершена');
        $payment->setDate(new \DateTime()); // Обновляем дату

        $entityManager->flush();

        // Добавляем сообщение об успехе
        $this->addFlash('success', 'Оплата отмечена как завершенная!');

        // Редирект обратно на список оплат
        return $this->redirectToRoute('student_payments_index');
    }
}
