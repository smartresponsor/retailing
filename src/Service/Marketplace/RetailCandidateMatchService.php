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
    ) {
    }

    /**
     * @return list<array{service: RetailEntity, serviceAreaStatus: 'exact'|'requires_geovalidation', distanceMeters: ?float}>
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

            $matches[] = [
                'service' => $service,
                'serviceAreaStatus' => $areaMatch['status'],
                'distanceMeters' => $areaMatch['distanceMeters'],
            ];
        }

        return $matches;
    }
}
