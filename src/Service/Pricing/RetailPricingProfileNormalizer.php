<?php

declare(strict_types=1);

namespace App\Retailing\Service\Pricing;

final class RetailPricingProfileNormalizer
{
    public function normalize(string $retailKind, array $input): array
    {
        $kind = strtolower(trim($retailKind));
        $model = strtolower(trim((string) ($input['model'] ?? '')));
        $allowed = match ($kind) {
            'service' => ['fixed', 'hourly', 'minimum', 'quote'],
            'goods' => ['fixed', 'deposit'],
            'task' => ['budget', 'range', 'fixed'],
            'project' => ['budget', 'range', 'fixed', 'quote'],
            default => throw new \InvalidArgumentException('Unsupported Retail kind for pricing profile.'),
        };
        if (!in_array($model, $allowed, true)) {
            throw new \InvalidArgumentException('Pricing model is not valid for the selected Retail kind.');
        }

        $currency = strtoupper(trim((string) ($input['currency'] ?? 'USD')));
        if (3 !== strlen($currency)) {
            throw new \InvalidArgumentException('Pricing currency must use a three-letter code.');
        }

        $amountMinor = $this->nullableNonNegativeInt($input['amountMinor'] ?? null, 'Primary amount');
        $maximumAmountMinor = $this->nullableNonNegativeInt($input['maximumAmountMinor'] ?? null, 'Maximum amount');
        if ('range' === $model && (null === $amountMinor || null === $maximumAmountMinor || $maximumAmountMinor < $amountMinor)) {
            throw new \InvalidArgumentException('Range pricing requires a valid minimum and maximum amount.');
        }
        if (!in_array($model, ['quote', 'range'], true) && null === $amountMinor) {
            throw new \InvalidArgumentException('Primary amount is required for the selected pricing model.');
        }

        return array_filter([
            'version' => 1,
            'kind' => $kind,
            'model' => $model,
            'amountMinor' => $amountMinor,
            'maximumAmountMinor' => $maximumAmountMinor,
            'currency' => $currency,
        ], static fn (mixed $value): bool => null !== $value);
    }

    private function nullableNonNegativeInt(mixed $value, string $label): ?int
    {
        if (null === $value || '' === trim((string) $value)) {
            return null;
        }
        if (!is_numeric($value) || (int) $value < 0) {
            throw new \InvalidArgumentException($label.' must be a non-negative integer amount in minor units.');
        }

        return (int) $value;
    }
}
