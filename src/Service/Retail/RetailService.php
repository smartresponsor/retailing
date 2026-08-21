<?php

declare(strict_types=1);

namespace App\Retailing\Service\Retail;

use App\Retailing\Entity\Retail\RetailEntity;
use App\Retailing\Repository\Retail\RetailRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class RetailService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RetailRepository $repository,
    ) {
    }

    public function save(RetailEntity $retail): RetailEntity
    {
        $this->entityManager->persist($retail);
        $this->entityManager->flush();

        return $retail;
    }

    public function remove(RetailEntity $retail): void
    {
        $this->entityManager->remove($retail);
        $this->entityManager->flush();
    }

    /** @return list<RetailEntity> */
    public function publishedByTypePath(string $typePath): array
    {
        return $this->repository->findPublishedByTypePath($typePath);
    }
}
