<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use Icinga\Module\Hcloud\Model\Behavior\UtcDateTime;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;
use ipl\Orm\Relations;

class SyncRun extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_sync_run';
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
            'project_id',
            'started',
            'ended',
            'status',
            'resources_synced',
            'rows_written',
            'rows_deleted',
            'api_requests',
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
            'project_id' => mt('hcloud', 'Project ID'),
            'started' => mt('hcloud', 'Started'),
            'ended' => mt('hcloud', 'Ended'),
            'status' => mt('hcloud', 'Status'),
            'resources_synced' => mt('hcloud', 'Resources Synced'),
            'rows_written' => mt('hcloud', 'Rows Written'),
            'rows_deleted' => mt('hcloud', 'Rows Deleted'),
            'api_requests' => mt('hcloud', 'Api Requests'),
            'error' => mt('hcloud', 'Error'),
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new UtcDateTime(['started', 'ended']));
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('project', Project::class)
            ->setCandidateKey('project_id')
            ->setForeignKey('id');
    }
}
