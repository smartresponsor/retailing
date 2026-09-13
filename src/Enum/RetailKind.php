<?php

declare(strict_types=1);

namespace App\Retailing\Enum;

enum RetailKind: string
{
    case Task = 'task';
    case Service = 'service';
    case Goods = 'goods';
    case Project = 'project';

    public function label(): string
    {
        return match ($this) {
            self::Task => 'Task',
            self::Service => 'Service',
            self::Goods => 'Product',
            self::Project => 'Project',
        };
    }

    public function catalogCode(): string
    {
        return match ($this) {
            self::Task, self::Service => 'services',
            self::Goods => 'products',
            self::Project => 'projects',
        };
    }
}
