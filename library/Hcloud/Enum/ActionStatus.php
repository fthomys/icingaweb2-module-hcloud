<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Enum;

enum ActionStatus: string implements HasLabel
{
    case Running = 'running';
    case Success = 'success';
    case Error = 'error';

    public static function tryFromValue(?string $value): ?self
    {
        return $value === null ? null : self::tryFrom($value);
    }

    public function label(): string
    {
        return match ($this) {
            self::Running => mt('hcloud', 'Running'),
            self::Success => mt('hcloud', 'Success'),
            self::Error => mt('hcloud', 'Error'),
        };
    }

    public function cssClass(): string
    {
        return match ($this) {
            self::Running => 'state-pending',
            self::Success => 'state-ok',
            self::Error => 'state-critical',
        };
    }
}
