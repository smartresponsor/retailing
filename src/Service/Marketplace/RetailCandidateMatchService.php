<?php

declare(strict_types=1);

namespace App\Retailing\Service\Marketplace;

use App\Retailing\Entity\Retail\RetailEntity;
use App\Retailing\Enum\Retail\RetailKind;
use App\Retailing\Repository\Retail\RetailRepository;

final class RetailCandidateMatchService
{
    public function __construct(private readonly RetailRepository $retailRepository)
    {
    }

    /**
     * @return list<array{service: RetailEntity, serviceAreaStatus: 'exact'|'requires_geovalidation'}>
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
            $areaStatus = $this->serviceAreaStatus($task->getLocationProfile(), $service->getLocationProfile());
            if (null === $areaStatus) {
                continue;
            }

            $matches[] = [
                'service' => $service,
                'serviceAreaStatus' => $areaStatus,
            ];
        }

        return $matches;
    }

    /**
     * @param array<string, mixed>|null $taskProfile
     * @param array<string, mixed>|null $serviceProfile
     *
     * @return 'exact'|'requires_geovalidation'|null
     */
    private function serviceAreaStatus(?array $taskProfile, ?array $serviceProfile): ?string
    {
        $postalCode = $this->postalCode($taskProfile);
        if (null === $postalCode || null === $serviceProfile) {
            return 'requires_geovalidation';
        }

        $mode = is_string($serviceProfile['mode'] ?? null) ? strtolower(trim($serviceProfile['mode'])) : '';
        if ('postal_codes' === $mode) {
            $postalCodes = $serviceProfile['postalCodes'] ?? null;
            if (!is_array($postalCodes)) {
                return 'requires_geovalidation';
            }

            foreach ($postalCodes as $candidate) {
                if (is_scalar($candidate) && $postalCode === trim((string) $candidate)) {
                    return 'exact';
                }
            }

            return null;
        }

        if ('radius' === $mode) {
            $origin = $serviceProfile['origin'] ?? null;
            if (is_array($origin) && $postalCode === $this->postalCode($origin)) {
                return 'exact';
            }

            return 'requires_geovalidation';
        }

        return 'requires_geovalidation';
    }

    /** @param array<string, mixed>|null $profile */
    private function postalCode(?array $profile): ?string
    {
        if (null === $profile) {
            return null;
        }

        $value = $profile['postalCode'] ?? null;
        if (!is_scalar($value)) {
            return null;
        }

        $postalCode = trim((string) $value);

        return '' === $postalCode ? null : $postalCode;
    }
}
