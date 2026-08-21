<?php

declare(strict_types=1);

namespace App\Retailing\Service\Marketplace;

final class RetailAvailabilityMatchService
{
    /**
     * @param array<string, mixed>|null $taskProfile
     * @param array<string, mixed>|null $serviceProfile
     *
     * @return 'compatible'|'requires_scheduling'|null
     */
    public function match(?array $taskProfile, ?array $serviceProfile): ?string
    {
        $preferredWindows = $taskProfile['preferredWindows'] ?? null;
        $weeklyWindows = $serviceProfile['weeklyWindows'] ?? null;
        if (!is_array($preferredWindows) || [] === $preferredWindows || !is_array($weeklyWindows) || [] === $weeklyWindows) {
            return 'requires_scheduling';
        }

        foreach ($preferredWindows as $day => $taskWindows) {
            if (!is_string($day) || !is_array($taskWindows)) {
                continue;
            }

            $serviceWindows = $weeklyWindows[strtolower(trim($day))] ?? null;
            if (!is_array($serviceWindows)) {
                continue;
            }

            foreach ($taskWindows as $taskWindow) {
                if (!is_array($taskWindow)) {
                    continue;
                }
                foreach ($serviceWindows as $serviceWindow) {
                    if (is_array($serviceWindow) && $this->overlaps($taskWindow, $serviceWindow)) {
                        return 'compatible';
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $left
     * @param array<string, mixed> $right
     */
    private function overlaps(array $left, array $right): bool
    {
        $leftStart = $this->minutes($left['start'] ?? null);
        $leftEnd = $this->minutes($left['end'] ?? null);
        $rightStart = $this->minutes($right['start'] ?? null);
        $rightEnd = $this->minutes($right['end'] ?? null);
        if (null === $leftStart || null === $leftEnd || null === $rightStart || null === $rightEnd) {
            return false;
        }

        return $leftStart < $leftEnd && $rightStart < $rightEnd && max($leftStart, $rightStart) < min($leftEnd, $rightEnd);
    }

    private function minutes(mixed $value): ?int
    {
        if (!is_string($value) || 1 !== preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value)) {
            return null;
        }

        [$hour, $minute] = array_map('intval', explode(':', $value, 2));

        return ($hour * 60) + $minute;
    }
}
