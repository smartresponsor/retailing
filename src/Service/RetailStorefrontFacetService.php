<?php

declare(strict_types=1);

namespace App\Retailing\Service;

use App\Cataloging\ServiceInterface\CatalogSearchServiceInterface;
use App\Cataloging\ValueObject\CategoryProjectionCriteria;

/**
 * Adapts Cataloging storefront facet projections for Retailing without owning facet semantics.
 */
final readonly class RetailStorefrontFacetService
{
    public function __construct(private CatalogSearchServiceInterface $catalogSearch) {}

    /**
     * @return list<array{identifier:string,buckets:array<string,int>}>
     */
    public function published(string $locale = 'en'): array
    {
        $result = $this->catalogSearch->search(CategoryProjectionCriteria::fromArray([
            'locale' => $locale,
            'published' => true,
            'limit' => 1,
            'offset' => 0,
        ]));

        $contracts = $result['facet_contracts'] ?? null;
        if (!is_array($contracts)) {
            return [];
        }

        $facets = [];
        foreach ($contracts as $contract) {
            if (!is_array($contract)) {
                continue;
            }
            $identifier = $contract['identifier'] ?? null;
            $buckets = $contract['buckets'] ?? null;
            if (!is_string($identifier) || '' === $identifier || !is_array($buckets)) {
                continue;
            }

            /** @var array<string, int> $buckets */
            $facets[] = ['identifier' => $identifier, 'buckets' => $buckets];
        }

        return $facets;
    }
}
