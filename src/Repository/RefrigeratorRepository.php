<?php

namespace App\Repository;

use App\Entity\Refrigerator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Refrigerator>
 *
 * @method Refrigerator|null find($id, $lockMode = null, $lockVersion = null)
 * @method Refrigerator|null findOneBy(array $criteria, array $orderBy = null)
 * @method Refrigerator[]    findAll()
 * @method Refrigerator[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RefrigeratorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Refrigerator::class);
    }

//    /**
//     * @return Refrigerator[] Returns an array of Refrigerator objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Refrigerator
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
