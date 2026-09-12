<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Metric;

/**
 * Drawing every sample onto a chart that is narrower than the sample count turns the line
 * into a band of noise. Averaging alone would fix the look but hide exactly the spikes worth
 * seeing, so each bucket keeps its minimum and maximum as well.
 */
final class Downsampler
{
    /**
     * Spread the samples over evenly spaced time buckets.
     *
     * Bucketing by index would place a dense hour and a sparse day side by side at the same
     * width, which distorts the time axis. Hetzner returns wildly different resolutions for
     * different windows, so the bucket has to be a slice of time, not a slice of the array.
     * A bucket with no sample in it stays null, so the chart can break the line instead of
     * drawing through a gap that never had data.
     *
     * @param list<array{ts: string, value: float}> $points Ordered by ts
     *
     * @return list<?array{ts: string, min: float, max: float, avg: float}>
     */
    public static function overTime(array $points, int $buckets, int $from, int $to): array
    {
        if ($buckets < 1) {
            $buckets = 1;
        }

        if ($to <= $from) {
            $to = $from + 1;
        }

        $width = ($to - $from) / $buckets;

        /** @var list<list<float>> $collected */
        $collected = array_fill(0, $buckets, []);

        foreach ($points as $point) {
            $stamp = strtotime($point['ts'] . ' UTC');
            if ($stamp === false || $stamp < $from || $stamp > $to) {
                continue;
            }

            $index = (int) floor(($stamp - $from) / $width);
            if ($index < 0) {
                $index = 0;
            } elseif ($index >= $buckets) {
                $index = $buckets - 1;
            }

            $collected[$index][] = $point['value'];
        }

        $out = [];

        foreach ($collected as $index => $values) {
            if ($values === []) {
                $out[] = null;
                continue;
            }

            $out[] = [
                'ts' => gmdate('Y-m-d H:i:s', (int) round($from + $width * ($index + 0.5))),
                'min' => (float) min($values),
                'max' => (float) max($values),
                'avg' => array_sum($values) / count($values),
            ];
        }

        return $out;
    }

    /**
     * @param list<array{ts: string, value: float}> $points
     *
     * @return list<array{ts: string, min: float, max: float, avg: float}>
     */
    public static function bucket(array $points, int $buckets): array
    {
        $count = count($points);

        if ($count === 0) {
            return [];
        }

        if ($buckets < 1) {
            $buckets = 1;
        }

        if ($count <= $buckets) {
            return array_map(
                static fn (array $point): array => [
                    'ts' => $point['ts'],
                    'min' => $point['value'],
                    'max' => $point['value'],
                    'avg' => $point['value'],
                ],
                $points
            );
        }

        $out = [];

        for ($i = 0; $i < $buckets; $i++) {
            $start = (int) floor($i * $count / $buckets);
            $end = (int) floor(($i + 1) * $count / $buckets);

            if ($end <= $start) {
                $end = $start + 1;
            }

            $slice = array_slice($points, $start, $end - $start);
            if ($slice === []) {
                continue;
            }

            $values = array_column($slice, 'value');

            $out[] = [
                'ts' => $slice[intdiv(count($slice), 2)]['ts'],
                'min' => (float) min($values),
                'max' => (float) max($values),
                'avg' => array_sum($values) / count($values),
            ];
        }

        return $out;
    }
}
