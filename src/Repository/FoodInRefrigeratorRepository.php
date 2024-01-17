<?php

namespace App\Repository;

use App\Entity\FoodRecipeInRefrigerator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FoodRecipeInRefrigerator>
 *
 * @method FoodRecipeInRefrigerator|null find($id, $lockMode = null, $lockVersion = null)
 * @method FoodRecipeInRefrigerator|null findOneBy(array $criteria, array $orderBy = null)
 * @method FoodRecipeInRefrigerator[]    findAll()
 * @method FoodRecipeInRefrigerator[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FoodInRefrigeratorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FoodRecipeInRefrigerator::class);
    }

//    /**
//     * @return FoodRecipeInRefrigerator[] Returns an array of FoodRecipeInRefrigerator objects
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

//    public function findOneBySomeField($value): ?FoodRecipeInRefrigerator
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
