<?php

declare(strict_types=1);

namespace App\Retailing\Repository;

use App\Retailing\Entity\Retail\RetailResponseEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Provides lifecycle-aware lookup operations for vendor responses to customer requests.
 * @extends ServiceEntityRepository<RetailResponseEntity>
 */
final class RetailResponseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RetailResponseEntity::class);
    }

    public function save(RetailResponseEntity $response, bool $flush = false): void
    {
        $this->getEntityManager()->persist($response);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    /**
     * Returns submitted responses that remain candidates for a specific customer request.
     * @return list<RetailResponseEntity>
     */
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

    /**
     * Finds the already accepted response for a request, if commercial selection has occurred.
     */
    public function findAcceptedForRetail(int $retailId): ?RetailResponseEntity
    {
        return $this->findOneBy([
            'retail' => $retailId,
            'status' => 'accepted',
        ]);
    }

    /**
     * Finds the unique response submitted by one vendor for one customer request.
     */
    public function findForRetailAndVendor(int $retailId, string $vendorId): ?RetailResponseEntity
    {
        return $this->findOneBy([
            'retail' => $retailId,
            'vendorId' => trim($vendorId),
        ]);
    }
}
