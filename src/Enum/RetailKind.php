<?php

declare(strict_types=1);

namespace App\Retailing\Enum;

/**
 * Defines supported retail listing kinds and their canonical catalog ownership.
 */
enum RetailKind: string
{
    case Task = 'task';
    case Service = 'service';
    case Goods = 'goods';
    case Project = 'project';

    /**
     * Returns the human-facing label used by forms and presentation payloads.
     */
    public function label(): string
    {
        return match ($this) {
            self::Task => 'Task',
            self::Service => 'Service',
            self::Goods => 'Product',
            self::Project => 'Project',
        };
    }

    /**
     * Maps the listing kind to the catalog code that owns its category vocabulary.
     */
    public function catalogCode(): string
    {
        return match ($this) {
            self::Task, self::Service => 'services',
            self::Goods => 'products',
            self::Project => 'projects',
        };
    }
}
