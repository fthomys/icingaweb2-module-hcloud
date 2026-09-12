<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use Icinga\Module\Hcloud\Model\Behavior\Json;
use Icinga\Module\Hcloud\Model\Behavior\UtcDateTime;
use ipl\Orm\Behaviors;
use ipl\Orm\Relations;

class PrimaryIp extends HcloudModel
{
    public function getTableName(): string
    {
        return 'hcloud_primary_ip';
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'name',
            'created',
            'ip',
            'type',
            'blocked',
            'auto_delete',
            'assignee_type',
            'assignee_id',
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
            'ip' => mt('hcloud', 'IP'),
            'type' => mt('hcloud', 'Type'),
            'blocked' => mt('hcloud', 'Blocked'),
            'auto_delete' => mt('hcloud', 'Auto Delete'),
            'assignee_type' => mt('hcloud', 'Assignee Type'),
            'assignee_id' => mt('hcloud', 'Assignee ID'),
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

        $relations->belongsTo('location', Location::class)
            ->setCandidateKey(['project_id', 'location_id'])
            ->setForeignKey(['project_id', 'id'])
            ->setJoinType('LEFT');

        $relations->hasMany('dns_ptr', PrimaryIpDnsPtr::class)
            ->setCandidateKey(['project_id', 'id'])
            ->setForeignKey(['project_id', 'primary_ip_id'])
            ->setJoinType('LEFT');
    }
}
