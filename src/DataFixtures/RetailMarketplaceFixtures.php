<?php

declare(strict_types=1);

namespace App\Retailing\DataFixtures;

use App\Retailing\Entity\Retail\RetailEntity;
use App\Retailing\Enum\Retail\RetailKind;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

final class RetailMarketplaceFixtures extends Fixture implements FixtureGroupInterface
{
    private const OFFERINGS = [
        'OneTasker Houston' => [
            ['standard-tv-mounting', 'TV Mounting', 12900, 9900, null],
            ['chandelier-installation', 'Chandelier Installation', 14900, 11900, 4900],
            ['ceiling-fan-replacement', 'Ceiling Fan Replacement', 13900, 10900, 4900],
            ['security-camera-installation', 'Security Camera Installation', 11900, 9900, null],
            ['video-doorbell-installation', 'Video Doorbell Installation', 8900, 7900, null],
            ['door-lock-replacement', 'Door Lock Replacement', 8900, 6900, null],
            ['smart-lock-installation', 'Smart Lock Installation', 9900, 7900, null],
            ['dishwasher-installation', 'Dishwasher Installation', 16900, 13900, null],
        ],
        'Katy Home Care' => [
            ['standard-home-cleaning', 'House Cleaning', 12900, 10900, null],
            ['deep-cleaning', 'Deep Cleaning', 19900, 16900, null],
            ['closet-organization', 'Closet Organization', 11900, 9900, null],
            ['kitchen-organization', 'Kitchen Organization', 11900, 9900, null],
        ],
        'Bayou Assembly & Mounting' => [
            ['standard-furniture-assembly', 'Furniture Assembly', 8900, 6900, null],
            ['gallery-wall-installation', 'Gallery Wall Installation', 11900, 9900, null],
            ['heavy-mirror-hanging', 'Heavy Mirror Hanging', 12900, 10900, null],
            ['floating-shelf-installation', 'Floating Shelf Installation', 9900, 7900, null],
            ['curtain-rod-installation', 'Curtain Rod Installation', 7900, 6900, null],
        ],
    ];

    public static function getGroups(): array
    {
        return ['retailing_marketplace'];
    }

    public function load(ObjectManager $manager): void
    {
        if (!$manager instanceof EntityManagerInterface) {
            return;
        }

        foreach (self::OFFERINGS as $brand => $offerings) {
            $vendorId = $manager->getConnection()->fetchOne(
                'SELECT id FROM vendor WHERE brand_name = :brand ORDER BY id LIMIT 1',
                ['brand' => $brand],
            );
            if (false === $vendorId) {
                continue;
            }

            foreach ($offerings as [$categorySlug, $title, $startingAmount, $minimumAmount, $serviceCallAmount]) {
                $categoryId = $manager->getConnection()->fetchOne(
                    <<<'SQL'
SELECT category.id
FROM category
JOIN catalog ON catalog.id = category.catalog_id
WHERE catalog.object_code = 'services'
  AND category.slug = :slug
  AND category.published = TRUE
LIMIT 1
SQL,
                    ['slug' => $categorySlug],
                );
                if (false === $categoryId) {
                    continue;
                }

                $retail = $manager->getRepository(RetailEntity::class)->findOneBy([
                    'ownerType' => 'vendor',
                    'owner' => (string) $vendorId,
                    'categoryId' => (string) $categoryId,
                ]);
                if (!$retail instanceof RetailEntity) {
                    $retail = new RetailEntity();
                    $retail->setKind(RetailKind::Service);
                } elseif (RetailKind::Service !== $retail->getKind()) {
                    continue;
                }
                $retail->setOwnerType('vendor');
                $retail->setOwner((string) $vendorId);
                $retail->setCatalogCode('services');
                $retail->setCategoryId((string) $categoryId);
                $retail->setTitle($title);
                $retail->setDescription($this->description($brand, $title));
                $retail->setAmountMinor($startingAmount);
                $retail->setCurrency('USD');
                $retail->setLocation('Houston, TX');
                $retail->setLocationProfile($this->serviceArea($brand));
                $retail->setAvailabilityProfile($this->availability($brand));
                $retail->setFulfillmentProfile([
                    'mode' => 'onsite',
                    'serviceAreaRequired' => true,
                ]);
                $pricingProfile = [
                    'mode' => 'starting_at',
                    'startingAmountMinor' => $startingAmount,
                    'minimumProjectAmountMinor' => $minimumAmount,
                    'currency' => 'USD',
                    'customerBudgetSupported' => true,
                ];
                if (null !== $serviceCallAmount) {
                    $pricingProfile['serviceCallAmountMinor'] = $serviceCallAmount;
                }
                $retail->setPricingProfile($pricingProfile);
                $retail->publish();
                $manager->persist($retail);
            }
        }

        $manager->flush();
    }

    /** @return array<string, mixed> */
    private function availability(string $brand): array
    {
        return match ($brand) {
            'Katy Home Care' => [
                'timezone' => 'America/Chicago',
                'weeklyWindows' => [
                    'monday' => [['start' => '08:00', 'end' => '17:00']],
                    'tuesday' => [['start' => '08:00', 'end' => '17:00']],
                    'wednesday' => [['start' => '08:00', 'end' => '17:00']],
                    'thursday' => [['start' => '08:00', 'end' => '17:00']],
                    'friday' => [['start' => '08:00', 'end' => '17:00']],
                    'saturday' => [['start' => '09:00', 'end' => '15:00']],
                ],
                'minimumLeadHours' => 24,
                'bookingHorizonDays' => 30,
            ],
            default => [
                'timezone' => 'America/Chicago',
                'weeklyWindows' => [
                    'monday' => [['start' => '09:00', 'end' => '18:00']],
                    'tuesday' => [['start' => '09:00', 'end' => '18:00']],
                    'wednesday' => [['start' => '09:00', 'end' => '18:00']],
                    'thursday' => [['start' => '09:00', 'end' => '18:00']],
                    'friday' => [['start' => '09:00', 'end' => '18:00']],
                    'saturday' => [['start' => '10:00', 'end' => '16:00']],
                ],
                'minimumLeadHours' => 4,
                'bookingHorizonDays' => 21,
            ],
        };
    }

    /** @return array<string, mixed> */
    private function serviceArea(string $brand): array
    {
        return match ($brand) {
            'Katy Home Care' => [
                'mode' => 'radius',
                'origin' => ['postalCode' => '77493', 'city' => 'Katy', 'state' => 'TX'],
                'radiusMiles' => 20,
            ],
            'Bayou Assembly & Mounting' => [
                'mode' => 'postal_codes',
                'postalCodes' => ['77024', '77055', '77079', '77080', '77084', '77094'],
            ],
            default => [
                'mode' => 'postal_codes',
                'postalCodes' => ['77449', '77450', '77493', '77084', '77094'],
            ],
        };
    }

    private function description(string $brand, string $title): string
    {
        return sprintf('%s by %s. Indoor residential service with transparent starting pricing and a defined Houston-area service zone.', $title, $brand);
    }
}
