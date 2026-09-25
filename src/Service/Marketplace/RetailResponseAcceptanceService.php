<?php

declare(strict_types=1);

namespace App\Retailing\Service\Marketplace;

use App\Retailing\Entity\Retail\RetailEntity;
use App\Retailing\Entity\Retail\RetailResponseEntity;
use App\Retailing\Repository\RetailRepository;
use App\Retailing\Repository\RetailResponseRepository;

final readonly class RetailResponseAcceptanceService
{
    public function __construct(
        private RetailRepository $retailRepository,
        private RetailResponseRepository $responseRepository,
    ) {}

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

        $service = $this->service($response);
        $retail->acceptResponse($response, $service);

        foreach ($this->responseRepository->findSubmittedForRetail($retail->getId()) as $otherResponse) {
            if ($otherResponse->getId() !== $response->getId()) {
                $otherResponse->reject();
            }
        }

        $this->responseRepository->save($response);
        $this->retailRepository->save($retail);
        $this->responseRepository->flush();

        return $retail;
    }

    public function synchronizeAccepted(RetailResponseEntity $response): RetailEntity
    {
        if ('accepted' !== $response->getStatus()) {
            throw new \DomainException('Only an accepted retail response can synchronize customer selection.');
        }

        $retail = $response->getRetail();
        $service = $this->service($response);
        $retail->synchronizeAcceptedResponse($response, $service);
        $this->retailRepository->save($retail, true);

        return $retail;
    }

    private function service(RetailResponseEntity $response): ?RetailEntity
    {
        $serviceId = $response->getServiceId();
        if (null === $serviceId) {
            return null;
        }
        $service = $this->retailRepository->find($serviceId);
        if (!$service instanceof RetailEntity) {
            throw new \DomainException('Accepted retail response references a missing marketplace service.');
        }

        return $service;
    }
}
