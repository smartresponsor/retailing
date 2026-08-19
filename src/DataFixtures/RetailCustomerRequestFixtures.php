<?php

declare(strict_types=1);

namespace App\Retailing\DataFixtures;

use App\Retailing\Entity\Retail\RetailEntity;
use App\Retailing\Enum\Retail\RetailKind;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

final class RetailCustomerRequestFixtures extends Fixture implements FixtureGroupInterface
{
    private const REQUESTS = [
        ['emily.customer@smartresponsor.local', 'standard-tv-mounting', 'Mount a 65-inch TV in living room', 18000, '77493', 'Katy', 'TX'],
        ['james.customer@smartresponsor.local', 'ceiling-fan-replacement', 'Replace bedroom ceiling fan', 22000, '77449', 'Katy', 'TX'],
        ['sophia.customer@smartresponsor.local', 'standard-home-cleaning', 'Clean a two-bedroom apartment', 17000, '77084', 'Houston', 'TX'],
        ['michael.customer@smartresponsor.local', 'standard-furniture-assembly', 'Assemble bedroom furniture set', 16000, '77094', 'Houston', 'TX'],
    ];

    public static function getGroups(): array
    {
        return ['retailing_customer_requests'];
    }

    public function load(ObjectManager $manager): void
    {
        if (!$manager instanceof EntityManagerInterface) {
            return;
        }

        foreach (self::REQUESTS as [$email, $categorySlug, $title, $budgetAmountMinor, $postalCode, $city, $state]) {
            $customerId = $manager->getConnection()->fetchOne('SELECT id FROM access WHERE email = :email ORDER BY id LIMIT 1', ['email' => $email]);
            if (false === $customerId) {
                continue;
            }

            $categoryId = $manager->getConnection()->fetchOne(
                "SELECT category.id FROM category JOIN catalog ON catalog.id = category.catalog_id WHERE catalog.object_code = 'services' AND category.slug = :slug AND category.published = TRUE LIMIT 1",
                ['slug' => $categorySlug],
            );
            if (false === $categoryId) {
                continue;
            }

            $retail = $manager->getRepository(RetailEntity::class)->findOneBy([
                'ownerType' => 'access',
                'owner' => (string) $customerId,
                'title' => $title,
            ]);
            if (!$retail instanceof RetailEntity) {
                $retail = new RetailEntity();
                $retail->setKind(RetailKind::Task);
            } elseif (RetailKind::Task !== $retail->getKind()) {
                continue;
            }

            $retail->setOwnerType('access');
            $retail->setOwner((string) $customerId);
            $retail->setCatalogCode('services');
            $retail->setCategoryId((string) $categoryId);
            $retail->setTitle($title);
            $retail->setDescription('Customer marketplace request fixture with realistic budget and on-site location details.');
            $retail->setAmountMinor($budgetAmountMinor);
            $retail->setCurrency('USD');
            $retail->setLocation(sprintf('%s, %s %s', $city, $state, $postalCode));
            $retail->setLocationProfile([
                'mode' => 'address_hint',
                'postalCode' => $postalCode,
                'city' => $city,
                'state' => $state,
                'country' => 'US',
            ]);
            $retail->setFulfillmentProfile(['mode' => 'onsite', 'customerRequest' => true]);
            $retail->setPricingProfile([
                'mode' => 'customer_budget',
                'budgetAmountMinor' => $budgetAmountMinor,
                'currency' => 'USD',
                'negotiable' => true,
            ]);
            $retail->publish();
            $manager->persist($retail);
        }

        $manager->flush();
    }
}
