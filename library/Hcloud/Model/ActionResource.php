<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use ipl\Orm\Model;

class ActionResource extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_action_resource';
    }

    /**
     * @return list<string>
     */
    public function getKeyName(): array
    {
        return [
            'project_id',
            'action_id',
            'resource_type',
            'resource_id',
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
            'action_id' => mt('hcloud', 'Action ID'),
            'resource_type' => mt('hcloud', 'Resource Type'),
            'resource_id' => mt('hcloud', 'Resource ID'),
        ];
    }
}
