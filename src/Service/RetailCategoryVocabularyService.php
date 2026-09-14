<?php

declare(strict_types=1);

namespace App\Retailing\Service;

use App\Cataloging\ServiceInterface\CatalogCategoryLookupServiceInterface;
use App\Cataloging\ServiceInterface\CatalogCatalogTreeReadServiceInterface;
use App\Retailing\Enum\RetailKind;

final readonly class RetailCategoryVocabularyService
{
    public function __construct(
        private CatalogCategoryLookupServiceInterface $categoryLookup,
        private CatalogCatalogTreeReadServiceInterface $legacyCatalogTree,
    ) {}

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
            return $this->legacyChoices($kind->catalogCode());
        }

        return $this->legacyChoices($kind->catalogCode());
    }

    public function contains(RetailKind $kind, string $categoryId): bool
    {
        return in_array(trim($categoryId), array_values($this->choicesForKind($kind)), true);
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

    /**
     * @param array<int, mixed> $types
     *
     * @return array<string, string>
     */
    private function flattenTypes(array $types, string $prefix = ''): array
    {
        $choices = [];
        foreach ($types as $type) {
            if (!is_array($type)) {
                continue;
            }
            $label = isset($type['label']) && is_scalar($type['label']) ? trim((string) $type['label']) : '';
            $sourceCategoryId = isset($type['sourceCategoryId']) && is_scalar($type['sourceCategoryId'])
                ? trim((string) $type['sourceCategoryId'])
                : '';
            if ('' === $label) {
                continue;
            }
            $choiceLabel = '' === $prefix ? $label : $prefix . ' › ' . $label;
            if ('' !== $sourceCategoryId) {
                $choices[$choiceLabel] = $sourceCategoryId;
            }
            $children = $type['types'] ?? null;
            if (is_array($children)) {
                $choices += $this->flattenTypes($children, $choiceLabel);
            }
        }

        return $choices;
    }

    /** @return array<string, string> */
    private function legacyChoices(string $catalogCode): array
    {
        $tree = $this->legacyCatalogTree->byCode($catalogCode);
        $nodes = $tree['nodes'] ?? null;

        return is_array($nodes) ? $this->flattenLegacyNodes($nodes) : [];
    }

    /**
     * @param array<int, mixed> $nodes
     *
     * @return array<string, string>
     */
    private function flattenLegacyNodes(array $nodes, string $prefix = ''): array
    {
        $choices = [];
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }
            $id = isset($node['nodeId']) && is_scalar($node['nodeId']) ? trim((string) $node['nodeId']) : '';
            $title = isset($node['title']) && is_scalar($node['title']) ? trim((string) $node['title']) : '';
            if ('' === $id || '' === $title) {
                continue;
            }
            $choiceLabel = '' === $prefix ? $title : $prefix . ' › ' . $title;
            $choices[$choiceLabel] = $id;
            $children = $node['children'] ?? null;
            if (is_array($children)) {
                $choices += $this->flattenLegacyNodes($children, $choiceLabel);
            }
        }

        return $choices;
    }
}
