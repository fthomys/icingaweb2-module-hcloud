<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use ipl\Orm\Model;

class PricingPrimaryIp extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_pricing_primary_ip';
    }

    /**
     * @return list<string>
     */
    public function getKeyName(): array
    {
        return [
            'project_id',
            'type',
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
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getColumnDefinitions(): array
    {
        return [
            'type' => mt('hcloud', 'Type'),
            'location_name' => mt('hcloud', 'Location Name'),
            'price_hourly_net' => mt('hcloud', 'Price Hourly Net'),
            'price_hourly_gross' => mt('hcloud', 'Price Hourly Gross'),
            'price_monthly_net' => mt('hcloud', 'Price Monthly Net'),
            'price_monthly_gross' => mt('hcloud', 'Price Monthly Gross'),
        ];
    }
}
