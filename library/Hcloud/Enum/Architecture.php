<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Enum;

enum Architecture: string implements HasLabel
{
    case X86 = 'x86';
    case Arm = 'arm';

    public static function tryFromValue(?string $value): ?self
    {
        return $value === null ? null : self::tryFrom($value);
    }

    public function label(): string
    {
        return match ($this) {
            self::X86 => 'x86',
            self::Arm => 'Arm',
        };
    }

    public function cssClass(): string
    {
        return 'architecture-' . $this->value;
    }
}
