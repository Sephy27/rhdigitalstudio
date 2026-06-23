<?php

namespace App\Repository;

use App\Entity\Galerie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Galerie>
 */
class GalerieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Galerie::class);
    }

    /**
     * @return Galerie[] Returns an array of Galerie objects
     */
    /* public function getAllPaginated(int $page = 1, int $limit = 12): array
    {
        $offset = ($page - 1) * $limit;
        $query = $this ->createQueryBuilder('p')

            ->orderBy('p.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $paginator = new Paginator($query);
        $data = $paginator->getQuery()->getResult();
        $result['galerie'] = $data;
        $result['pages'] = ceil($paginator->count() / $limit);
        $result['current'] = $page;
        return $result;

        ;
    } */

//    public function findOneBySomeField($value): ?Galerie
//    {
//        return $this->createQueryBuilder('g')
//            ->andWhere('g.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
