<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync;

use DateTimeImmutable;
use Icinga\Application\Logger;
use Icinga\Module\Hcloud\Api\Client;
use Icinga\Module\Hcloud\Api\Project;
use Icinga\Module\Hcloud\Api\ProjectRegistry;
use Icinga\Module\Hcloud\Db\Store;
use Icinga\Module\Hcloud\Model\Behavior\UtcDateTime;
use Icinga\Module\Hcloud\Sync\Mapper\ActionMapper;
use Icinga\Module\Hcloud\Sync\Mapper\Mapper;
use Icinga\Module\Hcloud\Sync\Mapper\LoadBalancerMapper;
use Icinga\Module\Hcloud\Sync\Mapper\ServerMapper;
use Icinga\Module\Hcloud\Sync\Mapper\StorageBoxMapper;
use Icinga\Module\Hcloud\Sync\Mapper\ZoneMapper;
use ipl\Sql\Select;
use Throwable;

final class Syncer
{
    public function __construct(
        private readonly Store $store,
        private readonly Project $project,
        private readonly Client $cloud,
        private readonly Client $storage,
        private readonly SyncOptions $options
    ) {
    }

    public function run(): SyncResult
    {
        $result = new SyncResult();
        if ($this->options->dryRun) {
            $projectId = $this->store->findProject($this->project) ?? 0;
            $syncRunId = null;
        } else {
            $projectId = $this->store->resolveProject($this->project);
            $syncRunId = $this->store->startSyncRun($projectId);
        }

        foreach (MapperRegistry::collections() as $mapper) {
            if (! $this->options->wants($mapper::resource())) {
                continue;
            }

            $sync = fn (): array => $this->syncCollection($mapper, $projectId);

            $this->runResource($result, $syncRunId, $projectId, $mapper::resource(), $sync);
        }

        $this->runPricing($result, $syncRunId, $projectId);
        $this->runPerParent($result, $syncRunId, $projectId);
        $this->runActions($result, $syncRunId, $projectId);
        $this->runObjectStorage($result, $syncRunId, $projectId);
        $this->runMetrics($result, $syncRunId, $projectId);
        $this->prune($projectId);

        $result->apiRequests = $this->cloud->getRequestCount() + $this->storage->getRequestCount();

        if ($syncRunId !== null) {
            $this->store->finishSyncRun(
                $syncRunId,
                $result->hasErrors() ? Store::STATUS_ERROR : Store::STATUS_SUCCESS,
                [
                    'resources_synced' => $result->resources,
                    'rows_written' => $result->written,
                    'rows_deleted' => $result->deleted,
                    'api_requests' => $result->apiRequests,
                ],
                $result->errorSummary()
            );
        }

        return $result;
    }

    /**
     * @param callable(): array{written: int, deleted: int, fetched: int} $work
     */
    private function runResource(
        SyncResult $result,
        ?int $syncRunId,
        int $projectId,
        string $resource,
        callable $work
    ): void {
        $startedAt = microtime(true);

        try {
            $counts = $work();
            $result->add($resource, $counts['written'], $counts['deleted']);

            if ($syncRunId !== null) {
                $this->store->recordResource(
                    $syncRunId,
                    $resource,
                    $counts['fetched'],
                    $counts['written'],
                    $counts['deleted'],
                    (int) round((microtime(true) - $startedAt) * 1000)
                );
            }
        } catch (Throwable $e) {
            Logger::error(
                'hcloud: syncing %s for project %s failed: %s',
                $resource,
                $this->project->key,
                $e->getMessage()
            );
            $result->fail($resource, $e->getMessage());

            if ($syncRunId !== null) {
                $this->store->recordResource(
                    $syncRunId,
                    $resource,
                    0,
                    0,
                    0,
                    (int) round((microtime(true) - $startedAt) * 1000),
                    $e->getMessage()
                );
            }
        }
    }

    /**
     * @param class-string<Mapper> $mapper
     *
     * @return array{written: int, deleted: int, fetched: int}
     */
    private function syncCollection(string $mapper, int $projectId): array
    {
        $client = $mapper::baseUri() === Client::STORAGE_BASE_URI ? $this->storage : $this->cloud;

        $rows = [];
        $children = array_fill_keys($mapper::childTables(), []);

        foreach ($client->getPaginated($mapper::path(), $mapper::key()) as $entry) {
            $payload = new Payload($entry);
            $rows[] = $mapper::row($payload);

            foreach ($mapper::children($payload) as $table => $childRows) {
                foreach ($childRows as $childRow) {
                    $children[$table][] = $childRow;
                }
            }
        }

        return $this->write($projectId, $mapper::table(), $rows, $children);
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @param array<string, list<array<string, mixed>>> $children
     *
     * @return array{written: int, deleted: int, fetched: int}
     */
    private function write(int $projectId, string $table, array $rows, array $children): array
    {
        $fetched = count($rows);

        if ($this->options->dryRun) {
            return ['written' => 0, 'deleted' => 0, 'fetched' => $fetched];
        }

        return $this->store->transaction(function () use ($projectId, $table, $rows, $children, $fetched): array {
            $counts = $this->store->replace($table, $projectId, $rows);
            $written = $counts['written'];
            $deleted = $counts['deleted'];

            foreach ($children as $childTable => $childRows) {
                $childCounts = $this->store->replace($childTable, $projectId, $childRows);
                $written += $childCounts['written'];
                $deleted += $childCounts['deleted'];
            }

            return ['written' => $written, 'deleted' => $deleted, 'fetched' => $fetched];
        });
    }

    private function runPricing(SyncResult $result, ?int $syncRunId, int $projectId): void
    {
        if (! $this->options->wants('pricing')) {
            return;
        }

        $sync = function () use ($projectId): array {
            $rows = PricingMapper::rows(
                $this->cloud->get(PricingMapper::PATH),
                new DateTimeImmutable('now')
            );

            $main = $rows['hcloud_pricing'];
            unset($rows['hcloud_pricing']);

            $counts = $this->write($projectId, 'hcloud_pricing', $main, $rows);
            $counts['fetched'] = array_sum(array_map('count', $rows)) + count($main);

            return $counts;
        };

        $this->runResource($result, $syncRunId, $projectId, 'pricing', $sync);
    }

    private function runPerParent(SyncResult $result, ?int $syncRunId, int $projectId): void
    {
        $zoneIds = $this->parentIds($projectId, ZoneMapper::table());
        $boxIds = $this->parentIds($projectId, StorageBoxMapper::table());

        foreach (MapperRegistry::perParent() as $parentTable => $mapper) {
            if (! $this->options->wants($mapper::resource())) {
                continue;
            }

            $ids = $parentTable === ZoneMapper::table() ? $zoneIds : $boxIds;

            $sync = function () use ($mapper, $projectId, $ids): array {
                $client = $mapper::baseUri() === Client::STORAGE_BASE_URI ? $this->storage : $this->cloud;

                $rows = [];
                $children = array_fill_keys($mapper::childTables(), []);

                foreach ($ids as $parentId) {
                    $path = str_replace('{id_or_name}', (string) $parentId, $mapper::path());
                    $path = str_replace('{id}', (string) $parentId, $path);

                    foreach ($client->getPaginated($path, $mapper::key()) as $entry) {
                        $payload = new Payload($entry);
                        $rows[] = $mapper::row($payload);

                        foreach ($mapper::children($payload) as $table => $childRows) {
                            foreach ($childRows as $childRow) {
                                $children[$table][] = $childRow;
                            }
                        }
                    }
                }

                return $this->write($projectId, $mapper::table(), $rows, $children);
            };

            $this->runResource($result, $syncRunId, $projectId, $mapper::resource(), $sync);
        }
    }

    private function runActions(SyncResult $result, ?int $syncRunId, int $projectId): void
    {
        if (! $this->options->wants(ActionMapper::resource())) {
            return;
        }

        $cutoff = (new DateTimeImmutable('now'))->modify(sprintf('-%d days', $this->options->actionRetentionDays));

        $sync = function () use ($projectId, $cutoff): array {
            $rows = [];
            $children = ['hcloud_action_resource' => []];
            $seen = [];

            $endpoints = array_merge(
                array_map(static fn (string $p): array => [$p, false], MapperRegistry::actionEndpoints()),
                array_map(static fn (string $p): array => [$p, true], MapperRegistry::storageActionEndpoints())
            );

            foreach ($endpoints as [$path, $isStorage]) {
                $client = $isStorage ? $this->storage : $this->cloud;

                foreach ($client->getPaginated($path, 'actions', ['sort' => 'started:desc']) as $entry) {
                    $payload = new Payload($entry);
                    $id = $payload->int('id');

                    if ($id === null || isset($seen[$id])) {
                        continue;
                    }

                    $started = $payload->time('started');
                    if ($started !== null && $started < UtcDateTime::format($cutoff)) {
                        break;
                    }

                    $seen[$id] = true;
                    $rows[] = ActionMapper::row($payload);

                    foreach (ActionMapper::children($payload)['hcloud_action_resource'] as $childRow) {
                        $children['hcloud_action_resource'][] = $childRow;
                    }
                }
            }

            return $this->write($projectId, ActionMapper::table(), $rows, $children);
        };

        $this->runResource($result, $syncRunId, $projectId, ActionMapper::resource(), $sync);
    }

    private function runObjectStorage(SyncResult $result, ?int $syncRunId, int $projectId): void
    {
        if (! $this->options->wants(ObjectStorageCollector::RESOURCE) || ! $this->project->hasS3Credentials()) {
            return;
        }

        $sync = function () use ($projectId): array {
            $collector = new ObjectStorageCollector(
                $this->store,
                ProjectRegistry::objectStorageClients($this->project),
                $this->options
            );

            return $collector->collect($projectId, $this->options->dryRun);
        };

        $this->runResource($result, $syncRunId, $projectId, ObjectStorageCollector::RESOURCE, $sync);
    }

    private function runMetrics(SyncResult $result, ?int $syncRunId, int $projectId): void
    {
        if (! $this->options->withMetrics || ! $this->options->wants('metrics')) {
            return;
        }

        $sync = function () use ($projectId): array {
            $collector = new MetricCollector($this->store, $this->cloud, $this->options);

            return $collector->collect($projectId, $this->options->dryRun);
        };

        $this->runResource($result, $syncRunId, $projectId, 'metrics', $sync);
    }

    private function prune(int $projectId): void
    {
        if ($this->options->dryRun) {
            return;
        }

        $now = new DateTimeImmutable('now');

        $this->store->pruneMetrics(
            $projectId,
            UtcDateTime::format($now->modify(sprintf('-%d hours', $this->options->metricRetentionHours)))
        );

        $this->store->pruneActions(
            $projectId,
            UtcDateTime::format($now->modify(sprintf('-%d days', $this->options->actionRetentionDays)))
        );

        $this->store->pruneOrphanedMetrics($projectId, [
            MetricCollector::RESOURCE_SERVER => ServerMapper::table(),
            MetricCollector::RESOURCE_LOAD_BALANCER => LoadBalancerMapper::table(),
        ]);
    }

    /**
     * @return list<int>
     */
    private function parentIds(int $projectId, string $table): array
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
