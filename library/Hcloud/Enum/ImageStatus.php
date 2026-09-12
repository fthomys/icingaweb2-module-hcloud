<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Enum;

enum ImageStatus: string implements HasLabel
{
    case Available = 'available';
    case Creating = 'creating';
    case Unavailable = 'unavailable';

    public static function tryFromValue(?string $value): ?self
    {
        return $value === null ? null : self::tryFrom($value);
    }

    public function label(): string
    {
        return match ($this) {
            self::Available => mt('hcloud', 'Available'),
            self::Creating => mt('hcloud', 'Creating'),
            self::Unavailable => mt('hcloud', 'Unavailable'),
        };
    }

    public function cssClass(): string
    {
        return match ($this) {
            self::Available => 'state-ok',
            self::Creating => 'state-pending',
            self::Unavailable => 'state-critical',
        };
    }
}
