<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Enum;

enum StorageBoxStatus: string implements HasLabel
{
    case Active = 'active';
    case Initializing = 'initializing';
    case Locked = 'locked';

    public static function tryFromValue(?string $value): ?self
    {
        return $value === null ? null : self::tryFrom($value);
    }

    public function label(): string
    {
        return match ($this) {
            self::Active => mt('hcloud', 'Active'),
            self::Initializing => mt('hcloud', 'Initializing'),
            self::Locked => mt('hcloud', 'Locked'),
        };
    }

    public function cssClass(): string
    {
        return match ($this) {
            self::Active => 'state-ok',
            self::Initializing => 'state-pending',
            self::Locked => 'state-critical',
        };
    }
}
