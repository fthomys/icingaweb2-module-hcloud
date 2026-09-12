<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Controllers;

use Icinga\Module\Hcloud\Model\Firewall;
use Icinga\Module\Hcloud\Web\DetailController;

class FirewallController extends DetailController
{
    protected function modelClass(): string
    {
        return Firewall::class;
    }

    /**
     * @return array<string, array{table: string, foreign: string, columns: list<string>}>
     */
    protected function relatedTables(): array
    {
        return [
            $this->translate('Rules') => [
                'table' => 'hcloud_firewall_rule',
                'foreign' => 'firewall_id',
                'columns' => ['rule_index', 'direction', 'protocol', 'port', 'source_ips', 'destination_ips'],
            ],
            $this->translate('Applied To') => [
                'table' => 'hcloud_firewall_applied_to',
                'foreign' => 'firewall_id',
                'columns' => ['applied_index', 'type', 'server_id', 'label_selector'],
            ],
        ];
    }
}
