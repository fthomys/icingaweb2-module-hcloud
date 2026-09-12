<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Enum;

enum ImageType: string implements HasLabel
{
    case System = 'system';
    case App = 'app';
    case Snapshot = 'snapshot';
    case Backup = 'backup';

    public static function tryFromValue(?string $value): ?self
    {
        return $value === null ? null : self::tryFrom($value);
    }

    public function label(): string
    {
        return match ($this) {
            self::System => mt('hcloud', 'System'),
            self::App => mt('hcloud', 'App'),
            self::Snapshot => mt('hcloud', 'Snapshot'),
            self::Backup => mt('hcloud', 'Backup'),
        };
    }

    public function cssClass(): string
    {
        return 'image-type-' . $this->value;
    }
}
