<?php

declare(strict_types=1);

namespace App\Retailing\Provider;

use App\Retailing\Entity\Retail\RetailEntity;
use App\Retailing\ValueObject\RetailViewPayload;
use App\Viewing\Value\View\ViewPayload;

/**
 * Adapts RetailEntity into the neutral Viewing payload consumed by the platform presentation boundary.
 */
final readonly class RetailViewProvider
{
    /**
     * Produces a Retailing-scoped view payload without rendering templates inside this component.
     */
    public function provide(RetailEntity $retail): ViewPayload
    {
        $payload = new RetailViewPayload($retail);

        return new ViewPayload(
            surface: 'retail',
            operation: 'view',
            intent: $retail->getKind()->value,
            component: 'Retailing',
            data: [
                'retail' => $payload,
            ],
        );
    }
}
