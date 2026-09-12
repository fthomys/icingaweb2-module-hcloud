<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use ipl\Orm\Model;

class PricingStorageBoxType extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_pricing_storage_box_type';
    }

    /**
     * @return list<string>
     */
    public function getKeyName(): array
    {
        return [
            'project_id',
            'storage_box_type_id',
            'location_name',
        ];
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'price_hourly_net',
            'price_hourly_gross',
            'price_monthly_net',
            'price_monthly_gross',
            'setup_fee_net',
            'setup_fee_gross',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getColumnDefinitions(): array
    {
        return [
            'storage_box_type_id' => mt('hcloud', 'Storage Box Type ID'),
            'location_name' => mt('hcloud', 'Location Name'),
            'price_hourly_net' => mt('hcloud', 'Price Hourly Net'),
            'price_hourly_gross' => mt('hcloud', 'Price Hourly Gross'),
            'price_monthly_net' => mt('hcloud', 'Price Monthly Net'),
            'price_monthly_gross' => mt('hcloud', 'Price Monthly Gross'),
            'setup_fee_net' => mt('hcloud', 'Setup Fee Net'),
            'setup_fee_gross' => mt('hcloud', 'Setup Fee Gross'),
        ];
    }
}
