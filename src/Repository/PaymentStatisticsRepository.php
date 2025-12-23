<?php
namespace App\Repository;

use App\Entity\Payment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Dto\InstitutePaymentStats;

class PaymentStatisticsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    /**
     * Получить статистику оплат по направлениям
     */
    public function getPaymentStatsByDirection(): array
    {
        $connection = $this->getEntityManager()->getConnection();

        $sql = "
SELECT
COALESCE(i.direction, 'Не привязано к институту') as direction,
COALESCE(SUM(CASE WHEN p.status = 'завершена' THEN 1 ELSE 0 END), 0) as paid_count,
COALESCE(SUM(CASE WHEN p.status = 'ожидает' THEN 1 ELSE 0 END), 0) as unpaid_count
FROM payment p
INNER JOIN student s ON p.student_id = s.id
LEFT JOIN student_institute si ON s.id = si.student_id
LEFT JOIN institute i ON si.institute_id = i.id
GROUP BY i.direction
ORDER BY
CASE WHEN i.direction IS NULL THEN 1 ELSE 0 END,
i.direction
";

        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery();

        $stats = [];
        foreach ($result->fetchAllAssociative() as $row) {
            $stats[] = new InstitutePaymentStats(
                $row['direction'],
                (int) $row['paid_count'],
                (int) $row['unpaid_count']
            );
        }

        return $stats;
    }

    /**
     * Получить общую статистику
     */
    public function getTotalStats(): array
    {
        $sql = "
SELECT
SUM(CASE WHEN status = 'завершена' THEN 1 ELSE 0 END) as total_paid,
SUM(CASE WHEN status = 'ожидает' THEN 1 ELSE 0 END) as total_unpaid,
COUNT(*) as total_payments
FROM payment
";

        $connection = $this->getEntityManager()->getConnection();
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery();

        $data = $result->fetchAssociative();

        return [
            'total_paid' => (int) ($data['total_paid'] ?? 0),
            'total_unpaid' => (int) ($data['total_unpaid'] ?? 0),
            'total_payments' => (int) ($data['total_payments'] ?? 0)
        ];
    }

    /**
     * Получить список студентов с их оплатами
     */
    public function getStudentsWithPayments(): array
    {
        $connection = $this->getEntityManager()->getConnection();

        $sql = "
SELECT
s.id as student_id,
s.full_name,
i.direction,
GROUP_CONCAT(
CONCAT(
p.id, ':',
p.status, ':',
p.summ, ':',
p.date, ':',
sv.service_name
) SEPARATOR '|'
) as payments_data
FROM student s
LEFT JOIN student_institute si ON s.id = si.student_id
LEFT JOIN institute i ON si.institute_id = i.id
LEFT JOIN payment p ON s.id = p.student_id
LEFT JOIN service sv ON p.service_id = sv.id
GROUP BY s.id, i.direction
ORDER BY s.full_name
";

        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery();

        $students = [];
        foreach ($result->fetchAllAssociative() as $row) {
            $payments = [];

            if (!empty($row['payments_data'])) {
                $paymentEntries = explode('|', $row['payments_data']);
                foreach ($paymentEntries as $entry) {
                    if (!empty($entry)) {
                        $parts = explode(':', $entry);
                        if (count($parts) >= 5) {
                            $payments[] = [
                                'id' => $parts[0],
                                'status' => $parts[1],
                                'summ' => $parts[2],
                                'date' => $parts[3],
                                'service_name' => $parts[4] ?? 'Не указано'
                            ];
                        }
                    }
                }
            }

            $students[] = [
                'id' => $row['student_id'],
                'full_name' => $row['full_name'],
                'direction' => $row['direction'] ?? 'Не указано',
                'payments' => $payments
            ];
        }

        return $students;
    }

    /**
     * Обновить статус оплаты
     */
    public function updatePaymentStatus(int $paymentId, string $status): bool
    {
        $payment = $this->find($paymentId);

        if (!$payment) {
            return false;
        }

        $payment->setStatus($status);
        $payment->setDate(new \DateTime());

        $this->getEntityManager()->persist($payment);
        $this->getEntityManager()->flush();

        return true;
    }

    /**
     * Получить информацию о конкретной оплате
     */
    public function getPaymentInfo(int $paymentId): ?array
    {
        $connection = $this->getEntityManager()->getConnection();

        $sql = "
SELECT
p.*,
s.full_name as student_name,
sv.service_name
FROM payment p
INNER JOIN student s ON p.student_id = s.id
LEFT JOIN service sv ON p.service_id = sv.id
WHERE p.id = :id
";

        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery(['id' => $paymentId]);

        return $result->fetchAssociative() ?: null;
    }

    /**
     * Поиск платежей по фильтрам
     */
    public function searchPayments(array $filters): array
    {
        $qb = $this->createQueryBuilder('p');

        $qb->select(['p', 's', 'sv'])
            ->join('p.student', 's')
            ->leftJoin('p.service', 'sv')
            ->orderBy('p.date', 'DESC');

        if (!empty($filters['student_name'])) {
            $qb->andWhere('s.full_name LIKE :student_name')
                ->setParameter('student_name', '%' . $filters['student_name'] . '%');
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('p.status = :status')
                ->setParameter('status', $filters['status']);
        }

        return $qb->getQuery()->getResult();
    }
}
