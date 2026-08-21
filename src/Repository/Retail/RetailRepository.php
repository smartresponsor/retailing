<?php

declare(strict_types=1);

namespace App\Retailing\Repository\Retail;

use App\Retailing\Entity\Retail\RetailEntity;
use App\Retailing\Enum\Retail\RetailKind;
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
    public function findPublishedByTypePath(string $typePath): array
    {
        return $this->createQueryBuilder('retail')
            ->andWhere('retail.typePath = :typePath')
            ->andWhere('retail.objectState.objectStatus = :status')
            ->setParameter('typePath', trim($typePath))
            ->setParameter('status', 'published')
            ->orderBy('retail.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<RetailEntity> */
    public function findPublishedVendorServicesByTypePath(string $typePath): array
    {
        return $this->createQueryBuilder('retail')
            ->andWhere('retail.typePath = :typePath')
            ->andWhere('retail.kind = :kind')
            ->andWhere('retail.ownerType = :ownerType')
            ->andWhere('retail.objectState.objectStatus = :status')
            ->setParameter('typePath', trim($typePath))
            ->setParameter('kind', RetailKind::Service)
            ->setParameter('ownerType', 'vendor')
            ->setParameter('status', 'published')
            ->orderBy('retail.amountMinor', 'ASC')
            ->addOrderBy('retail.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
