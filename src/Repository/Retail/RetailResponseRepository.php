<?php

declare(strict_types=1);

namespace App\Retailing\Repository\Retail;

use App\Retailing\Entity\Retail\RetailResponseEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<RetailResponseEntity> */
final class RetailResponseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RetailResponseEntity::class);
    }

    /** @return list<RetailResponseEntity> */
    public function findSubmittedForRetail(int $retailId): array
    {
        return $this->createQueryBuilder('response')
            ->andWhere('IDENTITY(response.retail) = :retailId')
            ->andWhere('response.status = :status')
            ->setParameter('retailId', $retailId)
            ->setParameter('status', 'submitted')
            ->orderBy('response.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findForRetailAndVendor(int $retailId, string $vendorId): ?RetailResponseEntity
    {
        return $this->findOneBy([
            'retail' => $retailId,
            'vendorId' => trim($vendorId),
        ]);
    }
}
