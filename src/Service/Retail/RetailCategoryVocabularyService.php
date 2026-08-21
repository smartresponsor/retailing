<?php

declare(strict_types=1);

namespace App\Retailing\Service\Retail;

use App\Cataloging\ServiceInterface\CatalogCategoryLookupServiceInterface;
use App\Cataloging\ServiceInterface\CatalogCatalogTreeReadServiceInterface;
use App\Retailing\Enum\Retail\RetailKind;

final readonly class RetailCategoryVocabularyService
{
    public function __construct(
        private CatalogCategoryLookupServiceInterface $categoryLookup,
        private CatalogCatalogTreeReadServiceInterface $legacyCatalogTree,
    ) {
    }

    /** @return array<string, array<string, string>> */
    public function choices(): array
    {
        $choices = [];
        foreach (RetailKind::cases() as $kind) {
            $kindChoices = $this->choicesForKind($kind);
            if ([] !== $kindChoices) {
                $choices[$kind->label()] = $kindChoices;
            }
        }

        return $choices;
    }

    /** @return array<string, string> */
    public function choicesForKind(RetailKind $kind): array
    {
        try {
            $category = $this->categoryLookup->publishedByCatalogAndPath('retailing', $this->categoryCode($kind));
            if (null !== $category) {
                $types = $category->getMetadata()['types'] ?? null;
                if (is_array($types)) {
                    $choices = $this->flattenTypes($types);
                    if ([] !== $choices) {
                        return $choices;
                    }
                }
            }
        } catch (\Throwable) {
        }

        return $this->legacyChoices($this->legacyCatalogCode($kind));
    }

    public function contains(RetailKind $kind, string $typePath): bool
    {
        return in_array(trim($typePath), array_values($this->choicesForKind($kind)), true);
    }

    private function categoryCode(RetailKind $kind): string
    {
        return match ($kind) {
            RetailKind::Goods => 'product',
            RetailKind::Service => 'service',
            RetailKind::Project => 'project',
            RetailKind::Task => 'task',
        };
    }

    /** @param array<int, mixed> $types @return array<string, string> */
    private function flattenTypes(array $types, string $labelPrefix = '', string $pathPrefix = ''): array
    {
        $choices = [];
        foreach ($types as $type) {
            if (!is_array($type)) {
                continue;
            }
            $label = isset($type['label']) && is_scalar($type['label']) ? trim((string) $type['label']) : '';
            $code = isset($type['code']) && is_scalar($type['code']) ? strtolower(trim((string) $type['code'])) : '';
            if ('' === $label || '' === $code) {
                continue;
            }
            $choiceLabel = '' === $labelPrefix ? $label : $labelPrefix.' › '.$label;
            $typePath = '' === $pathPrefix ? $code : $pathPrefix.'/'.$code;
            $choices[$choiceLabel] = $typePath;
            $children = $type['types'] ?? null;
            if (is_array($children)) {
                $choices += $this->flattenTypes($children, $choiceLabel, $typePath);
            }
        }

        return $choices;
    }

    /** @return array<string, string> */
    private function legacyChoices(string $catalogCode): array
    {
        try {
            $tree = $this->legacyCatalogTree->byCode($catalogCode);
        } catch (\Throwable) {
            return [];
        }
        $nodes = $tree['nodes'] ?? null;

        return is_array($nodes) ? $this->flattenLegacyNodes($nodes) : [];
    }

    /** @param array<int, mixed> $nodes @return array<string, string> */
    private function flattenLegacyNodes(array $nodes, string $labelPrefix = '', string $pathPrefix = ''): array
    {
        $choices = [];
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }
            $title = isset($node['title']) && is_scalar($node['title']) ? trim((string) $node['title']) : '';
            $slug = isset($node['slug']) && is_scalar($node['slug']) ? strtolower(trim((string) $node['slug'])) : '';
            if ('' === $slug || '' === $title) {
                continue;
            }
            $choiceLabel = '' === $labelPrefix ? $title : $labelPrefix.' › '.$title;
            $typePath = '' === $pathPrefix ? $slug : $pathPrefix.'/'.$slug;
            $choices[$choiceLabel] = $typePath;
            $children = $node['children'] ?? null;
            if (is_array($children)) {
                $choices += $this->flattenLegacyNodes($children, $choiceLabel, $typePath);
            }
        }

        return $choices;
    }

    private function legacyCatalogCode(RetailKind $kind): string
    {
        return match ($kind) {
            RetailKind::Task, RetailKind::Service => 'services',
            RetailKind::Goods => 'products',
            RetailKind::Project => 'projects',
        };
    }
}
