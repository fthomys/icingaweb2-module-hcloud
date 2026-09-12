<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use Icinga\Module\Hcloud\Model\Behavior\Json;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;

class FirewallRule extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_firewall_rule';
    }

    /**
     * @return list<string>
     */
    public function getKeyName(): array
    {
        return [
            'project_id',
            'firewall_id',
            'rule_index',
        ];
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'direction',
            'protocol',
            'port',
            'description',
            'source_ips',
            'destination_ips',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getColumnDefinitions(): array
    {
        return [
            'firewall_id' => mt('hcloud', 'Firewall ID'),
            'rule_index' => mt('hcloud', 'Rule Index'),
            'direction' => mt('hcloud', 'Direction'),
            'protocol' => mt('hcloud', 'Protocol'),
            'port' => mt('hcloud', 'Port'),
            'description' => mt('hcloud', 'Description'),
            'source_ips' => mt('hcloud', 'Source IPs'),
            'destination_ips' => mt('hcloud', 'Destination IPs'),
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new Json(['source_ips', 'destination_ips']));
    }
}
