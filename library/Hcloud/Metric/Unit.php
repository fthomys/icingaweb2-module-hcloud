<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Metric;

enum Unit: string
{
    case Percent = 'percent';
    case BytesPerSecond = 'bytes_per_second';
    case PacketsPerSecond = 'packets_per_second';
    case OperationsPerSecond = 'operations_per_second';
    case RequestsPerSecond = 'requests_per_second';
    case ConnectionsPerSecond = 'connections_per_second';
    case Count = 'count';

    private const BINARY = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB'];

    private const DECIMAL = ['', 'k', 'M', 'G', 'T'];

    /**
     * A short, already scaled representation, for axis ticks and summaries.
     */
    public function format(float $value): string
    {
        return match ($this) {
            self::Percent => self::trim($value, 1) . ' %',
            self::BytesPerSecond => self::scale($value, self::BINARY, 1024, true) . '/s',
            self::PacketsPerSecond => self::scale($value, self::DECIMAL, 1000) . ' pps',
            self::OperationsPerSecond => self::scale($value, self::DECIMAL, 1000) . ' IOPS',
            self::RequestsPerSecond => self::scale($value, self::DECIMAL, 1000) . ' req/s',
            self::ConnectionsPerSecond => self::scale($value, self::DECIMAL, 1000) . ' conn/s',
            self::Count => self::scale($value, self::DECIMAL, 1000),
        };
    }

    /**
     * The axis ceiling for the given peak.
     *
     * Hetzner sums CPU across cores the way top does, so a four core server legitimately
     * reports up to 400 percent. Rounding up to the next full core keeps the axis readable
     * and stops a busy server from looking like it broke the scale.
     */
    public function axisMaximum(float $peak): float
    {
        if ($this !== self::Percent) {
            return $peak;
        }

        return max(100.0, ceil($peak / 100) * 100);
    }

    /**
     * @param list<string> $units
     */
    private static function scale(float $value, array $units, int $step, bool $spaced = false): string
    {
        $negative = $value < 0;
        $value = abs($value);
        $index = 0;

        while ($value >= $step && $index < count($units) - 1) {
            $value /= $step;
            $index++;
        }

        $text = self::trim($value, $index === 0 ? 0 : 1);
        $suffix = $units[$index];

        if ($suffix !== '') {
            $text .= ($spaced ? ' ' : '') . $suffix;
        }

        return ($negative ? '-' : '') . $text;
    }

    private static function trim(float $value, int $decimals): string
    {
        $text = number_format($value, $decimals, '.', '');

        if (! str_contains($text, '.')) {
            return $text;
        }

        return rtrim(rtrim($text, '0'), '.');
    }
}
