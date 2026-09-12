<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Metric;

/**
 * Hetzner names its series like disk.0.iops.read. The name carries the unit and the disk or
 * interface index, neither of which the API states anywhere, so it is decoded here once.
 */
final class SeriesInfo
{
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly Unit $unit,
        public readonly string $group,
        public readonly int $order
    ) {
    }

    public static function forName(string $name): self
    {
        $parts = explode('.', $name);
        $index = null;

        foreach ($parts as $part) {
            if (ctype_digit($part)) {
                $index = (int) $part;
                break;
            }
        }

        $suffix = static function (string $label) use ($index): string {
            return $index === null ? $label : sprintf('%s %d', $label, $index);
        };

        return match (true) {
            $name === 'cpu' => new self($name, mt('hcloud', 'CPU'), Unit::Percent, 'cpu', 0),

            str_starts_with($name, 'disk.') && str_ends_with($name, 'iops.read') => new self(
                $name,
                sprintf(mt('hcloud', 'Disk %s read'), $suffix(mt('hcloud', 'IOPS'))),
                Unit::OperationsPerSecond,
                'disk-iops',
                10
            ),
            str_starts_with($name, 'disk.') && str_ends_with($name, 'iops.write') => new self(
                $name,
                sprintf(mt('hcloud', 'Disk %s write'), $suffix(mt('hcloud', 'IOPS'))),
                Unit::OperationsPerSecond,
                'disk-iops',
                11
            ),
            str_starts_with($name, 'disk.') && str_ends_with($name, 'bandwidth.read') => new self(
                $name,
                sprintf(mt('hcloud', 'Disk %s read'), $suffix(mt('hcloud', 'throughput'))),
                Unit::BytesPerSecond,
                'disk-bandwidth',
                20
            ),
            str_starts_with($name, 'disk.') && str_ends_with($name, 'bandwidth.write') => new self(
                $name,
                sprintf(mt('hcloud', 'Disk %s write'), $suffix(mt('hcloud', 'throughput'))),
                Unit::BytesPerSecond,
                'disk-bandwidth',
                21
            ),

            str_starts_with($name, 'network.') && str_ends_with($name, 'pps.in') => new self(
                $name,
                sprintf(mt('hcloud', 'Network %s in'), $suffix(mt('hcloud', 'packets'))),
                Unit::PacketsPerSecond,
                'network-pps',
                30
            ),
            str_starts_with($name, 'network.') && str_ends_with($name, 'pps.out') => new self(
                $name,
                sprintf(mt('hcloud', 'Network %s out'), $suffix(mt('hcloud', 'packets'))),
                Unit::PacketsPerSecond,
                'network-pps',
                31
            ),
            str_starts_with($name, 'network.') && str_ends_with($name, 'bandwidth.in') => new self(
                $name,
                sprintf(mt('hcloud', 'Network %s in'), $suffix(mt('hcloud', 'throughput'))),
                Unit::BytesPerSecond,
                'network-bandwidth',
                40
            ),
            str_starts_with($name, 'network.') && str_ends_with($name, 'bandwidth.out') => new self(
                $name,
                sprintf(mt('hcloud', 'Network %s out'), $suffix(mt('hcloud', 'throughput'))),
                Unit::BytesPerSecond,
                'network-bandwidth',
                41
            ),

            $name === 'open_connections' => new self(
                $name,
                mt('hcloud', 'Open connections'),
                Unit::Count,
                'connections',
                50
            ),
            $name === 'connections_per_second' => new self(
                $name,
                mt('hcloud', 'New connections'),
                Unit::ConnectionsPerSecond,
                'connections',
                51
            ),
            $name === 'requests_per_second' => new self(
                $name,
                mt('hcloud', 'Requests'),
                Unit::RequestsPerSecond,
                'requests',
                60
            ),
            str_starts_with($name, 'bandwidth.in') => new self(
                $name,
                mt('hcloud', 'Bandwidth in'),
                Unit::BytesPerSecond,
                'lb-bandwidth',
                70
            ),
            str_starts_with($name, 'bandwidth.out') => new self(
                $name,
                mt('hcloud', 'Bandwidth out'),
                Unit::BytesPerSecond,
                'lb-bandwidth',
                71
            ),

            default => new self($name, $name, Unit::Count, 'other', 900),
        };
    }
}
