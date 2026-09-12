<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Enum;

enum ServerStatus: string implements HasLabel
{
    case Running = 'running';
    case Initializing = 'initializing';
    case Starting = 'starting';
    case Stopping = 'stopping';
    case Off = 'off';
    case Deleting = 'deleting';
    case Migrating = 'migrating';
    case Rebuilding = 'rebuilding';
    case Unknown = 'unknown';

    public static function tryFromValue(?string $value): ?self
    {
        return $value === null ? null : self::tryFrom($value);
    }

    public function label(): string
    {
        return match ($this) {
            self::Running => mt('hcloud', 'Running'),
            self::Initializing => mt('hcloud', 'Initializing'),
            self::Starting => mt('hcloud', 'Starting'),
            self::Stopping => mt('hcloud', 'Stopping'),
            self::Off => mt('hcloud', 'Off'),
            self::Deleting => mt('hcloud', 'Deleting'),
            self::Migrating => mt('hcloud', 'Migrating'),
            self::Rebuilding => mt('hcloud', 'Rebuilding'),
            self::Unknown => mt('hcloud', 'Unknown'),
        };
    }

    public function cssClass(): string
    {
        return match ($this) {
            self::Running => 'state-ok',
            self::Initializing, self::Starting, self::Migrating, self::Rebuilding => 'state-pending',
            self::Stopping, self::Deleting => 'state-warning',
            self::Off => 'state-critical',
            self::Unknown => 'state-unknown',
        };
    }

    public function isHealthy(): bool
    {
        return $this === self::Running;
    }
}
