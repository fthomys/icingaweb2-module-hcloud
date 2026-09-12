<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use Icinga\Module\Hcloud\Model\Behavior\UtcDateTime;
use ipl\Orm\Behaviors;

class IsoImage extends HcloudModel
{
    public function getTableName(): string
    {
        return 'hcloud_iso';
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'name',
            'description',
            'type',
            'architecture',
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
            'type' => mt('hcloud', 'Type'),
            'architecture' => mt('hcloud', 'Architecture'),
            'deprecation_announced' => mt('hcloud', 'Deprecation Announced'),
            'deprecation_unavailable_after' => mt('hcloud', 'Deprecation Unavailable After'),
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new UtcDateTime(['deprecation_announced', 'deprecation_unavailable_after']));
    }
}
