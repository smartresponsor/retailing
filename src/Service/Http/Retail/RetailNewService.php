<?php

declare(strict_types=1);

namespace App\Retailing\Service\Http\Retail;

use App\Cruding\Dto\Crud\Entrypoint\CrudServiceContext;
use App\Cruding\Dto\Crud\Entrypoint\CrudServiceResult;
use App\Cruding\Service\Crud\AbstractCrudService;
use App\Retailing\Entity\Retail\RetailEntity;
use App\Retailing\Enum\Retail\RetailKind;
use App\Retailing\Service\Marketplace\RetailCandidateMatchService;
use App\Retailing\Service\Marketplace\RetailOrderIntentFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class RetailNewService extends AbstractCrudService
{
    private const SESSION_KEY = 'retail_placement';

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RetailCandidateMatchService $candidateMatchService,
        private readonly RetailOrderIntentFactory $orderIntentFactory,
    ) {
    }

    protected function afterDefault(CrudServiceContext $context, CrudServiceResult $result): CrudServiceResult
    {
        if (!$context->isPost() || !$result->payload() instanceof RedirectResponse || !$context->object instanceof RetailEntity) {
            return $result;
        }

        if ($context->object->getId() <= 0 || !$context->request->hasSession()) {
            return $result;
        }

        $ownerId = $context->object->getOwner()
            ?? $this->scalarString($context->actorIdentityValue())
            ?? $this->scalarString($context->actorUserId());

        if (null === $ownerId) {
            return $result;
        }

        $ownerType = $context->object->getOwnerType() ?? 'access';
        $placement = [];

        $placement['retailId'] = (string) $context->object->getId();
        $placement['placementReference'] = 'retail:'.(string) $context->object->getId();
        $placement['tenantId'] = $this->tenantId($context);
        $placement['ownerType'] = $ownerType;
        $placement['ownerId'] = $ownerId;
        if ('vendor' === $ownerType) {
            $placement['vendorId'] = $ownerId;
        }
        $placement['kind'] = $context->object->getKind()->value;
        $placement['catalogCode'] = $context->object->getCatalogCode();
        $placement['categoryId'] = $context->object->getCategoryId();
        $placement['amountMinor'] = $context->object->getAmountMinor();
        $placement['currency'] = $context->object->getCurrency();
        if (RetailKind::Task === $context->object->getKind() && 'published' === $context->object->getObjectStatus()) {
            $placement['candidateServices'] = array_map(
                function (array $match) use ($context): array {
                    $orderIntent = $this->orderIntentFactory->forCandidate($context->object, $match['service']);

                    return [
                        'serviceId' => (string) $match['service']->getId(),
                        'vendorId' => $match['service']->getOwner(),
                        'amountMinor' => $match['service']->getAmountMinor(),
                        'currency' => $match['service']->getCurrency(),
                        'serviceAreaStatus' => $match['serviceAreaStatus'],
                        'distanceMeters' => $match['distanceMeters'],
                        'availabilityStatus' => $match['availabilityStatus'],
                        'budgetStatus' => $match['budgetStatus'],
                        'orderingStatus' => $orderIntent['status'],
                        'orderIntent' => $orderIntent['payload'],
                    ];
                },
                $this->candidateMatchService->matchForTask($context->object),
            );
        }
        $context->request->getSession()->set(self::SESSION_KEY, $placement);

        return CrudServiceResult::response(new RedirectResponse($this->urlGenerator->generate(
            'cruding_tokenized_catch_all',
            ['crudPath' => 'fulfillment/new'],
        )));
    }

    private function tenantId(CrudServiceContext $context): string
    {
        foreach (['tenantId', 'tenant_id', '_tenant_id'] as $attribute) {
            $value = $context->request->attributes->get($attribute);
            if (is_scalar($value) && '' !== trim((string) $value)) {
                return trim((string) $value);
            }
        }

        return 'default';
    }

    private function scalarString(string|int|null $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $value = trim((string) $value);

        return '' === $value ? null : $value;
    }
}
