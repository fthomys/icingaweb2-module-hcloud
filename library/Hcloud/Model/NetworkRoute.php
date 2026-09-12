<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use ipl\Orm\Model;

class NetworkRoute extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_network_route';
    }

    /**
     * @return list<string>
     */
    public function getKeyName(): array
    {
        return [
            'project_id',
            'network_id',
            'destination',
        ];
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'gateway',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getColumnDefinitions(): array
    {
        return [
            'network_id' => mt('hcloud', 'Network ID'),
            'destination' => mt('hcloud', 'Destination'),
            'gateway' => mt('hcloud', 'Gateway'),
        ];
    }
}
