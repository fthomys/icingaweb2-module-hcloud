<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use ipl\Orm\Model;

class SyncRunResource extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_sync_run_resource';
    }

    public function getKeyName(): string
    {
        return 'id';
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'sync_run_id',
            'resource',
            'fetched',
            'written',
            'deleted',
            'duration_ms',
            'error',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getColumnDefinitions(): array
    {
        return [
            'id' => mt('hcloud', 'ID'),
            'sync_run_id' => mt('hcloud', 'Sync Run ID'),
            'resource' => mt('hcloud', 'Resource'),
            'fetched' => mt('hcloud', 'Fetched'),
            'written' => mt('hcloud', 'Written'),
            'deleted' => mt('hcloud', 'Deleted'),
            'duration_ms' => mt('hcloud', 'Duration ms'),
            'error' => mt('hcloud', 'Error'),
        ];
    }
}
