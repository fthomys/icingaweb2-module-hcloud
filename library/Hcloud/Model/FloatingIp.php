<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use Icinga\Module\Hcloud\Model\Behavior\Json;
use Icinga\Module\Hcloud\Model\Behavior\UtcDateTime;
use ipl\Orm\Behaviors;
use ipl\Orm\Relations;

class FloatingIp extends HcloudModel
{
    public function getTableName(): string
    {
        return 'hcloud_floating_ip';
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'name',
            'description',
            'created',
            'ip',
            'type',
            'server_id',
            'blocked',
            'home_location_id',
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
            'description' => mt('hcloud', 'Description'),
            'created' => mt('hcloud', 'Created'),
            'ip' => mt('hcloud', 'IP'),
            'type' => mt('hcloud', 'Type'),
            'server_id' => mt('hcloud', 'Server ID'),
            'blocked' => mt('hcloud', 'Blocked'),
            'home_location_id' => mt('hcloud', 'Home Location ID'),
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
        return ['ip'];
    }

    /**
     * @return list<string>
     */
    public function getSearchColumns(): array
    {
        return ['name', 'ip'];
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('project', Project::class)
            ->setCandidateKey('project_id')
            ->setForeignKey('id');

        $relations->belongsTo('home_location', Location::class)
            ->setCandidateKey(['project_id', 'home_location_id'])
            ->setForeignKey(['project_id', 'id'])
            ->setJoinType('LEFT');

        $relations->belongsTo('server', Server::class)
            ->setCandidateKey(['project_id', 'server_id'])
            ->setForeignKey(['project_id', 'id'])
            ->setJoinType('LEFT');

        $relations->hasMany('dns_ptr', FloatingIpDnsPtr::class)
            ->setCandidateKey(['project_id', 'id'])
            ->setForeignKey(['project_id', 'floating_ip_id'])
            ->setJoinType('LEFT');
    }
}
