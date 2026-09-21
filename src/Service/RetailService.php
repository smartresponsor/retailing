<?php

declare(strict_types=1);

namespace App\Retailing\Service;

use App\Retailing\Entity\Retail\RetailEntity;
use App\Retailing\Repository\RetailRepository;

/**
 * Encapsulates straightforward RetailEntity persistence and published-category retrieval for application callers.
 */
final readonly class RetailService
{
    public function __construct(private RetailRepository $repository) {}

    /**
     * Persists the current listing state and returns the same managed entity.
     */
    public function save(RetailEntity $retail): RetailEntity
    {
        $this->repository->save($retail, true);

        return $retail;
    }

    /**
     * Removes the supplied listing from persistence and flushes the change immediately.
     */
    public function remove(RetailEntity $retail): void
    {
        $this->repository->remove($retail, true);
    }

    /**
     * Returns published listings for the requested category through the component repository.
     * @return list<RetailEntity>
     */
    public function publishedByCategory(string $categoryId): array
    {
        return $this->repository->findPublishedByCategory($categoryId);
    }
}
