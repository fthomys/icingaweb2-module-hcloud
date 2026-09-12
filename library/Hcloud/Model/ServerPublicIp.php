<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use ipl\Orm\Model;

class ServerPublicIp extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_server_public_ip';
    }

    /**
     * @return list<string>
     */
    public function getKeyName(): array
    {
        return [
            'project_id',
            'server_id',
            'family',
        ];
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'ip_id',
            'ip',
            'blocked',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getColumnDefinitions(): array
    {
        return [
            'server_id' => mt('hcloud', 'Server ID'),
            'family' => mt('hcloud', 'Family'),
            'ip_id' => mt('hcloud', 'IP ID'),
            'ip' => mt('hcloud', 'IP'),
            'blocked' => mt('hcloud', 'Blocked'),
        ];
    }
}
