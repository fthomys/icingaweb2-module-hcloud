<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Enum;

enum ZoneStatus: string implements HasLabel
{
    case Ok = 'ok';
    case Updating = 'updating';
    case Error = 'error';

    public static function tryFromValue(?string $value): ?self
    {
        return $value === null ? null : self::tryFrom($value);
    }

    public function label(): string
    {
        return match ($this) {
            self::Ok => mt('hcloud', 'OK'),
            self::Updating => mt('hcloud', 'Updating'),
            self::Error => mt('hcloud', 'Error'),
        };
    }

    public function cssClass(): string
    {
        return match ($this) {
            self::Ok => 'state-ok',
            self::Updating => 'state-pending',
            self::Error => 'state-critical',
        };
    }
}
