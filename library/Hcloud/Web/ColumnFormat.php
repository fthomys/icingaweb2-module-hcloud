<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Web;

enum ColumnFormat
{
    case Text;
    case Bytes;
    case Gigabytes;
    case YesNo;
    case Price;
    case Percent;
    case Seconds;
    case Milliseconds;
    case Labels;
    case Reference;

    public function apply(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (! is_scalar($value)) {
            return ValueFormatter::text($value);
        }

        if ($this === self::YesNo) {
            return ValueFormatter::yesNo($value);
        }

        if (is_bool($value)) {
            return ValueFormatter::text($value);
        }

        return match ($this) {
            self::Bytes => ValueFormatter::bytes($value),
            self::Gigabytes => ValueFormatter::gigabytes($value),
            self::Price => ValueFormatter::price($value),
            self::Percent => ValueFormatter::percentage($value),
            self::Seconds => ValueFormatter::seconds($value),
            self::Milliseconds => ValueFormatter::milliseconds($value),
            default => ValueFormatter::text($value),
        };
    }
}
