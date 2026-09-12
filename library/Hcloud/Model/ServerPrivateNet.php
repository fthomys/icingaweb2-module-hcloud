<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use ipl\Orm\Model;

class ServerPrivateNet extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_server_private_net';
    }

    /**
     * @return list<string>
     */
    public function getKeyName(): array
    {
        return [
            'project_id',
            'server_id',
            'network_id',
        ];
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'ip',
            'mac_address',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getColumnDefinitions(): array
    {
        return [
            'server_id' => mt('hcloud', 'Server ID'),
            'network_id' => mt('hcloud', 'Network ID'),
            'ip' => mt('hcloud', 'IP'),
            'mac_address' => mt('hcloud', 'MAC Address'),
        ];
    }
}
