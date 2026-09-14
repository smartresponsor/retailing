<?php

declare(strict_types=1);

namespace App\Retailing\Service;

use App\Cataloging\ServiceInterface\CatalogCategoryVocabularyServiceInterface;
use App\Retailing\Enum\RetailKind;

final readonly class RetailKindVocabularyService
{
    public function __construct(private CatalogCategoryVocabularyServiceInterface $catalogVocabulary) {}

    /** @return array<string, RetailKind> */
    public function choices(): array
    {
        $choices = [];
        try {
            $categories = $this->catalogVocabulary->publishedCategories('retailing');
        } catch (\Throwable) {
            return $this->fallbackChoices();
        }

        foreach ($categories as $category) {
            $code = strtolower(trim($category['code']));
            $label = trim($category['label']);
            $kind = 'product' === $code ? RetailKind::Goods : RetailKind::tryFrom($code);
            if (null === $kind || '' === $label) {
                continue;
            }

            $choices[$label] = $kind;
        }

        return [] === $choices ? $this->fallbackChoices() : $choices;
    }

    /** @return array<string, RetailKind> */
    private function fallbackChoices(): array
    {
        $choices = [];
        foreach (RetailKind::cases() as $kind) {
            $choices[$kind->label()] = $kind;
        }

        return $choices;
    }
}
