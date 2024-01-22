<?php

namespace App\Repository;

use App\Entity\FoodRecipeNotInRefrigerator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FoodRecipeNotInRefrigerator>
 *
 * @method FoodRecipeNotInRefrigerator|null find($id, $lockMode = null, $lockVersion = null)
 * @method FoodRecipeNotInRefrigerator|null findOneBy(array $criteria, array $orderBy = null)
 * @method FoodRecipeNotInRefrigerator[]    findAll()
 * @method FoodRecipeNotInRefrigerator[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FoodNotInRefrigeratorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FoodRecipeNotInRefrigerator::class);
    }

//    /**
//     * @return FoodRecipeNotInRefrigerator[] Returns an array of FoodRecipeNotInRefrigerator objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('f.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?FoodRecipeNotInRefrigerator
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
