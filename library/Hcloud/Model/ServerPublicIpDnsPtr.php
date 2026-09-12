<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use ipl\Orm\Model;

class ServerPublicIpDnsPtr extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_server_public_ip_dns_ptr';
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
            'ip',
        ];
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'dns_ptr',
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
            'ip' => mt('hcloud', 'IP'),
            'dns_ptr' => mt('hcloud', 'DNS PTR'),
        ];
    }
}
