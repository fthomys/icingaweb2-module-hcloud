<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use ipl\Orm\Model;

class ZoneNameserver extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_zone_nameserver';
    }

    /**
     * @return list<string>
     */
    public function getKeyName(): array
    {
        return [
            'project_id',
            'zone_id',
            'kind',
            'address',
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
            'zone_id' => mt('hcloud', 'Zone ID'),
            'kind' => mt('hcloud', 'Kind'),
            'address' => mt('hcloud', 'Address'),
        ];
    }
}
