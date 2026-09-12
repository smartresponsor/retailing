<?php

declare(strict_types=1);

namespace App\Retailing\Tests\Unit\Enum\Retail;

use App\Retailing\Enum\Retail\RetailKind;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RetailKindTest extends TestCase
{
    /** @return iterable<string, array{RetailKind, string, string}> */
    public static function kindProvider(): iterable
    {
        yield 'task' => [RetailKind::Task, 'Task', 'services'];
        yield 'service' => [RetailKind::Service, 'Service', 'services'];
        yield 'goods' => [RetailKind::Goods, 'Product', 'products'];
        yield 'project' => [RetailKind::Project, 'Project', 'projects'];
    }

    #[DataProvider('kindProvider')]
    public function testLabelAndCatalogCode(RetailKind $kind, string $label, string $catalogCode): void
    {
        self::assertSame($label, $kind->label());
        self::assertSame($catalogCode, $kind->catalogCode());
    }
}
