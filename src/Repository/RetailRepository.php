<?php

declare(strict_types=1);

namespace App\Retailing\Repository;

use App\Retailing\Entity\Retail\RetailEntity;
use App\Retailing\Enum\RetailKind;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Provides persisted retail listing queries used by publication and marketplace matching flows.
 * @extends ServiceEntityRepository<RetailEntity>
 */
final class RetailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RetailEntity::class);
    }

    public function save(RetailEntity $retail, bool $flush = false): void
    {
        $this->getEntityManager()->persist($retail);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(RetailEntity $retail, bool $flush = false): void
    {
        $this->getEntityManager()->remove($retail);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    /**
     * Returns published listings for one catalog category, newest first.
     * @return list<RetailEntity>
     */
    public function findPublishedByCategory(string $categoryId): array
    {
        return $this->createQueryBuilder('retail')
            ->andWhere('retail.categoryId = :categoryId')
            ->andWhere('retail.objectState.objectStatus = :status')
            ->andWhere('(retail.publicationStartsAt IS NULL OR retail.publicationStartsAt <= :now)')
            ->andWhere('(retail.publicationEndsAt IS NULL OR retail.publicationEndsAt > :now)')
            ->setParameter('categoryId', trim($categoryId))
            ->setParameter('status', 'published')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('retail.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns published vendor service offerings eligible for category-level marketplace matching.
     * @return list<RetailEntity>
     */
    public function findPublishedVendorServicesByCategory(string $categoryId): array
    {
        return $this->createQueryBuilder('retail')
            ->andWhere('retail.categoryId = :categoryId')
            ->andWhere('retail.kind = :kind')
            ->andWhere('retail.ownerType = :ownerType')
            ->andWhere('retail.objectState.objectStatus = :status')
            ->andWhere('(retail.publicationStartsAt IS NULL OR retail.publicationStartsAt <= :now)')
            ->andWhere('(retail.publicationEndsAt IS NULL OR retail.publicationEndsAt > :now)')
            ->setParameter('categoryId', trim($categoryId))
            ->setParameter('kind', RetailKind::Service)
            ->setParameter('ownerType', 'vendor')
            ->setParameter('status', 'published')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('retail.amountMinor', 'ASC')
            ->addOrderBy('retail.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
