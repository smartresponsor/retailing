<?php

declare(strict_types=1);

namespace App\Retailing\Service\Http\Retail;

use App\Cruding\Dto\Crud\Entrypoint\CrudServiceContext;
use App\Cruding\Dto\Crud\Entrypoint\CrudServiceResult;
use App\Cruding\Service\Crud\AbstractCrudService;
use App\Retailing\Entity\Retail\RetailEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class RetailNewService extends AbstractCrudService
{
    private const SESSION_KEY = 'retail_placement';

    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    protected function afterDefault(CrudServiceContext $context, CrudServiceResult $result): CrudServiceResult
    {
        if (!$context->isPost() || !$result->payload() instanceof RedirectResponse || !$context->object instanceof RetailEntity) {
            return $result;
        }

        if ($context->object->getId() <= 0 || !$context->request->hasSession()) {
            return $result;
        }

        $vendorId = $context->object->getOwner()
            ?? $this->scalarString($context->actorIdentityValue())
            ?? $this->scalarString($context->actorUserId());

        if (null === $vendorId) {
            return $result;
        }

        $placement = $context->request->getSession()->get(self::SESSION_KEY);
        if (!is_array($placement) || !isset($placement['orderId']) || !is_scalar($placement['orderId']) || '' === trim((string) $placement['orderId'])) {
            return $result;
        }

        $placement['retailId'] = (string) $context->object->getId();
        $placement['tenantId'] = $this->tenantId($context);
        $placement['vendorId'] = $vendorId;
        $placement['amountMinor'] = $context->object->getAmountMinor();
        $placement['currency'] = $context->object->getCurrency();
        $context->request->getSession()->set(self::SESSION_KEY, $placement);

        return CrudServiceResult::response(new RedirectResponse($this->urlGenerator->generate(
            'cruding_tokenized_catch_all',
            ['crudPath' => 'shipment/new'],
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
