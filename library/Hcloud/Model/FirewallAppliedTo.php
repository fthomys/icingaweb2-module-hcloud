<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use ipl\Orm\Model;

class FirewallAppliedTo extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_firewall_applied_to';
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
        ];
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'type',
            'server_id',
            'label_selector',
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
            'type' => mt('hcloud', 'Type'),
            'server_id' => mt('hcloud', 'Server ID'),
            'label_selector' => mt('hcloud', 'Label Selector'),
        ];
    }
}
