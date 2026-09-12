<?php

declare(strict_types=1);

namespace App\Retailing\Service\Marketplace;

use App\Locating\ServiceInterface\Provider\Location\Runtime\Geo\LocationDistanceServiceInterface;

final class RetailServiceAreaMatchService
{
    public function __construct(private readonly LocationDistanceServiceInterface $distanceService) {}

    /**
     * @param array<string, mixed>|null $taskProfile
     * @param array<string, mixed>|null $serviceProfile
     *
     * @return array{status: 'exact'|'requires_geovalidation', distanceMeters: ?float}|null
     */
    public function match(?array $taskProfile, ?array $serviceProfile): ?array
    {
        $postalCode = $this->postalCode($taskProfile);
        if (null === $serviceProfile) {
            return ['status' => 'requires_geovalidation', 'distanceMeters' => null];
        }

        $mode = is_string($serviceProfile['mode'] ?? null) ? strtolower(trim($serviceProfile['mode'])) : '';
        if ('postal_codes' === $mode) {
            if (null === $postalCode) {
                return ['status' => 'requires_geovalidation', 'distanceMeters' => null];
            }

            $postalCodes = $serviceProfile['postalCodes'] ?? null;
            if (!is_array($postalCodes)) {
                return ['status' => 'requires_geovalidation', 'distanceMeters' => null];
            }

            foreach ($postalCodes as $candidate) {
                if (is_scalar($candidate) && $postalCode === trim((string) $candidate)) {
                    return ['status' => 'exact', 'distanceMeters' => null];
                }
            }

            return null;
        }

        if ('radius' === $mode) {
            $origin = $serviceProfile['origin'] ?? null;
            if (is_array($origin) && null !== $postalCode && $postalCode === $this->postalCode($origin)) {
                return ['status' => 'exact', 'distanceMeters' => 0.0];
            }

            $taskCoordinates = $this->coordinates($taskProfile);
            $originCoordinates = is_array($origin) ? $this->coordinates($origin) : null;
            $radiusMiles = $serviceProfile['radiusMiles'] ?? null;
            if (null === $taskCoordinates || null === $originCoordinates || !is_numeric($radiusMiles)) {
                return ['status' => 'requires_geovalidation', 'distanceMeters' => null];
            }

            $distanceMeters = $this->distanceService->meters(
                $taskCoordinates['latitude'],
                $taskCoordinates['longitude'],
                $originCoordinates['latitude'],
                $originCoordinates['longitude'],
            );
            $radiusMeters = max(0.0, (float) $radiusMiles) * 1609.344;
            if ($distanceMeters > $radiusMeters) {
                return null;
            }

            return ['status' => 'exact', 'distanceMeters' => $distanceMeters];
        }

        return ['status' => 'requires_geovalidation', 'distanceMeters' => null];
    }

    /**
     * @param array<string, mixed>|null $profile
     *
     * @return array{latitude: float, longitude: float}|null
     */
    private function coordinates(?array $profile): ?array
    {
        if (null === $profile) {
            return null;
        }

        $point = is_array($profile['geoPoint'] ?? null) ? $profile['geoPoint'] : $profile;
        $latitude = $point['latitude'] ?? $point['lat'] ?? null;
        $longitude = $point['longitude'] ?? $point['lon'] ?? $point['lng'] ?? null;
        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            return null;
        }

        $latitude = (float) $latitude;
        $longitude = (float) $longitude;
        if ($latitude < -90.0 || $latitude > 90.0 || $longitude < -180.0 || $longitude > 180.0) {
            return null;
        }

        return ['latitude' => $latitude, 'longitude' => $longitude];
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
