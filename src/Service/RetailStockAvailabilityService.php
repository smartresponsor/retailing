<?php

declare(strict_types=1);

namespace App\Retailing\Service;

use App\Retailing\Entity\Retail\RetailEntity;
use App\Retailing\Enum\RetailKind;
use App\Stocking\Repository\StockLevelRepository;
use App\Stocking\Service\StockAvailabilityService;

/**
 * Projects Stocking-owned availability facts for vendor-owned goods listings without mutating inventory.
 */
final readonly class RetailStockAvailabilityService
{
    public function __construct(
        private StockLevelRepository $stockLevelRepository,
        private StockAvailabilityService $stockAvailabilityService,
    ) {}

    /**
     * @return array{status:'available'|'insufficient'|'unmanaged',availableToPromise:?int,requestedQuantity:int}
     */
    public function project(
        RetailEntity $listing,
        string $stockItemId,
        string $locationReference,
        int $quantity = 1,
    ): array {
        if (RetailKind::Goods !== $listing->getKind() || 'vendor' !== $listing->getOwnerType()) {
            throw new \InvalidArgumentException('Stock availability projection requires a vendor-owned goods listing.');
        }
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Requested stock quantity must be greater than zero.');
        }

        $stockItemId = trim($stockItemId);
        $locationReference = trim($locationReference);
        if ('' === $stockItemId || '' === $locationReference) {
            throw new \InvalidArgumentException('Stock item and location references are required.');
        }

        $level = $this->stockLevelRepository->find($stockItemId, $locationReference);
        if (null === $level) {
            return [
                'status' => 'unmanaged',
                'availableToPromise' => null,
                'requestedQuantity' => $quantity,
            ];
        }

        $availableToPromise = $this->stockAvailabilityService->availableToPromise($level);

        return [
            'status' => $availableToPromise >= $quantity ? 'available' : 'insufficient',
            'availableToPromise' => $availableToPromise,
            'requestedQuantity' => $quantity,
        ];
    }
}
