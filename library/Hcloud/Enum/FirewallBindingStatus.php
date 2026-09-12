<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Enum;

enum FirewallBindingStatus: string implements HasLabel
{
    case Applied = 'applied';
    case Pending = 'pending';

    public static function tryFromValue(?string $value): ?self
    {
        return $value === null ? null : self::tryFrom($value);
    }

    public function label(): string
    {
        return match ($this) {
            self::Applied => mt('hcloud', 'Applied'),
            self::Pending => mt('hcloud', 'Pending'),
        };
    }

    public function cssClass(): string
    {
        return match ($this) {
            self::Applied => 'state-ok',
            self::Pending => 'state-warning',
        };
    }
}
