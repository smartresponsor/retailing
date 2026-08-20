<?php

declare(strict_types=1);

namespace App\Retailing\Service\Marketplace;

use App\Retailing\Entity\Retail\RetailEntity;
use App\Retailing\Entity\Retail\RetailResponseEntity;
use App\Retailing\Repository\Retail\RetailRepository;
use App\Retailing\Repository\Retail\RetailResponseRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class RetailResponseAcceptanceService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RetailRepository $retailRepository,
        private RetailResponseRepository $responseRepository,
    ) {
    }

    public function accept(RetailResponseEntity $response): RetailEntity
    {
        if ('submitted' !== $response->getStatus()) {
            throw new \DomainException('Only a submitted retail response can be accepted.');
        }
        if ($response->getId() <= 0) {
            throw new \DomainException('Retail response must be persisted before acceptance.');
        }

        $retail = $response->getRetail();
        $existingAccepted = $this->responseRepository->findAcceptedForRetail($retail->getId());
        if ($existingAccepted instanceof RetailResponseEntity && $existingAccepted->getId() !== $response->getId()) {
            throw new \DomainException('Customer request already has an accepted vendor response.');
        }

        $serviceId = $response->getServiceId();
        if (null === $serviceId) {
            throw new \DomainException('Accepted retail response must reference a marketplace service.');
        }
        $service = $this->retailRepository->find($serviceId);
        if (!$service instanceof RetailEntity) {
            throw new \DomainException('Accepted retail response references a missing marketplace service.');
        }

        $pricing = $response->getPricingProfile() ?? [];
        $amountMinor = $pricing['amountMinor'] ?? null;
        if (!is_numeric($amountMinor) || (int) $amountMinor < 0) {
            throw new \DomainException('Accepted retail response requires an exact non-negative amountMinor.');
        }

        $response->accept();
        $retail->selectServiceCandidate($service, (int) $amountMinor, $response->getId());

        foreach ($this->responseRepository->findSubmittedForRetail($retail->getId()) as $otherResponse) {
            if ($otherResponse->getId() !== $response->getId()) {
                $otherResponse->reject();
            }
        }

        $this->entityManager->persist($response);
        $this->entityManager->persist($retail);
        $this->entityManager->flush();

        return $retail;
    }
}
