<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use ipl\Orm\Model;

class ZonePrimaryNameserver extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_zone_primary_nameserver';
    }

    /**
     * @return list<string>
     */
    public function getKeyName(): array
    {
        return [
            'project_id',
            'zone_id',
            'address',
        ];
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'port',
            'tsig_algorithm',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getColumnDefinitions(): array
    {
        return [
            'zone_id' => mt('hcloud', 'Zone ID'),
            'address' => mt('hcloud', 'Address'),
            'port' => mt('hcloud', 'Port'),
            'tsig_algorithm' => mt('hcloud', 'TSIG Algorithm'),
        ];
    }
}
