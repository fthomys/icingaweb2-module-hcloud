<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use Icinga\Module\Hcloud\Model\Behavior\Json;
use Icinga\Module\Hcloud\Model\Behavior\UtcDateTime;
use ipl\Orm\Behaviors;
use ipl\Orm\Relations;

class Network extends HcloudModel
{
    public function getTableName(): string
    {
        return 'hcloud_network';
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'name',
            'ip_range',
            'created',
            'expose_routes_to_vswitch',
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
            'ip_range' => mt('hcloud', 'IP Range'),
            'created' => mt('hcloud', 'Created'),
            'expose_routes_to_vswitch' => mt('hcloud', 'Expose Routes To vSwitch'),
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
        return ['name', 'ip_range'];
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('project', Project::class)
            ->setCandidateKey('project_id')
            ->setForeignKey('id');

        $relations->hasMany('subnet', NetworkSubnet::class)
            ->setCandidateKey(['project_id', 'id'])
            ->setForeignKey(['project_id', 'network_id'])
            ->setJoinType('LEFT');

        $relations->hasMany('route', NetworkRoute::class)
            ->setCandidateKey(['project_id', 'id'])
            ->setForeignKey(['project_id', 'network_id'])
            ->setJoinType('LEFT');
    }
}
