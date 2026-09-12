<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use Icinga\Module\Hcloud\Model\Behavior\Json;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;

class ZoneRrset extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_zone_rrset';
    }

    /**
     * @return list<string>
     */
    public function getKeyName(): array
    {
        return [
            'project_id',
            'zone_id',
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
            'type',
            'ttl',
            'protection_change',
            'labels',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getColumnDefinitions(): array
    {
        return [
            'zone_id' => mt('hcloud', 'Zone ID'),
            'id' => mt('hcloud', 'ID'),
            'name' => mt('hcloud', 'Name'),
            'type' => mt('hcloud', 'Type'),
            'ttl' => mt('hcloud', 'TTL'),
            'protection_change' => mt('hcloud', 'Protection Change'),
            'labels' => mt('hcloud', 'Labels'),
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Json(['labels']));
    }
}
