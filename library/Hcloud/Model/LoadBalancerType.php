<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use Icinga\Module\Hcloud\Model\Behavior\UtcDateTime;
use ipl\Orm\Behaviors;

class LoadBalancerType extends HcloudModel
{
    public function getTableName(): string
    {
        return 'hcloud_load_balancer_type';
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'name',
            'description',
            'max_connections',
            'max_services',
            'max_targets',
            'max_assigned_certificates',
            'deprecation_announced',
            'deprecation_unavailable_after',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getColumnDefinitions(): array
    {
        return [
            'id' => mt('hcloud', 'ID'),
            'name' => mt('hcloud', 'Name'),
            'description' => mt('hcloud', 'Description'),
            'max_connections' => mt('hcloud', 'Max Connections'),
            'max_services' => mt('hcloud', 'Max Services'),
            'max_targets' => mt('hcloud', 'Max Targets'),
            'max_assigned_certificates' => mt('hcloud', 'Max Assigned Certificates'),
            'deprecation_announced' => mt('hcloud', 'Deprecation Announced'),
            'deprecation_unavailable_after' => mt('hcloud', 'Deprecation Unavailable After'),
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new UtcDateTime(['deprecation_announced', 'deprecation_unavailable_after']));
    }
}
