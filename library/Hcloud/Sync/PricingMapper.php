<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync;

use DateTimeInterface;
use Icinga\Module\Hcloud\Model\Behavior\UtcDateTime;

/**
 * /pricing is a singleton, not a paginated collection, so it cannot be a Mapper.
 */
final class PricingMapper
{
    public const PATH = '/pricing';

    public const TABLES = [
        'hcloud_pricing',
        'hcloud_pricing_server_type',
        'hcloud_pricing_load_balancer_type',
        'hcloud_pricing_primary_ip',
        'hcloud_pricing_floating_ip',
    ];

    /**
     * @param array<string, mixed> $response The decoded /pricing response
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public static function rows(array $response, DateTimeInterface $now): array
    {
        $payload = new Payload($response);
        $pricing = $payload->child('pricing') ?? new Payload([]);

        return [
            'hcloud_pricing' => [[
                'currency' => $pricing->str('currency'),
                'vat_rate' => $pricing->num('vat_rate'),
                'image_price_per_gb_month_net' => $pricing->num('image.price_per_gb_month.net'),
                'image_price_per_gb_month_gross' => $pricing->num('image.price_per_gb_month.gross'),
                'volume_price_per_gb_month_net' => $pricing->num('volume.price_per_gb_month.net'),
                'volume_price_per_gb_month_gross' => $pricing->num('volume.price_per_gb_month.gross'),
                'server_backup_percentage' => $pricing->num('server_backup.percentage'),
                'updated' => UtcDateTime::format($now),
            ]],
            'hcloud_pricing_server_type' => self::typeRows($pricing, 'server_types', 'server_type_id', true),
            'hcloud_pricing_load_balancer_type' => self::typeRows(
                $pricing,
                'load_balancer_types',
                'load_balancer_type_id',
                true
            ),
            'hcloud_pricing_primary_ip' => self::ipRows($pricing, 'primary_ips', true),
            'hcloud_pricing_floating_ip' => self::ipRows($pricing, 'floating_ips', false),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function typeRows(Payload $pricing, string $key, string $idColumn, bool $withTraffic): array
    {
        $rows = [];

        foreach ($pricing->each($key) as $type) {
            $id = $type->int('id');
            if ($id === null) {
                continue;
            }

            foreach ($type->each('prices') as $price) {
                $location = $price->str('location');
                if ($location === null) {
                    continue;
                }

                $row = [
                    $idColumn => $id,
                    'location_name' => $location,
                    'name' => $type->str('name'),
                    'price_hourly_net' => $price->num('price_hourly.net'),
                    'price_hourly_gross' => $price->num('price_hourly.gross'),
                    'price_monthly_net' => $price->num('price_monthly.net'),
                    'price_monthly_gross' => $price->num('price_monthly.gross'),
                ];

                if ($withTraffic) {
                    $row['included_traffic'] = $price->int('included_traffic');
                    $row['price_per_tb_traffic_net'] = $price->num('price_per_tb_traffic.net');
                    $row['price_per_tb_traffic_gross'] = $price->num('price_per_tb_traffic.gross');
                }

                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function ipRows(Payload $pricing, string $key, bool $withHourly): array
    {
        $rows = [];

        foreach ($pricing->each($key) as $entry) {
            $type = $entry->str('type');
            if ($type === null) {
                continue;
            }

            foreach ($entry->each('prices') as $price) {
                $location = $price->str('location');
                if ($location === null) {
                    continue;
                }

                $row = [
                    'type' => $type,
                    'location_name' => $location,
                    'price_monthly_net' => $price->num('price_monthly.net'),
                    'price_monthly_gross' => $price->num('price_monthly.gross'),
                ];

                if ($withHourly) {
                    $row['price_hourly_net'] = $price->num('price_hourly.net');
                    $row['price_hourly_gross'] = $price->num('price_hourly.gross');
                }

                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * Storage box prices ride along with the type objects on the second base URI.
     *
     * @param array<string, mixed> $storageBoxType
     *
     * @return list<array<string, mixed>>
     */
    public static function storageBoxTypeRows(array $storageBoxType): array
    {
        $type = new Payload($storageBoxType);
        $id = $type->int('id');

        if ($id === null) {
            return [];
        }

        $rows = [];

        foreach ($type->each('prices') as $price) {
            $location = $price->str('location');
            if ($location === null) {
                continue;
            }

            $rows[] = [
                'storage_box_type_id' => $id,
                'location_name' => $location,
                'price_hourly_net' => $price->num('price_hourly.net'),
                'price_hourly_gross' => $price->num('price_hourly.gross'),
                'price_monthly_net' => $price->num('price_monthly.net'),
                'price_monthly_gross' => $price->num('price_monthly.gross'),
                'setup_fee_net' => $price->num('setup_fee.net'),
                'setup_fee_gross' => $price->num('setup_fee.gross'),
            ];
        }

        return $rows;
    }
}
