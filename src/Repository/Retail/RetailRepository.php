<?php

declare(strict_types=1);

namespace App\Retailing\Repository\Retail;

use App\Retailing\Entity\Retail\RetailEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<RetailEntity> */
final class RetailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RetailEntity::class);
    }

    /** @return list<RetailEntity> */
    public function findPublishedByCategory(string $categoryId): array
    {
        return $this->createQueryBuilder('retail')
            ->andWhere('retail.categoryId = :categoryId')
            ->andWhere('retail.objectState.objectStatus = :status')
            ->setParameter('categoryId', trim($categoryId))
            ->setParameter('status', 'published')
            ->orderBy('retail.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
