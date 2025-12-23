<?php
namespace App\Repository;

use App\Dto\MasterStudentPaymentInfo;
use Doctrine\ORM\EntityManagerInterface;

class MasterReportRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Получить детальную информацию об оплатах студентов
     *
     * @return MasterStudentPaymentInfo[]
     */
    public function getStudentPaymentDetails(): array
    {
        $connection = $this->entityManager->getConnection();

        // Адаптированный запрос под вашу структуру БД
        $sql = "
            SELECT
                st.full_name as student_name,
                i.direction,
                sv.service_name,
                p.status,
                p.id as contract_number  -- если нет отдельного поля contract_number
            FROM payment p
            INNER JOIN student st ON p.student_id = st.id
            INNER JOIN service sv ON p.service_id = sv.id
            LEFT JOIN student_institute si ON st.id = si.student_id
            LEFT JOIN institute i ON si.institute_id = i.id
            ORDER BY
                i.direction,
                st.full_name,
                p.status
        ";

        // Если у вас есть поле contract_number в таблице payment, используйте этот запрос:
        /*
        $sql = "
            SELECT
                s.full_name as student_name,
                i.direction,
                sv.service_name,
                p.status,
                p.contract_number
            FROM payment p
            INNER JOIN student st ON p.student_id = st.id
            INNER JOIN service sv ON p.service_id = sv.id
            LEFT JOIN student_institute si ON st.id = si.student_id
            LEFT JOIN institute i ON si.institute_id = i.id
            ORDER BY
                i.direction,
                s.full_name,
                p.status
        ";
        */

        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery();

        $data = [];
        foreach ($result->fetchAllAssociative() as $row) {
            $data[] = new MasterStudentPaymentInfo(
                $row['student_name'],
                $row['direction'] ?? 'Не указано',
                $row['service_name'],
                $row['status'],
                $row['contract_number'] ?? null
            );
        }

        return $data;
    }

    /**
     * Получить статистику по статусам
     */
    public function getStatusSummary(): array
    {
        $sql = "
            SELECT
                status,
                COUNT(*) as count,
                SUM(summ) as total_amount
            FROM payment
            GROUP BY status
            ORDER BY status
        ";

        $connection = $this->entityManager->getConnection();
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery();

        return $result->fetchAllAssociative();
    }

    /**
     * Получить топ услуг по количеству оплат
     */
    public function getTopServices(): array
    {
        $sql = "
            SELECT
                sv.service_name,
                COUNT(p.id) as payment_count,
                SUM(p.summ) as total_amount
            FROM payment p
            INNER JOIN service sv ON p.service_id = sv.id
            GROUP BY sv.service_name
            ORDER BY payment_count DESC
            LIMIT 10
        ";

        $connection = $this->entityManager->getConnection();
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery();

        return $result->fetchAllAssociative();
    }
}
