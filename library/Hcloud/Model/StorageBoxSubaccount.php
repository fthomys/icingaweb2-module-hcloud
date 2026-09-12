<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use Icinga\Module\Hcloud\Model\Behavior\Json;
use Icinga\Module\Hcloud\Model\Behavior\UtcDateTime;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;

class StorageBoxSubaccount extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_storage_box_subaccount';
    }

    /**
     * @return list<string>
     */
    public function getKeyName(): array
    {
        return [
            'project_id',
            'storage_box_id',
            'id',
        ];
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'name',
            'username',
            'home_directory',
            'description',
            'server',
            'created',
            'access_reachable_externally',
            'access_samba_enabled',
            'access_ssh_enabled',
            'access_webdav_enabled',
            'access_readonly',
            'labels',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getColumnDefinitions(): array
    {
        return [
            'storage_box_id' => mt('hcloud', 'Storage Box ID'),
            'id' => mt('hcloud', 'ID'),
            'name' => mt('hcloud', 'Name'),
            'username' => mt('hcloud', 'Username'),
            'home_directory' => mt('hcloud', 'Home Directory'),
            'description' => mt('hcloud', 'Description'),
            'server' => mt('hcloud', 'Server'),
            'created' => mt('hcloud', 'Created'),
            'access_reachable_externally' => mt('hcloud', 'Access Reachable Externally'),
            'access_samba_enabled' => mt('hcloud', 'Access Samba Enabled'),
            'access_ssh_enabled' => mt('hcloud', 'Access SSH Enabled'),
            'access_webdav_enabled' => mt('hcloud', 'Access WebDAV Enabled'),
            'access_readonly' => mt('hcloud', 'Access Readonly'),
            'labels' => mt('hcloud', 'Labels'),
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new UtcDateTime(['created']));
        $behaviors->add(new Json(['labels']));
    }
}
