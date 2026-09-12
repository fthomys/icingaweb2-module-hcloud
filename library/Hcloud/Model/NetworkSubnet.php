<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use ipl\Orm\Model;

class NetworkSubnet extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_network_subnet';
    }

    /**
     * @return list<string>
     */
    public function getKeyName(): array
    {
        return [
            'project_id',
            'network_id',
            'ip_range',
        ];
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'type',
            'network_zone',
            'gateway',
            'vswitch_id',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getColumnDefinitions(): array
    {
        return [
            'network_id' => mt('hcloud', 'Network ID'),
            'ip_range' => mt('hcloud', 'IP Range'),
            'type' => mt('hcloud', 'Type'),
            'network_zone' => mt('hcloud', 'Network Zone'),
            'gateway' => mt('hcloud', 'Gateway'),
            'vswitch_id' => mt('hcloud', 'vSwitch ID'),
        ];
    }
}
