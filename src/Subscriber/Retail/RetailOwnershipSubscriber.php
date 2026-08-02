<?php

declare(strict_types=1);

namespace App\Retailing\Subscriber\Retail;

use App\Cruding\Dto\Crud\CrudMutationLifecycleContext;
use App\Cruding\ServiceInterface\Crud\CrudMutationLifecycleSubscriberInterface;
use App\Retailing\Entity\Retail\RetailEntity;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class RetailOwnershipSubscriber implements CrudMutationLifecycleSubscriberInterface
{
    public function __construct(private Security $security)
    {
    }

    public function supports(CrudMutationLifecycleContext $context): bool
    {
        return $context->object instanceof RetailEntity;
    }

    public function before(CrudMutationLifecycleContext $context): void
    {
        if (!$context->object instanceof RetailEntity || 'create' !== $context->operation) {
            return;
        }
        $actor = $this->security->getUser();
        if (!is_object($actor) || !method_exists($actor, 'getId')) {
            throw new \DomainException('An authenticated vendor is required to create a retail listing.');
        }
        $actorId = $actor->getId();
        if (!is_scalar($actorId) || '' === trim((string) $actorId)) {
            throw new \DomainException('The authenticated vendor has no usable identifier.');
        }
        $context->object->setOwner((string) $actorId);
    }

    public function after(CrudMutationLifecycleContext $context): void
    {
    }
}
