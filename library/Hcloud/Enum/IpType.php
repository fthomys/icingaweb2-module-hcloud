<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Enum;

enum IpType: string implements HasLabel
{
    case Ipv4 = 'ipv4';
    case Ipv6 = 'ipv6';

    public static function tryFromValue(?string $value): ?self
    {
        return $value === null ? null : self::tryFrom($value);
    }

    public function label(): string
    {
        return match ($this) {
            self::Ipv4 => 'IPv4',
            self::Ipv6 => 'IPv6',
        };
    }

    public function cssClass(): string
    {
        return 'ip-type-' . $this->value;
    }
}
