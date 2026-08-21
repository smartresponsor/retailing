<?php

declare(strict_types=1);

namespace App\Retailing\Service\Marketplace;

use App\Retailing\Entity\Retail\RetailEntity;
use App\Retailing\Enum\Retail\RetailKind;
use App\Retailing\Repository\Retail\RetailRepository;

final class RetailCandidateMatchService
{
    public function __construct(
        private readonly RetailRepository $retailRepository,
        private readonly RetailServiceAreaMatchService $serviceAreaMatchService,
        private readonly RetailAvailabilityMatchService $availabilityMatchService,
    ) {
    }

    /**
     * @return list<array{service: RetailEntity, serviceAreaStatus: 'exact'|'requires_geovalidation', distanceMeters: ?float, availabilityStatus: 'compatible'|'requires_scheduling', budgetStatus: 'within_budget'|'over_budget'|'unknown'}>
     */
    public function matchForTask(RetailEntity $task): array
    {
        if (RetailKind::Task !== $task->getKind() || 'access' !== $task->getOwnerType() || 'published' !== $task->getObjectStatus()) {
            throw new \InvalidArgumentException('Marketplace candidate matching requires a published access-owned task.');
        }

        $categoryId = $task->getCategoryId();
        if (null === $categoryId || '' === trim($categoryId)) {
            return [];
        }

        $matches = [];
        foreach ($this->retailRepository->findPublishedVendorServicesByCategory($categoryId) as $service) {
            $areaMatch = $this->serviceAreaMatchService->match($task->getLocationProfile(), $service->getLocationProfile());
            if (null === $areaMatch) {
                continue;
            }

            $availabilityStatus = $this->availabilityMatchService->match($task->getAvailabilityProfile(), $service->getAvailabilityProfile());
            if (null === $availabilityStatus) {
                continue;
            }

            $matches[] = [
                'service' => $service,
                'serviceAreaStatus' => $areaMatch['status'],
                'distanceMeters' => $areaMatch['distanceMeters'],
                'availabilityStatus' => $availabilityStatus,
                'budgetStatus' => $this->budgetStatus($task, $service),
            ];
        }

        usort($matches, $this->compareMatches(...));

        return $matches;
    }

    private function budgetStatus(RetailEntity $task, RetailEntity $service): string
    {
        if ($task->getCurrency() !== $service->getCurrency()) {
            return 'unknown';
        }

        $taskBudget = $task->getPricingProfile()['budgetAmountMinor'] ?? $task->getAmountMinor();
        $minimumProject = $service->getPricingProfile()['minimumProjectAmountMinor'] ?? $service->getAmountMinor();
        if (!is_numeric($taskBudget) || !is_numeric($minimumProject)) {
            return 'unknown';
        }

        return (int) $minimumProject <= (int) $taskBudget ? 'within_budget' : 'over_budget';
    }

    /**
     * @param array{service: RetailEntity, serviceAreaStatus: string, distanceMeters: ?float, availabilityStatus: string, budgetStatus: string} $left
     * @param array{service: RetailEntity, serviceAreaStatus: string, distanceMeters: ?float, availabilityStatus: string, budgetStatus: string} $right
     */
    private function compareMatches(array $left, array $right): int
    {
        $areaOrder = ['exact' => 0, 'requires_geovalidation' => 1];
        $availabilityOrder = ['compatible' => 0, 'requires_scheduling' => 1];
        $budgetOrder = ['within_budget' => 0, 'unknown' => 1, 'over_budget' => 2];

        $comparison = ($areaOrder[$left['serviceAreaStatus']] ?? 99) <=> ($areaOrder[$right['serviceAreaStatus']] ?? 99);
        if (0 !== $comparison) {
            return $comparison;
        }

        $comparison = ($availabilityOrder[$left['availabilityStatus']] ?? 99) <=> ($availabilityOrder[$right['availabilityStatus']] ?? 99);
        if (0 !== $comparison) {
            return $comparison;
        }

        $comparison = ($budgetOrder[$left['budgetStatus']] ?? 99) <=> ($budgetOrder[$right['budgetStatus']] ?? 99);
        if (0 !== $comparison) {
            return $comparison;
        }

        $leftDistance = $left['distanceMeters'];
        $rightDistance = $right['distanceMeters'];
        if (null !== $leftDistance || null !== $rightDistance) {
            $comparison = (null === $leftDistance ? PHP_FLOAT_MAX : $leftDistance) <=> (null === $rightDistance ? PHP_FLOAT_MAX : $rightDistance);
            if (0 !== $comparison) {
                return $comparison;
            }
        }

        return ($left['service']->getAmountMinor() ?? PHP_INT_MAX) <=> ($right['service']->getAmountMinor() ?? PHP_INT_MAX);
    }
}
