<?php

declare(strict_types=1);

namespace App\Retailing\Value\Retail;

use App\Retailing\Entity\Retail\RetailEntity;
use App\Viewing\ValueInterface\View\ViewObjectPayloadInterface;

final readonly class RetailViewPayload implements ViewObjectPayloadInterface
{
    public function __construct(private RetailEntity $retail) {}

    public function toTemplateContext(): array
    {
        return $this->data();
    }

    public function toFallbackData(): array
    {
        return $this->data();
    }

    /** @return array<string, mixed> */
    private function data(): array
    {
        return [
            'id' => $this->retail->getId(),
            'code' => $this->retail->getCode(),
            'kind' => $this->retail->getKind()->value,
            'kindLabel' => $this->retail->getKind()->label(),
            'title' => $this->retail->getTitle(),
            'description' => $this->retail->getDescription(),
            'catalogCode' => $this->retail->getCatalogCode(),
            'categoryId' => $this->retail->getCategoryId(),
            'amountMinor' => $this->retail->getAmountMinor(),
            'currency' => $this->retail->getCurrency(),
            'location' => $this->retail->getLocation(),
            'locationProfile' => $this->retail->getLocationProfile(),
            'fulfillmentProfile' => $this->retail->getFulfillmentProfile(),
            'pricingProfile' => $this->retail->getPricingProfile(),
            'status' => $this->retail->getObjectStatus(),
        ];
    }
}
