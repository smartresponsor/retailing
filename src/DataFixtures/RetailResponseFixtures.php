<?php

declare(strict_types=1);

namespace App\Retailing\DataFixtures;

use App\Retailing\Entity\Retail\RetailEntity;
use App\Retailing\Entity\Retail\RetailResponseEntity;
use App\Retailing\Repository\RetailResponseRepository;
use App\Retailing\Service\Marketplace\RetailResponseAcceptanceService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;

final class RetailResponseFixtures extends Fixture implements FixtureGroupInterface
{
    private const RESPONSES = [
        ['emily.customer@smartresponsor.local', 'Mount a 65-inch TV in living room', 'TV Mounting', 'quote', 12500, true],
        ['james.customer@smartresponsor.local', 'Replace bedroom ceiling fan', 'Ceiling Fan Replacement', 'quote', 13500, true],
        ['sophia.customer@smartresponsor.local', 'Clean a two-bedroom apartment', 'House Cleaning', 'estimate', 15000, false],
        ['michael.customer@smartresponsor.local', 'Assemble bedroom furniture set', 'Furniture Assembly', 'quote', 8500, true],
    ];

    public function __construct(private readonly RetailResponseAcceptanceService $acceptanceService) {}

    public static function getGroups(): array
    {
        return ['retailing_responses'];
    }

    public function load(ObjectManager $manager): void
    {
        if (!$manager instanceof EntityManagerInterface) {
            throw new \RuntimeException('Doctrine entity manager is required to load retail response fixtures.');
        }

        /** @var RetailResponseRepository $responseRepository */
        $responseRepository = $manager->getRepository(RetailResponseEntity::class);

        foreach (self::RESPONSES as [$email, $taskTitle, $serviceTitle, $pricingModel, $amountMinor, $accept]) {
            $customerId = $manager->getConnection()->fetchOne(
                'SELECT id FROM access WHERE email = :email ORDER BY id LIMIT 1',
                ['email' => $email],
            );
            if (false === $customerId) {
                throw new \RuntimeException(sprintf('Marketplace response fixture customer is missing: %s.', $email));
            }

            $task = $manager->getRepository(RetailEntity::class)->findOneBy([
                'ownerType' => 'access',
                'owner' => (string) $customerId,
                'title' => $taskTitle,
            ]);
            if (!$task instanceof RetailEntity) {
                throw new \RuntimeException(sprintf('Marketplace response fixture task is missing: %s.', $taskTitle));
            }

            $service = $manager->getRepository(RetailEntity::class)->findOneBy([
                'ownerType' => 'vendor',
                'title' => $serviceTitle,
                'categoryId' => $task->getCategoryId(),
            ]);
            if (!$service instanceof RetailEntity || null === $service->getOwner()) {
                throw new \RuntimeException(sprintf('Marketplace response fixture service is missing: %s.', $serviceTitle));
            }

            $response = $responseRepository->findForRetailAndVendor($task->getId(), $service->getOwner());
            if (!$response instanceof RetailResponseEntity) {
                $response = new RetailResponseEntity($task, $service->getOwner());
            }

            if ('draft' === $response->getStatus()) {
                $response->setService($service);
                $response->setDescription(sprintf('%s response for %s.', ucfirst($pricingModel), $taskTitle));
                $response->setPricingProfile([
                    'model' => $pricingModel,
                    'amountMinor' => $amountMinor,
                    'currency' => $service->getCurrency(),
                ]);
                $response->setFulfillmentProfile($service->getFulfillmentProfile());
                $response->setAvailabilityProfile($service->getAvailabilityProfile());
                $response->setLocationProfile($service->getLocationProfile());
                $manager->persist($response);
                $manager->flush();
                $response->submit();
                $manager->persist($response);
                $manager->flush();
            }

            if ($accept && 'submitted' === $response->getStatus()) {
                $this->acceptanceService->accept($response);
                continue;
            }

            if ('accepted' === $response->getStatus()) {
                $this->acceptanceService->synchronizeAccepted($response);
                continue;
            }

            if ('submitted' !== $response->getStatus()) {
                throw new \RuntimeException(sprintf('Marketplace response fixture has incompatible lifecycle state: %s.', $response->getStatus()));
            }
        }
    }
}
