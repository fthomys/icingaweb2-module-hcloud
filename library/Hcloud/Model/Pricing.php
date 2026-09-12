<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use Icinga\Module\Hcloud\Model\Behavior\UtcDateTime;
use ipl\Orm\Behaviors;
use ipl\Orm\Model;

class Pricing extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_pricing';
    }

    public function getKeyName(): string
    {
        return 'project_id';
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'currency',
            'vat_rate',
            'image_price_per_gb_month_net',
            'image_price_per_gb_month_gross',
            'volume_price_per_gb_month_net',
            'volume_price_per_gb_month_gross',
            'server_backup_percentage',
            'updated',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getColumnDefinitions(): array
    {
        return [
            'currency' => mt('hcloud', 'Currency'),
            'vat_rate' => mt('hcloud', 'VAT Rate'),
            'image_price_per_gb_month_net' => mt('hcloud', 'Image Price Per GB Month Net'),
            'image_price_per_gb_month_gross' => mt('hcloud', 'Image Price Per GB Month Gross'),
            'volume_price_per_gb_month_net' => mt('hcloud', 'Volume Price Per GB Month Net'),
            'volume_price_per_gb_month_gross' => mt('hcloud', 'Volume Price Per GB Month Gross'),
            'server_backup_percentage' => mt('hcloud', 'Server Backup Percentage'),
            'updated' => mt('hcloud', 'Updated'),
        ];
    }

    public function createBehaviors(Behaviors $behaviors): void
    {
        $behaviors->add(new UtcDateTime(['updated']));
    }
}
