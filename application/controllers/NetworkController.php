<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Controllers;

use Icinga\Module\Hcloud\Model\Network;
use Icinga\Module\Hcloud\Web\DetailController;

class NetworkController extends DetailController
{
    protected function modelClass(): string
    {
        return Network::class;
    }

    /**
     * @return array<string, array{table: string, foreign: string, columns: list<string>}>
     */
    protected function relatedTables(): array
    {
        return [
            $this->translate('Subnets') => [
                'table' => 'hcloud_network_subnet',
                'foreign' => 'network_id',
                'columns' => ['ip_range', 'type', 'network_zone', 'gateway'],
            ],
            $this->translate('Routes') => [
                'table' => 'hcloud_network_route',
                'foreign' => 'network_id',
                'columns' => ['destination', 'gateway'],
            ],
        ];
    }
}
