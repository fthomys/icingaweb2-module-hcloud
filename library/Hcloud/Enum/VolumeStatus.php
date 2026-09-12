<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Enum;

enum VolumeStatus: string implements HasLabel
{
    case Available = 'available';
    case Creating = 'creating';

    public static function tryFromValue(?string $value): ?self
    {
        return $value === null ? null : self::tryFrom($value);
    }

    public function label(): string
    {
        return match ($this) {
            self::Available => mt('hcloud', 'Available'),
            self::Creating => mt('hcloud', 'Creating'),
        };
    }

    public function cssClass(): string
    {
        return match ($this) {
            self::Available => 'state-ok',
            self::Creating => 'state-pending',
        };
    }

    public function isHealthy(): bool
    {
        return $this === self::Available;
    }
}
