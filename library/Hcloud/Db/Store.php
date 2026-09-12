<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Db;

use DateTimeImmutable;
use Icinga\Module\Hcloud\Api\Project;
use Icinga\Module\Hcloud\Model\Behavior\UtcDateTime;
use ipl\Sql\Adapter\Pgsql;
use ipl\Sql\Connection;
use ipl\Sql\Select;
use Throwable;

final class Store
{
    public const STATUS_RUNNING = 'running';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_ERROR = 'error';

    public function __construct(private readonly Connection $db)
    {
    }

    public function db(): Connection
    {
        return $this->db;
    }

    /**
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     */
    public function transaction(callable $callback): mixed
    {
        $this->db->beginTransaction();

        try {
            $result = $callback();
            $this->db->commitTransaction();

            return $result;
        } catch (Throwable $e) {
            $this->db->rollBackTransaction();

            throw $e;
        }
    }

    /**
     * @param iterable<array<string, mixed>> $rows
     *
     * @return array{written: int, deleted: int}
     */
    public function replace(string $table, int $projectId, iterable $rows): array
    {
        $deleted = $this->db->delete($table, ['project_id = ?' => $projectId])->rowCount();

        $written = 0;
        foreach ($rows as $row) {
            $this->db->insert($table, $row + ['project_id' => $projectId]);
            $written++;
        }

        return ['written' => $written, 'deleted' => $deleted];
    }

    /**
     * @param array<string, mixed> $row
     */
    public function insertRow(string $table, array $row): void
    {
        $this->db->insert($table, $row);
    }

    public function findProject(Project $project): ?int
    {
        $select = (new Select())
            ->from('hcloud_project')
            ->columns(['id'])
            ->where(['config_key = ?' => $project->key]);

        $existing = $this->db->fetchScalar($select);

        return $existing === false || $existing === null ? null : (int) $existing;
    }

    public function resolveProject(Project $project): int
    {
        $select = (new Select())
            ->from('hcloud_project')
            ->columns(['id'])
            ->where(['config_key = ?' => $project->key]);

        $existing = $this->db->fetchScalar($select);

        if ($existing !== false && $existing !== null) {
            $id = (int) $existing;
            $this->db->update(
                'hcloud_project',
                ['name' => $project->name, 'enabled' => $project->enabled ? 1 : 0],
                ['id = ?' => $id]
            );

            return $id;
        }

        $this->db->insert('hcloud_project', [
            'config_key' => $project->key,
            'name' => $project->name,
            'enabled' => $project->enabled ? 1 : 0,
            'created' => UtcDateTime::format(new DateTimeImmutable('now')),
        ]);

        return $this->lastId('hcloud_project');
    }

    public function startSyncRun(int $projectId): int
    {
        $this->db->insert('hcloud_sync_run', [
            'project_id' => $projectId,
            'started' => UtcDateTime::format(new DateTimeImmutable('now')),
            'status' => self::STATUS_RUNNING,
        ]);

        return $this->lastId('hcloud_sync_run');
    }

    /**
     * @param array{resources_synced?: int, rows_written?: int, rows_deleted?: int, api_requests?: int} $counts
     */
    public function finishSyncRun(int $syncRunId, string $status, array $counts = [], ?string $error = null): void
    {
        $this->db->update('hcloud_sync_run', [
            'ended' => UtcDateTime::format(new DateTimeImmutable('now')),
            'status' => $status,
            'resources_synced' => $counts['resources_synced'] ?? 0,
            'rows_written' => $counts['rows_written'] ?? 0,
            'rows_deleted' => $counts['rows_deleted'] ?? 0,
            'api_requests' => $counts['api_requests'] ?? 0,
            'error' => $error,
        ], ['id = ?' => $syncRunId]);
    }

    public function recordResource(
        int $syncRunId,
        string $resource,
        int $fetched,
        int $written,
        int $deleted,
        int $durationMs,
        ?string $error = null
    ): void {
        $this->db->insert('hcloud_sync_run_resource', [
            'sync_run_id' => $syncRunId,
            'resource' => $resource,
            'fetched' => $fetched,
            'written' => $written,
            'deleted' => $deleted,
            'duration_ms' => $durationMs,
            'error' => $error,
        ]);
    }

    /**
     * @param iterable<array{ts: string, value: float|int|string|null}> $samples
     */
    public function replaceMetricSeries(
        int $projectId,
        string $resourceType,
        int $resourceId,
        string $series,
        string $from,
        iterable $samples
    ): int {
        $rows = is_array($samples) ? $samples : iterator_to_array($samples);

        // An empty answer must never clear what is already stored. The API returning nothing
        // for a window says nothing about whether that window was measured.
        if ($rows === []) {
            return 0;
        }

        $this->db->delete('hcloud_metric_sample', [
            'project_id = ?' => $projectId,
            'resource_type = ?' => $resourceType,
            'resource_id = ?' => $resourceId,
            'series_name = ?' => $series,
            'ts >= ?' => $from,
        ]);

        $written = 0;
        foreach ($rows as $sample) {
            $this->db->insert('hcloud_metric_sample', [
                'project_id' => $projectId,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'series_name' => $series,
                'ts' => $sample['ts'],
                'value' => $sample['value'],
            ]);
            $written++;
        }

        return $written;
    }

    public function pruneMetrics(int $projectId, string $before): int
    {
        return $this->db->delete('hcloud_metric_sample', [
            'project_id = ?' => $projectId,
            'ts < ?' => $before,
        ])->rowCount();
    }

    /**
     * Drop samples belonging to resources that no longer exist.
     *
     * hcloud_metric_sample keys on the resource id but cannot reference the resource table,
     * because one table holds samples for several kinds. A destroyed server would therefore
     * leave its samples behind until retention happened to reach them.
     *
     * @param array<string, string> $tables Resource type to the table holding those resources
     */
    public function pruneOrphanedMetrics(int $projectId, array $tables): int
    {
        $removed = 0;

        foreach ($tables as $resourceType => $table) {
            $known = [];
            $select = (new Select())->from($table)->columns(['id'])->where(['project_id = ?' => $projectId]);

            foreach ($this->db->fetchCol($select) as $id) {
                $known[] = (int) $id;
            }

            $condition = [
                'project_id = ?' => $projectId,
                'resource_type = ?' => $resourceType,
            ];

            if ($known !== []) {
                $condition['resource_id NOT IN (?)'] = $known;
            }

            $removed += $this->db->delete('hcloud_metric_sample', $condition)->rowCount();
        }

        return $removed;
    }

    public function pruneActions(int $projectId, string $before): int
    {
        return $this->db->delete('hcloud_action', [
            'project_id = ?' => $projectId,
            'started < ?' => $before,
        ])->rowCount();
    }

    private function lastId(string $table): int
    {
        $name = $this->db->getAdapter() instanceof Pgsql ? $table . '_id_seq' : null;

        return (int) $this->db->lastInsertId($name);
    }
}
