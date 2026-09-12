<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Enum;

enum PrimaryIpAssigneeType: string implements HasLabel
{
    case Server = 'server';
    case Unassigned = 'unassigned';

    public static function tryFromValue(?string $value): ?self
    {
        return $value === null ? null : self::tryFrom($value);
    }

    public function label(): string
    {
        return match ($this) {
            self::Server => mt('hcloud', 'Server'),
            self::Unassigned => mt('hcloud', 'Unassigned'),
        };
    }

    public function cssClass(): string
    {
        return match ($this) {
            self::Server => 'state-ok',
            self::Unassigned => 'state-unknown',
        };
    }
}
