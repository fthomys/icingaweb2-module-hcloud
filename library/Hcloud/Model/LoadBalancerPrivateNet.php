<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use ipl\Orm\Model;

class LoadBalancerPrivateNet extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_load_balancer_private_net';
    }

    /**
     * @return list<string>
     */
    public function getKeyName(): array
    {
        return [
            'project_id',
            'load_balancer_id',
            'network_id',
        ];
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'ip',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getColumnDefinitions(): array
    {
        return [
            'load_balancer_id' => mt('hcloud', 'Load Balancer ID'),
            'network_id' => mt('hcloud', 'Network ID'),
            'ip' => mt('hcloud', 'IP'),
        ];
    }
}
