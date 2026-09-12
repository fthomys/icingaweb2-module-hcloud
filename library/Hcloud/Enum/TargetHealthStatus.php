<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Enum;

enum TargetHealthStatus: string implements HasLabel
{
    case Healthy = 'healthy';
    case Unhealthy = 'unhealthy';
    case Unknown = 'unknown';

    public static function tryFromValue(?string $value): ?self
    {
        return $value === null ? null : self::tryFrom($value);
    }

    public function label(): string
    {
        return match ($this) {
            self::Healthy => mt('hcloud', 'Healthy'),
            self::Unhealthy => mt('hcloud', 'Unhealthy'),
            self::Unknown => mt('hcloud', 'Unknown'),
        };
    }

    public function cssClass(): string
    {
        return match ($this) {
            self::Healthy => 'state-ok',
            self::Unhealthy => 'state-critical',
            self::Unknown => 'state-unknown',
        };
    }
}
