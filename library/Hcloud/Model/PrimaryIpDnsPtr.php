<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use ipl\Orm\Model;

class PrimaryIpDnsPtr extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_primary_ip_dns_ptr';
    }

    /**
     * @return list<string>
     */
    public function getKeyName(): array
    {
        return [
            'project_id',
            'primary_ip_id',
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
            'primary_ip_id' => mt('hcloud', 'Primary IP ID'),
            'ip' => mt('hcloud', 'IP'),
            'dns_ptr' => mt('hcloud', 'DNS PTR'),
        ];
    }
}
