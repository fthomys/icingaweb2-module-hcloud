<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use Icinga\Module\Hcloud\Model\Behavior\Json;
use Icinga\Module\Hcloud\Model\Behavior\UtcDateTime;
use ipl\Orm\Behaviors;
use ipl\Orm\Relations;

class Volume extends HcloudModel
{
    public function getTableName(): string
    {
        return 'hcloud_volume';
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'name',
            'created',
            'status',
            'server_id',
            'linux_device',
            'size',
            'format',
            'location_id',
            'protection_delete',
            'labels',
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
            'created' => mt('hcloud', 'Created'),
            'status' => mt('hcloud', 'Status'),
            'server_id' => mt('hcloud', 'Server ID'),
            'linux_device' => mt('hcloud', 'Linux Device'),
            'size' => mt('hcloud', 'Size'),
            'format' => mt('hcloud', 'Format'),
            'location_id' => mt('hcloud', 'Location ID'),
            'protection_delete' => mt('hcloud', 'Protection Delete'),
            'labels' => mt('hcloud', 'Labels'),
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new UtcDateTime(['created']));
        $behaviors->add(new Json(['labels']));
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
        return ['name', 'status'];
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('project', Project::class)
            ->setCandidateKey('project_id')
            ->setForeignKey('id');

        $relations->belongsTo('location', Location::class)
            ->setCandidateKey(['project_id', 'location_id'])
            ->setForeignKey(['project_id', 'id'])
            ->setJoinType('LEFT');

        $relations->belongsTo('server', Server::class)
            ->setCandidateKey(['project_id', 'server_id'])
            ->setForeignKey(['project_id', 'id'])
            ->setJoinType('LEFT');
    }
}
