<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use ipl\Orm\Model;

class ServerFirewall extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_server_firewall';
    }

    /**
     * @return list<string>
     */
    public function getKeyName(): array
    {
        return [
            'project_id',
            'server_id',
            'firewall_id',
        ];
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'status',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getColumnDefinitions(): array
    {
        return [
            'server_id' => mt('hcloud', 'Server ID'),
            'firewall_id' => mt('hcloud', 'Firewall ID'),
            'status' => mt('hcloud', 'Status'),
        ];
    }
}
