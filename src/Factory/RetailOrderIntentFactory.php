<?php

declare(strict_types=1);

namespace App\Retailing\Factory;

use App\Pricing\DTO\PriceQuoteDTO;
use App\Retailing\Entity\Retail\RetailEntity;
use App\Retailing\Enum\RetailKind;

/**
 * Converts a matched customer task and vendor service into a neutral Ordering intent payload.
 */
final class RetailOrderIntentFactory
{
    /**
     * Builds an order-ready payload when commercial price is known, otherwise requests price agreement.
     *
     * @return array{status:'ready'|'agreed_price_required', payload:?array{customerId:string,vendorId:string,currency:string,items:list<array{sku:string,qty:int,price:string}>}}
     */
    public function forCandidate(
        RetailEntity $task,
        RetailEntity $service,
        ?int $agreedAmountMinor = null,
        ?PriceQuoteDTO $priceQuote = null,
    ): array {
        if (RetailKind::Task !== $task->getKind() || 'access' !== $task->getOwnerType()) {
            throw new \InvalidArgumentException('Order intent requires an access-owned customer task.');
        }
        if (RetailKind::Service !== $service->getKind() || 'vendor' !== $service->getOwnerType()) {
            throw new \InvalidArgumentException('Order intent requires a vendor-owned service offering.');
        }

        $customerId = trim((string) $task->getOwner());
        $vendorId = trim((string) $service->getOwner());
        if ('' === $customerId || '' === $vendorId) {
            throw new \DomainException('Customer and vendor identifiers are required for order intent.');
        }
        if ($task->getCurrency() !== $service->getCurrency()) {
            throw new \DomainException('Customer task and service offering currencies must match before order intent.');
        }

        $pricingProfile = $service->getPricingProfile() ?? [];
        $pricingMode = is_string($pricingProfile['mode'] ?? null) ? strtolower(trim($pricingProfile['mode'])) : '';
        $amountMinor = $agreedAmountMinor
            ?? $this->quotedAmount($service, $priceQuote)
            ?? $this->selectedAmount($task, $service);
        if (null === $amountMinor && 'fixed' === $pricingMode) {
            $amountMinor = $service->getAmountMinor();
        }

        if (null === $amountMinor) {
            return ['status' => 'agreed_price_required', 'payload' => null];
        }
        if ($amountMinor < 0) {
            throw new \InvalidArgumentException('Agreed order amount cannot be negative.');
        }

        return [
            'status' => 'ready',
            'payload' => [
                'customerId' => $customerId,
                'vendorId' => $vendorId,
                'currency' => $service->getCurrency(),
                'items' => [[
                    'sku' => 'retail:' . $service->getId(),
                    'qty' => 1,
                    'price' => number_format($amountMinor / 100, 2, '.', ''),
                ]],
            ],
        ];
    }

    private function quotedAmount(RetailEntity $service, ?PriceQuoteDTO $priceQuote): ?int
    {
        if (null === $priceQuote) {
            return null;
        }

        $expectedReference = 'retail:' . $service->getId();
        if ($priceQuote->priceableReference() !== $expectedReference) {
            throw new \DomainException('Pricing quote does not belong to the selected retail service.');
        }
        if ($priceQuote->currency->currencyCode !== $service->getCurrency()) {
            throw new \DomainException('Pricing quote currency must match the selected retail service.');
        }

        return $priceQuote->amountMinor();
    }

    private function selectedAmount(RetailEntity $task, RetailEntity $service): ?int
    {
        $selection = $task->getSelectionProfile();
        if (null === $selection) {
            return null;
        }

        if ((string) ($selection['serviceId'] ?? '') !== (string) $service->getId()) {
            return null;
        }
        if ((string) ($selection['vendorId'] ?? '') !== (string) $service->getOwner()) {
            return null;
        }
        if ((string) ($selection['currency'] ?? '') !== $service->getCurrency()) {
            return null;
        }

        $amountMinor = $selection['agreedAmountMinor'] ?? null;

        return is_numeric($amountMinor) ? (int) $amountMinor : null;
    }
}
