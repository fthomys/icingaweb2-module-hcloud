<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use ipl\Orm\Model;

class LoadBalancerTargetHealth extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_load_balancer_target_health';
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
            'listen_port',
        ];
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'status',
            'detail',
            'http_status_code',
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
            'listen_port' => mt('hcloud', 'Listen Port'),
            'status' => mt('hcloud', 'Status'),
            'detail' => mt('hcloud', 'Detail'),
            'http_status_code' => mt('hcloud', 'HTTP Status Code'),
        ];
    }
}
