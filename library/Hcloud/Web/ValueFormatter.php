<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Web;

use DateTimeInterface;

final class ValueFormatter
{
    private const BYTE_UNITS = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB'];

    public static function bytes(int|float|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $bytes = (float) $value;
        if ($bytes <= 0.0) {
            return '0 B';
        }

        $unit = 0;
        while ($bytes >= 1024 && $unit < count(self::BYTE_UNITS) - 1) {
            $bytes /= 1024;
            $unit++;
        }

        return sprintf($unit === 0 ? '%d %s' : '%.1f %s', $bytes, self::BYTE_UNITS[$unit]);
    }

    public static function gigabytes(int|float|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $gb = (float) $value;

        return $gb >= 1024.0
            ? sprintf('%.1f TB', $gb / 1024)
            : sprintf($gb === floor($gb) ? '%d GB' : '%.1f GB', $gb);
    }

    public static function percent(int|float|null $part, int|float|null $total): ?float
    {
        if ($part === null || $total === null || (float) $total <= 0.0) {
            return null;
        }

        return round(((float) $part / (float) $total) * 100, 1);
    }

    public static function money(int|float|string|null $value, ?string $currency = null): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $formatted = number_format((float) $value, 2, '.', '');

        return $currency === null || $currency === '' ? $formatted : $formatted . ' ' . $currency;
    }

    public static function price(int|float|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $raw = is_string($value) ? trim($value) : sprintf('%.10F', (float) $value);
        if (! is_numeric($raw)) {
            return '-';
        }

        if (! str_contains($raw, '.')) {
            return $raw . '.00';
        }

        [$whole, $fraction] = explode('.', $raw, 2);
        $fraction = str_pad(substr(rtrim($fraction, '0'), 0, 6), 2, '0');

        return $whole . '.' . $fraction;
    }

    public static function percentage(int|float|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $trimmed = rtrim(rtrim(sprintf('%.2f', (float) $value), '0'), '.');

        return ($trimmed === '' ? '0' : $trimmed) . ' %';
    }

    public static function seconds(int|float|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $seconds = (int) round((float) $value);
        if ($seconds < 60) {
            return $seconds . ' s';
        }

        if ($seconds < 3600) {
            return self::compact($seconds / 60, 'min');
        }

        if ($seconds < 86400) {
            return self::compact($seconds / 3600, 'h');
        }

        return self::compact($seconds / 86400, 'd');
    }

    public static function milliseconds(int|float|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $ms = (float) $value;

        return $ms < 1000 ? sprintf('%d ms', (int) round($ms)) : self::compact($ms / 1000, 's');
    }

    private static function compact(float $value, string $unit): string
    {
        return sprintf($value === floor($value) ? '%d %s' : '%.1f %s', $value, $unit);
    }

    public static function date(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_string($value) && $value !== '') {
            return $value;
        }

        return '-';
    }

    public static function yesNo(mixed $value): string
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ? mt('hcloud', 'Yes')
            : mt('hcloud', 'No');
    }

    public static function text(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (is_bool($value)) {
            return self::yesNo($value);
        }

        if ($value instanceof DateTimeInterface) {
            return self::date($value);
        }

        return is_scalar($value) ? (string) $value : '-';
    }

    /**
     * @param array<string, string>|string|null $labels
     *
     * @return array<string, string>
     */
    public static function labels(array|string|null $labels): array
    {
        if ($labels === null) {
            return [];
        }

        if (is_string($labels)) {
            $decoded = json_decode($labels, true);
            $labels = is_array($decoded) ? $decoded : [];
        }

        $out = [];
        foreach ($labels as $key => $value) {
            if (is_scalar($value)) {
                $out[(string) $key] = (string) $value;
            }
        }

        return $out;
    }
}
