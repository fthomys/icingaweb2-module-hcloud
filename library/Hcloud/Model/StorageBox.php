<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use Icinga\Module\Hcloud\Model\Behavior\Json;
use Icinga\Module\Hcloud\Model\Behavior\UtcDateTime;
use ipl\Orm\Behaviors;
use ipl\Orm\Relations;

class StorageBox extends HcloudModel
{
    public function getTableName(): string
    {
        return 'hcloud_storage_box';
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
            'username',
            'server',
            'system',
            'location_id',
            'storage_box_type_id',
            'stats_size',
            'stats_size_data',
            'stats_size_snapshots',
            'access_reachable_externally',
            'access_samba_enabled',
            'access_ssh_enabled',
            'access_webdav_enabled',
            'access_zfs_enabled',
            'snapshot_plan_max_snapshots',
            'snapshot_plan_minute',
            'snapshot_plan_hour',
            'snapshot_plan_day_of_week',
            'snapshot_plan_day_of_month',
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
            'username' => mt('hcloud', 'Username'),
            'server' => mt('hcloud', 'Server'),
            'system' => mt('hcloud', 'System'),
            'location_id' => mt('hcloud', 'Location ID'),
            'storage_box_type_id' => mt('hcloud', 'Storage Box Type ID'),
            'stats_size' => mt('hcloud', 'Stats Size'),
            'stats_size_data' => mt('hcloud', 'Stats Size Data'),
            'stats_size_snapshots' => mt('hcloud', 'Stats Size Snapshots'),
            'access_reachable_externally' => mt('hcloud', 'Access Reachable Externally'),
            'access_samba_enabled' => mt('hcloud', 'Access Samba Enabled'),
            'access_ssh_enabled' => mt('hcloud', 'Access SSH Enabled'),
            'access_webdav_enabled' => mt('hcloud', 'Access WebDAV Enabled'),
            'access_zfs_enabled' => mt('hcloud', 'Access ZFS Enabled'),
            'snapshot_plan_max_snapshots' => mt('hcloud', 'Snapshot Plan Max Snapshots'),
            'snapshot_plan_minute' => mt('hcloud', 'Snapshot Plan Minute'),
            'snapshot_plan_hour' => mt('hcloud', 'Snapshot Plan Hour'),
            'snapshot_plan_day_of_week' => mt('hcloud', 'Snapshot Plan Day Of Week'),
            'snapshot_plan_day_of_month' => mt('hcloud', 'Snapshot Plan Day Of Month'),
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
        return ['name', 'username'];
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

        $relations->belongsTo('storage_box_type', StorageBoxType::class)
            ->setCandidateKey(['project_id', 'storage_box_type_id'])
            ->setForeignKey(['project_id', 'id'])
            ->setJoinType('LEFT');

        $relations->hasMany('subaccount', StorageBoxSubaccount::class)
            ->setCandidateKey(['project_id', 'id'])
            ->setForeignKey(['project_id', 'storage_box_id'])
            ->setJoinType('LEFT');

        $relations->hasMany('snapshot', StorageBoxSnapshot::class)
            ->setCandidateKey(['project_id', 'id'])
            ->setForeignKey(['project_id', 'storage_box_id'])
            ->setJoinType('LEFT');
    }
}
