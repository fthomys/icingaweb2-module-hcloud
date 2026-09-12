<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use Icinga\Module\Hcloud\Model\Behavior\UtcDateTime;
use ipl\Orm\Behaviors;
use ipl\Orm\Relations;

class ObjectStorageBucket extends HcloudModel
{
    public function getTableName(): string
    {
        return 'hcloud_object_storage_bucket';
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'name',
            'location',
            'created',
            'object_count',
            'size',
            'usage_complete',
            'usage_scanned',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getColumnDefinitions(): array
    {
        return [
            'id' => mt('hcloud', 'ID'),
            'name' => mt('hcloud', 'Name'),
            'location' => mt('hcloud', 'Location'),
            'created' => mt('hcloud', 'Created'),
            'object_count' => mt('hcloud', 'Objects'),
            'size' => mt('hcloud', 'Size'),
            'usage_complete' => mt('hcloud', 'Size Complete'),
            'usage_scanned' => mt('hcloud', 'Size Measured'),
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new UtcDateTime(['created', 'usage_scanned']));
    }

    /**
     * @return list<string>
     */
    public function getDefaultSort(): array
    {
        return ['name'];
    }

    /**
     * @return list<string>
     */
    public function getSearchColumns(): array
    {
        return ['name', 'location'];
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('project', Project::class)
            ->setCandidateKey('project_id')
            ->setForeignKey('id');
    }
}
