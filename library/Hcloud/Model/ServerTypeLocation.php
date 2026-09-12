<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use Icinga\Module\Hcloud\Model\Behavior\UtcDateTime;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;

class ServerTypeLocation extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_server_type_location';
    }

    /**
     * @return list<string>
     */
    public function getKeyName(): array
    {
        return [
            'project_id',
            'server_type_id',
            'location_id',
        ];
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'location_name',
            'recommended',
            'available',
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
            'server_type_id' => mt('hcloud', 'Server Type ID'),
            'location_id' => mt('hcloud', 'Location ID'),
            'location_name' => mt('hcloud', 'Location Name'),
            'recommended' => mt('hcloud', 'Recommended'),
            'available' => mt('hcloud', 'Available'),
            'deprecation_announced' => mt('hcloud', 'Deprecation Announced'),
            'deprecation_unavailable_after' => mt('hcloud', 'Deprecation Unavailable After'),
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new UtcDateTime(['deprecation_announced', 'deprecation_unavailable_after']));
    }
}
