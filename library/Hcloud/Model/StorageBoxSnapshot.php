<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use Icinga\Module\Hcloud\Model\Behavior\Json;
use Icinga\Module\Hcloud\Model\Behavior\UtcDateTime;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;

class StorageBoxSnapshot extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_storage_box_snapshot';
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
            'description',
            'is_automatic',
            'created',
            'stats_size',
            'stats_size_filesystem',
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
            'description' => mt('hcloud', 'Description'),
            'is_automatic' => mt('hcloud', 'Is Automatic'),
            'created' => mt('hcloud', 'Created'),
            'stats_size' => mt('hcloud', 'Stats Size'),
            'stats_size_filesystem' => mt('hcloud', 'Stats Size Filesystem'),
            'labels' => mt('hcloud', 'Labels'),
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new UtcDateTime(['created']));
        $behaviors->add(new Json(['labels']));
    }
}
