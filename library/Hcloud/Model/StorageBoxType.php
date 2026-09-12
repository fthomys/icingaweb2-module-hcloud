<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use Icinga\Module\Hcloud\Model\Behavior\UtcDateTime;
use ipl\Orm\Behaviors;

class StorageBoxType extends HcloudModel
{
    public function getTableName(): string
    {
        return 'hcloud_storage_box_type';
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'name',
            'description',
            'size',
            'snapshot_limit',
            'automatic_snapshot_limit',
            'subaccounts_limit',
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
            'size' => mt('hcloud', 'Size'),
            'snapshot_limit' => mt('hcloud', 'Snapshot Limit'),
            'automatic_snapshot_limit' => mt('hcloud', 'Automatic Snapshot Limit'),
            'subaccounts_limit' => mt('hcloud', 'Subaccounts Limit'),
            'deprecation_announced' => mt('hcloud', 'Deprecation Announced'),
            'deprecation_unavailable_after' => mt('hcloud', 'Deprecation Unavailable After'),
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new UtcDateTime(['deprecation_announced', 'deprecation_unavailable_after']));
    }
}
