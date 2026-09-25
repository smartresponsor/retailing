<?php

declare(strict_types=1);

namespace App\Retailing\Tests\Unit;

use App\Pricing\DTO\PriceCurrencyMetadataDTO;
use App\Pricing\DTO\PriceQuoteDTO;
use App\Pricing\DTO\PriceSelectionSnapshotDTO;
use App\Retailing\Entity\Retail\RetailEntity;
use App\Retailing\Enum\RetailKind;
use App\Retailing\Factory\RetailOrderIntentFactory;
use PHPUnit\Framework\TestCase;

/** Verifies Retailing consumes typed reusable-price facts from Pricing. */
final class RetailPricingQuoteIntegrationTest extends TestCase
{
    public function testOrderIntentUsesPricingQuoteWhenNoNegotiatedAmountExists(): void
    {
        [$task, $service] = $this->candidatePair();
        $quote = $this->quote('retail:77', 'USD', 4321);

        $intent = (new RetailOrderIntentFactory())->forCandidate($task, $service, null, $quote);

        self::assertSame('ready', $intent['status']);
        self::assertSame('43.21', $intent['payload']['items'][0]['price'] ?? null);
    }

    public function testNegotiatedAmountOverridesReusablePricingQuote(): void
    {
        [$task, $service] = $this->candidatePair();
        $quote = $this->quote('retail:77', 'USD', 4321);

        $intent = (new RetailOrderIntentFactory())->forCandidate($task, $service, 5000, $quote);

        self::assertSame('50.00', $intent['payload']['items'][0]['price'] ?? null);
    }

    public function testRejectsPricingQuoteForAnotherRetailService(): void
    {
        [$task, $service] = $this->candidatePair();

        $this->expectException(\DomainException::class);
        (new RetailOrderIntentFactory())->forCandidate(
            $task,
            $service,
            null,
            $this->quote('retail:999', 'USD', 4321),
        );
    }

    /** @return array{RetailEntity, RetailEntity} */
    private function candidatePair(): array
    {
        $task = new RetailEntity();
        $task->setKind(RetailKind::Task);
        $task->setOwnerType('access');
        $task->setOwner('customer-1');

        $service = new RetailEntity();
        $service->setKind(RetailKind::Service);
        $service->setOwnerType('vendor');
        $service->setOwner('vendor-1');

        $id = new \ReflectionProperty($service, 'id');
        $id->setValue($service, 77);

        return [$task, $service];
    }

    private function quote(string $reference, string $currency, int $amountMinor): PriceQuoteDTO
    {
        return new PriceQuoteDTO(
            new PriceSelectionSnapshotDTO(
                priceSetId: 'set-retail-77',
                priceSetRevision: 3,
                priceableReference: $reference,
                priceId: 'price-retail-77',
                priceRevision: 2,
                priceListId: null,
                priceListRevision: null,
                currencyCode: $currency,
                quantity: 1,
                amountMinor: $amountMinor,
                referenceAmountMinor: null,
                taxIncluded: false,
                selectedAt: new \DateTimeImmutable('2026-09-21T16:00:00+00:00'),
                context: [],
                explanation: 'Retailing integration test.',
            ),
            new PriceCurrencyMetadataDTO($currency, 2, 100),
        );
    }
}
