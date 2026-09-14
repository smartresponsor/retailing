<?php

declare(strict_types=1);

namespace App\Retailing\Provider;

use App\Retailing\Entity\Retail\RetailEntity;
use App\Retailing\ValueObject\RetailViewPayload;
use App\Viewing\Value\View\ViewPayload;

final readonly class RetailViewProvider
{
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
