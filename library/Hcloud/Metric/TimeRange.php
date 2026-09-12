<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Metric;

enum TimeRange: string
{
    case LastHour = '1h';
    case LastSixHours = '6h';
    case LastDay = '24h';
    case LastTwoDays = '48h';
    case LastWeek = '7d';

    public const PARAM = 'range';

    public static function default(): self
    {
        return self::LastDay;
    }

    public static function tryFromValue(?string $value): ?self
    {
        return $value === null ? null : self::tryFrom($value);
    }

    public function hours(): int
    {
        return match ($this) {
            self::LastHour => 1,
            self::LastSixHours => 6,
            self::LastDay => 24,
            self::LastTwoDays => 48,
            self::LastWeek => 168,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::LastHour => mt('hcloud', '1 h'),
            self::LastSixHours => mt('hcloud', '6 h'),
            self::LastDay => mt('hcloud', '24 h'),
            self::LastTwoDays => mt('hcloud', '48 h'),
            self::LastWeek => mt('hcloud', '7 d'),
        };
    }
}
