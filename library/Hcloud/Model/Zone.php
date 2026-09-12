<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use Icinga\Module\Hcloud\Model\Behavior\Json;
use Icinga\Module\Hcloud\Model\Behavior\UtcDateTime;
use ipl\Orm\Behaviors;
use ipl\Orm\Relations;

class Zone extends HcloudModel
{
    public function getTableName(): string
    {
        return 'hcloud_zone';
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'name',
            'created',
            'mode',
            'ttl',
            'status',
            'record_count',
            'registrar',
            'delegation_last_check',
            'delegation_status',
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
            'mode' => mt('hcloud', 'Mode'),
            'ttl' => mt('hcloud', 'TTL'),
            'status' => mt('hcloud', 'Status'),
            'record_count' => mt('hcloud', 'Record Count'),
            'registrar' => mt('hcloud', 'Registrar'),
            'delegation_last_check' => mt('hcloud', 'Delegation Last Check'),
            'delegation_status' => mt('hcloud', 'Delegation Status'),
            'protection_delete' => mt('hcloud', 'Protection Delete'),
            'labels' => mt('hcloud', 'Labels'),
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new UtcDateTime(['created', 'delegation_last_check']));
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
        return ['name'];
    }

    public function createRelations(Relations $relations): void
    {
        $relations->belongsTo('project', Project::class)
            ->setCandidateKey('project_id')
            ->setForeignKey('id');

        $relations->hasMany('nameserver', ZoneNameserver::class)
            ->setCandidateKey(['project_id', 'id'])
            ->setForeignKey(['project_id', 'zone_id'])
            ->setJoinType('LEFT');

        $relations->hasMany('rrset', ZoneRrset::class)
            ->setCandidateKey(['project_id', 'id'])
            ->setForeignKey(['project_id', 'zone_id'])
            ->setJoinType('LEFT');
    }
}
