<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Enum;

enum CertificateRenewalStatus: string implements HasLabel
{
    case Scheduled = 'scheduled';
    case Pending = 'pending';
    case Failed = 'failed';
    case Unavailable = 'unavailable';

    public static function tryFromValue(?string $value): ?self
    {
        return $value === null ? null : self::tryFrom($value);
    }

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => mt('hcloud', 'Scheduled'),
            self::Pending => mt('hcloud', 'Pending'),
            self::Failed => mt('hcloud', 'Failed'),
            self::Unavailable => mt('hcloud', 'Unavailable'),
        };
    }

    public function cssClass(): string
    {
        return match ($this) {
            self::Scheduled => 'state-ok',
            self::Pending => 'state-pending',
            self::Failed => 'state-critical',
            self::Unavailable => 'state-unknown',
        };
    }
}
