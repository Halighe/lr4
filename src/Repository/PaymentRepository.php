<?php
namespace App\Repository;

use App\Entity\Payment;
use App\Entity\Student;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payment>
 */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    /**
     * Найти все ожидающие оплаты для студента
     */
    public function findPendingByStudent(Student $student): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.student = :student')
            ->andWhere('p.status = :status')
            ->setParameter('student', $student)
            ->setParameter('status', 'ожидает')
            ->orderBy('p.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Найти все оплаты для студента (все статусы)
     */
    public function findByStudent(Student $student): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.student = :student')
            ->setParameter('student', $student)
            ->orderBy('p.date', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
