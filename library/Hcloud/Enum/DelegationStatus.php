<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Enum;

enum DelegationStatus: string implements HasLabel
{
    case Valid = 'valid';
    case PartiallyValid = 'partially-valid';
    case Invalid = 'invalid';
    case Lame = 'lame';
    case Unregistered = 'unregistered';
    case Unknown = 'unknown';

    public static function tryFromValue(?string $value): ?self
    {
        return $value === null ? null : self::tryFrom($value);
    }

    public function label(): string
    {
        return match ($this) {
            self::Valid => mt('hcloud', 'Valid'),
            self::PartiallyValid => mt('hcloud', 'Partially valid'),
            self::Invalid => mt('hcloud', 'Invalid'),
            self::Lame => mt('hcloud', 'Lame'),
            self::Unregistered => mt('hcloud', 'Unregistered'),
            self::Unknown => mt('hcloud', 'Unknown'),
        };
    }

    public function cssClass(): string
    {
        return match ($this) {
            self::Valid => 'state-ok',
            self::PartiallyValid => 'state-warning',
            self::Invalid, self::Lame => 'state-critical',
            self::Unregistered, self::Unknown => 'state-unknown',
        };
    }
}
