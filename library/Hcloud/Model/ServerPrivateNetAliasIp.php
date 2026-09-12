<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use ipl\Orm\Model;

class ServerPrivateNetAliasIp extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_server_private_net_alias_ip';
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
            'alias_ip',
        ];
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
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
            'alias_ip' => mt('hcloud', 'Alias IP'),
        ];
    }
}
