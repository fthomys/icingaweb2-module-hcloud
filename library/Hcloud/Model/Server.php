<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use Icinga\Module\Hcloud\Model\Behavior\Json;
use Icinga\Module\Hcloud\Model\Behavior\UtcDateTime;
use ipl\Orm\Behaviors;
use ipl\Orm\Relations;

class Server extends HcloudModel
{
    public function getTableName(): string
    {
        return 'hcloud_server';
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'name',
            'status',
            'created',
            'locked',
            'rescue_enabled',
            'backup_window',
            'outgoing_traffic',
            'ingoing_traffic',
            'included_traffic',
            'primary_disk_size',
            'server_type_id',
            'location_id',
            'image_id',
            'iso_id',
            'placement_group_id',
            'protection_delete',
            'protection_rebuild',
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
            'status' => mt('hcloud', 'Status'),
            'created' => mt('hcloud', 'Created'),
            'locked' => mt('hcloud', 'Locked'),
            'rescue_enabled' => mt('hcloud', 'Rescue Enabled'),
            'backup_window' => mt('hcloud', 'Backup Window'),
            'outgoing_traffic' => mt('hcloud', 'Outgoing Traffic'),
            'ingoing_traffic' => mt('hcloud', 'Ingoing Traffic'),
            'included_traffic' => mt('hcloud', 'Included Traffic'),
            'primary_disk_size' => mt('hcloud', 'Primary Disk Size'),
            'server_type_id' => mt('hcloud', 'Server Type ID'),
            'location_id' => mt('hcloud', 'Location ID'),
            'image_id' => mt('hcloud', 'Image ID'),
            'iso_id' => mt('hcloud', 'ISO ID'),
            'placement_group_id' => mt('hcloud', 'Placement Group ID'),
            'protection_delete' => mt('hcloud', 'Protection Delete'),
            'protection_rebuild' => mt('hcloud', 'Protection Rebuild'),
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

        $relations->belongsTo('server_type', ServerType::class)
            ->setCandidateKey(['project_id', 'server_type_id'])
            ->setForeignKey(['project_id', 'id'])
            ->setJoinType('LEFT');

        $relations->hasMany('public_ip', ServerPublicIp::class)
            ->setCandidateKey(['project_id', 'id'])
            ->setForeignKey(['project_id', 'server_id'])
            ->setJoinType('LEFT');

        $relations->hasMany('server_firewall', ServerFirewall::class)
            ->setCandidateKey(['project_id', 'id'])
            ->setForeignKey(['project_id', 'server_id'])
            ->setJoinType('LEFT');

        $relations->hasMany('private_net', ServerPrivateNet::class)
            ->setCandidateKey(['project_id', 'id'])
            ->setForeignKey(['project_id', 'server_id'])
            ->setJoinType('LEFT');

        $relations->hasMany('volume', Volume::class)
            ->setCandidateKey(['project_id', 'id'])
            ->setForeignKey(['project_id', 'server_id'])
            ->setJoinType('LEFT');
    }
}
