<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use ipl\Orm\Model;

class LoadBalancerTarget extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_load_balancer_target';
    }

    /**
     * @return list<string>
     */
    public function getKeyName(): array
    {
        return [
            'project_id',
            'load_balancer_id',
            'target_index',
        ];
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'parent_index',
            'type',
            'server_id',
            'server_ip',
            'ip_address',
            'label_selector',
            'use_private_ip',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getColumnDefinitions(): array
    {
        return [
            'load_balancer_id' => mt('hcloud', 'Load Balancer ID'),
            'target_index' => mt('hcloud', 'Target Index'),
            'parent_index' => mt('hcloud', 'Parent Index'),
            'type' => mt('hcloud', 'Type'),
            'server_id' => mt('hcloud', 'Server ID'),
            'server_ip' => mt('hcloud', 'Server IP'),
            'ip_address' => mt('hcloud', 'IP Address'),
            'label_selector' => mt('hcloud', 'Label Selector'),
            'use_private_ip' => mt('hcloud', 'Use Private IP'),
        ];
    }
}
