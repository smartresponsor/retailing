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
            ['standard-tv-mounting', 'Standard TV Mounting', 12900, 9900],
            ['chandelier-installation', 'Chandelier Installation', 14900, 11900],
            ['ceiling-fan-replacement', 'Ceiling Fan Replacement', 13900, 10900],
            ['video-doorbell-installation', 'Video Doorbell Installation', 8900, 7900],
            ['smart-lock-installation', 'Smart Lock Installation', 9900, 7900],
            ['dishwasher-installation', 'Dishwasher Installation', 16900, 13900],
        ],
        'Katy Home Care' => [
            ['standard-home-cleaning', 'Standard Home Cleaning', 12900, 10900],
            ['deep-cleaning', 'Deep Cleaning', 19900, 16900],
            ['closet-organization', 'Closet Organization', 11900, 9900],
            ['kitchen-organization', 'Kitchen Organization', 11900, 9900],
        ],
        'Bayou Assembly & Mounting' => [
            ['standard-furniture-assembly', 'Standard Furniture Assembly', 8900, 6900],
            ['gallery-wall-installation', 'Gallery Wall Installation', 11900, 9900],
            ['heavy-mirror-hanging', 'Heavy Mirror Hanging', 12900, 10900],
            ['floating-shelf-installation', 'Floating Shelf Installation', 9900, 7900],
            ['curtain-rod-installation', 'Curtain Rod Installation', 7900, 6900],
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

            foreach ($offerings as [$categorySlug, $title, $startingAmount, $minimumAmount]) {
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
                    'owner' => (string) $vendorId,
                    'title' => $title,
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
                $retail->setFulfillmentProfile([
                    'mode' => 'onsite',
                    'serviceAreaRequired' => true,
                ]);
                $retail->setPricingProfile([
                    'mode' => 'starting_at',
                    'startingAmountMinor' => $startingAmount,
                    'minimumProjectAmountMinor' => $minimumAmount,
                    'currency' => 'USD',
                    'customerBudgetSupported' => true,
                ]);
                $retail->publish();
                $manager->persist($retail);
            }
        }

        $manager->flush();
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
