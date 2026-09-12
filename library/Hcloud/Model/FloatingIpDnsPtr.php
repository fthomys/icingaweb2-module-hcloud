<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use ipl\Orm\Model;

class FloatingIpDnsPtr extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_floating_ip_dns_ptr';
    }

    /**
     * @return list<string>
     */
    public function getKeyName(): array
    {
        return [
            'project_id',
            'floating_ip_id',
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
            'floating_ip_id' => mt('hcloud', 'Floating IP ID'),
            'ip' => mt('hcloud', 'IP'),
            'dns_ptr' => mt('hcloud', 'DNS PTR'),
        ];
    }
}
