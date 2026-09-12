<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use ipl\Orm\Model;

class PricingLoadBalancerType extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_pricing_load_balancer_type';
    }

    /**
     * @return list<string>
     */
    public function getKeyName(): array
    {
        return [
            'project_id',
            'load_balancer_type_id',
            'location_name',
        ];
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
            'name',
            'price_hourly_net',
            'price_hourly_gross',
            'price_monthly_net',
            'price_monthly_gross',
            'included_traffic',
            'price_per_tb_traffic_net',
            'price_per_tb_traffic_gross',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getColumnDefinitions(): array
    {
        return [
            'load_balancer_type_id' => mt('hcloud', 'Load Balancer Type ID'),
            'location_name' => mt('hcloud', 'Location Name'),
            'name' => mt('hcloud', 'Name'),
            'price_hourly_net' => mt('hcloud', 'Price Hourly Net'),
            'price_hourly_gross' => mt('hcloud', 'Price Hourly Gross'),
            'price_monthly_net' => mt('hcloud', 'Price Monthly Net'),
            'price_monthly_gross' => mt('hcloud', 'Price Monthly Gross'),
            'included_traffic' => mt('hcloud', 'Included Traffic'),
            'price_per_tb_traffic_net' => mt('hcloud', 'Price Per TB Traffic Net'),
            'price_per_tb_traffic_gross' => mt('hcloud', 'Price Per TB Traffic Gross'),
        ];
    }
}
