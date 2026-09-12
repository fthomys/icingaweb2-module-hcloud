<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync;

use DateTimeImmutable;
use DateTimeZone;
use Icinga\Module\Hcloud\Api\Client;
use Icinga\Module\Hcloud\Db\Store;
use Icinga\Module\Hcloud\Model\Behavior\UtcDateTime;
use ipl\Sql\Select;

final class MetricCollector
{
    public const RESOURCE_SERVER = 'server';
    public const RESOURCE_LOAD_BALANCER = 'load_balancer';

    private const SERVER_TYPES = ['cpu', 'disk', 'network'];

    private const LOAD_BALANCER_TYPES = [
        'open_connections',
        'connections_per_second',
        'requests_per_second',
        'bandwidth',
    ];

    public function __construct(
        private readonly Store $store,
        private readonly Client $client,
        private readonly SyncOptions $options
    ) {
    }

    /**
     * @return array{written: int, deleted: int, fetched: int}
     */
    public function collect(int $projectId, bool $dryRun): array
    {
        $written = 0;
        $fetched = 0;

        $subjects = [
            [self::RESOURCE_SERVER, 'hcloud_server', '/servers/%d/metrics', self::SERVER_TYPES],
            [
                self::RESOURCE_LOAD_BALANCER,
                'hcloud_load_balancer',
                '/load_balancers/%d/metrics',
                self::LOAD_BALANCER_TYPES,
            ],
        ];

        foreach ($subjects as [$resourceType, $table, $pathTemplate, $types]) {
            foreach ($this->resourceIds($projectId, $table) as $resourceId) {
                $start = $this->startFor($projectId, $resourceType, $resourceId);
                $end = new DateTimeImmutable('now', new DateTimeZone('UTC'));

                $payload = $this->client->get(sprintf($pathTemplate, $resourceId), [
                    'type' => $types,
                    'start' => $start->format(DATE_RFC3339),
                    'end' => $end->format(DATE_RFC3339),
                    'step' => $this->options->metricStep,
                ]);

                $series = $payload['metrics']['time_series'] ?? null;
                if (! is_array($series)) {
                    continue;
                }

                foreach ($series as $name => $definition) {
                    if (! is_string($name) || ! is_array($definition)) {
                        continue;
                    }

                    $samples = self::samples($definition);
                    $fetched += count($samples);

                    if ($dryRun) {
                        continue;
                    }

                    $written += $this->store->replaceMetricSeries(
                        $projectId,
                        $resourceType,
                        $resourceId,
                        $name,
                        UtcDateTime::format($start),
                        $samples
                    );
                }
            }
        }

        return ['written' => $written, 'deleted' => 0, 'fetched' => $fetched];
    }

    /**
     * @param array<string, mixed> $definition
     *
     * @return list<array{ts: string, value: string|null}>
     */
    private static function samples(array $definition): array
    {
        $values = $definition['values'] ?? null;
        if (! is_array($values)) {
            return [];
        }

        $samples = [];
        foreach ($values as $pair) {
            if (! is_array($pair) || ! isset($pair[0], $pair[1])) {
                continue;
            }

            if (! is_numeric($pair[0])) {
                continue;
            }

            $timestamp = (new DateTimeImmutable('@' . (int) $pair[0]))->setTimezone(new DateTimeZone('UTC'));

            $samples[] = [
                'ts' => UtcDateTime::format($timestamp),
                'value' => is_numeric($pair[1]) ? (string) $pair[1] : null,
            ];
        }

        return $samples;
    }

    private function startFor(int $projectId, string $resourceType, int $resourceId): DateTimeImmutable
    {
        $fallback = (new DateTimeImmutable('now', new DateTimeZone('UTC')))
            ->modify(sprintf('-%d hours', $this->options->metricRetentionHours));

        $select = (new Select())
            ->from('hcloud_metric_sample')
            ->columns(['newest' => 'MAX(ts)'])
            ->where([
                'project_id = ?' => $projectId,
                'resource_type = ?' => $resourceType,
                'resource_id = ?' => $resourceId,
            ]);

        $newest = $this->store->db()->fetchScalar($select);

        if (! is_string($newest) || $newest === '') {
            return $fallback;
        }

        $parsed = DateTimeImmutable::createFromFormat(
            UtcDateTime::FORMAT,
            $newest,
            new DateTimeZone('UTC')
        );

        if ($parsed === false) {
            return $fallback;
        }

        return $parsed > $fallback ? $parsed : $fallback;
    }

    /**
     * @return list<int>
     */
    private function resourceIds(int $projectId, string $table): array
    {
        $select = (new Select())
            ->from($table)
            ->columns(['id'])
            ->where(['project_id = ?' => $projectId]);

        $ids = [];
        foreach ($this->store->db()->fetchCol($select) as $id) {
            $ids[] = (int) $id;
        }

        return $ids;
    }
}
