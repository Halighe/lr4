<?php
namespace App\Repository;

use App\Dto\InstitutePaymentStats;
use Doctrine\ORM\EntityManagerInterface;

class PaymentStatisticsRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Получить статистику оплат по направлениям
     *
     * @return InstitutePaymentStats[]
     */
    public function getPaymentStatsByDirection(): array
    {
        $connection = $this->entityManager->getConnection();

        $sql = "
            SELECT
                i.direction,
                COUNT(CASE WHEN p.status = 'завершена' THEN 1 END) as paid_count,
                COUNT(CASE WHEN p.status = 'ожидает' THEN 1 END) as unpaid_count
            FROM institute i
            LEFT JOIN student_institute si ON i.id = si.institute_id
            LEFT JOIN student s ON si.student_id = s.id
            LEFT JOIN payment p ON s.id = p.student_id
            GROUP BY i.direction
            ORDER BY i.direction
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
     * Альтернативный запрос через QueryBuilder
     */
    public function getPaymentStatsByDirectionQB(): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        return $qb
            ->select([
                'i.direction',
                'SUM(CASE WHEN p.status = :paidStatus THEN 1 ELSE 0 END) as paid_count',
                'SUM(CASE WHEN p.status = :unpaidStatus THEN 1 ELSE 0 END) as unpaid_count'
            ])
            ->from('App\Entity\Institute', 'i')
            ->leftJoin('i.students', 's')
            ->leftJoin('s.payments', 'p')
            ->groupBy('i.direction')
            ->orderBy('i.direction', 'ASC')
            ->setParameter('paidStatus', 'завершена')
            ->setParameter('unpaidStatus', 'ожидает')
            ->getQuery()
            ->getResult();
    }

    /**
     * Общая статистика по всем направлениям
     */
    public function getTotalStats(): array
    {
        $sql = "
            SELECT
                COUNT(CASE WHEN p.status = 'завершена' THEN 1 END) as total_paid,
                COUNT(CASE WHEN p.status = 'ожидает' THEN 1 END) as total_unpaid,
                COUNT(*) as total_payments
            FROM payment p
        ";

        $connection = $this->entityManager->getConnection();
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery();

        return $result->fetchAssociative();
    }
}
