<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use ipl\Orm\Model;

class ZoneRrsetRecord extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_zone_rrset_record';
    }

    /**
     * @return list<string>
     */
    public function getKeyName(): array
    {
        return [
            'project_id',
            'zone_id',
            'rrset_id',
            'record_index',
        ];
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'value',
            'comment',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getColumnDefinitions(): array
    {
        return [
            'zone_id' => mt('hcloud', 'Zone ID'),
            'rrset_id' => mt('hcloud', 'RRset ID'),
            'record_index' => mt('hcloud', 'Record Index'),
            'value' => mt('hcloud', 'Value'),
            'comment' => mt('hcloud', 'Comment'),
        ];
    }
}
