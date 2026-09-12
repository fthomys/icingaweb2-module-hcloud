<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Controllers;

use Icinga\Module\Hcloud\Enum\ServerStatus;
use Icinga\Module\Hcloud\Model\Server;
use Icinga\Module\Hcloud\Web\DetailController;

class ServerController extends DetailController
{
    protected function modelClass(): string
    {
        return Server::class;
    }

    /**
     * @return array<string, class-string>
     */
    protected function enumColumns(): array
    {
        return ['status' => ServerStatus::class];
    }

    /**
     * @return array<string, array{table: string, foreign: string, columns: list<string>}>
     */
    protected function relatedTables(): array
    {
        return [
            $this->translate('Public IPs') => [
                'table' => 'hcloud_server_public_ip',
                'foreign' => 'server_id',
                'columns' => ['family', 'ip', 'blocked'],
            ],
            $this->translate('Private Networks') => [
                'table' => 'hcloud_server_private_net',
                'foreign' => 'server_id',
                'columns' => ['network_id', 'ip', 'mac_address'],
            ],
            $this->translate('Firewalls') => [
                'table' => 'hcloud_server_firewall',
                'foreign' => 'server_id',
                'columns' => ['firewall_id', 'status'],
            ],
        ];
    }

    protected function metricResourceType(): ?string
    {
        return 'server';
    }
}
