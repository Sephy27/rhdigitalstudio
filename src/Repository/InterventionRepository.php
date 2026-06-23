<?php

namespace App\Repository;

use App\Entity\Intervention;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Intervention>
 */
class InterventionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Intervention::class);
    }

//    /**
//     * @return Intervention[] Returns an array of Intervention objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('i.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Intervention
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    public function findLastDocumentNumber(string $prefix, string $year): ?string
    {
        $field = $prefix === 'DEV' ? 'quoteNumber' : 'invoiceNumber';

        $result = $this->createQueryBuilder('i')
            ->select('i.' . $field)
            ->where('i.' . $field . ' LIKE :pattern')
            ->setParameter('pattern', $prefix . '-' . $year . '-%')
            ->orderBy('i.' . $field, 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $result[$field] ?? null;
    }

    public function getPaidRevenueBetween(\DateTimeImmutable $start, \DateTimeImmutable $end): float
    {
        return (float) $this->createQueryBuilder('i')
            ->select('COALESCE(SUM(i.price), 0)')
            ->where('i.paymentStatus = :paid')
            ->andWhere('i.paidAt BETWEEN :start AND :end')
            ->setParameter('paid', 'paid')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getPendingRevenue(): float
    {
        return (float) $this->createQueryBuilder('i')
            ->select('COALESCE(SUM(i.price), 0)')
            ->where('i.paymentStatus != :paid')
            ->andWhere('i.invoiceNumber IS NOT NULL')
            ->setParameter('paid', 'paid')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countUnpaidInvoices(): int
    {
        return (int) $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.paymentStatus != :paid')
            ->andWhere('i.invoiceNumber IS NOT NULL')
            ->setParameter('paid', 'paid')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getInvoicedRevenueBetween(\DateTimeImmutable $start, \DateTimeImmutable $end): float
    {
        return (float) $this->createQueryBuilder('i')
            ->select('COALESCE(SUM(i.price), 0)')
            ->where('i.invoiceNumber IS NOT NULL')
            ->andWhere('i.invoiceAt BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
