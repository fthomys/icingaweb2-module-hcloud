<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use ipl\Orm\Model;

class FirewallAppliedResource extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_firewall_applied_resource';
    }

    /**
     * @return list<string>
     */
    public function getKeyName(): array
    {
        return [
            'project_id',
            'firewall_id',
            'applied_index',
            'server_id',
        ];
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'resource_type',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getColumnDefinitions(): array
    {
        return [
            'firewall_id' => mt('hcloud', 'Firewall ID'),
            'applied_index' => mt('hcloud', 'Applied Index'),
            'server_id' => mt('hcloud', 'Server ID'),
            'resource_type' => mt('hcloud', 'Resource Type'),
        ];
    }
}
