<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync\Mapper;

use Icinga\Module\Hcloud\Sync\Payload;

final class NetworkMapper extends Mapper
{
    public static function resource(): string
    {
        return 'networks';
    }

    public static function path(): string
    {
        return '/networks';
    }

    public static function table(): string
    {
        return 'hcloud_network';
    }

    public static function childTables(): array
    {
        return ['hcloud_network_subnet', 'hcloud_network_route'];
    }

    public static function row(Payload $payload): array
    {
        return [
            'id' => $payload->int('id'),
            'name' => $payload->str('name'),
            'ip_range' => $payload->str('ip_range'),
            'created' => $payload->time('created'),
            'expose_routes_to_vswitch' => $payload->flag('expose_routes_to_vswitch'),
            'protection_delete' => $payload->flag('protection.delete'),
            'labels' => $payload->json('labels'),
        ];
    }

    public static function children(Payload $payload): array
    {
        $networkId = $payload->int('id');
        $subnets = [];
        $routes = [];

        foreach ($payload->each('subnets') as $subnet) {
            $ipRange = $subnet->str('ip_range');
            if ($ipRange === null) {
                continue;
            }

            $subnets[] = [
                'network_id' => $networkId,
                'ip_range' => $ipRange,
                'type' => $subnet->str('type'),
                'network_zone' => $subnet->str('network_zone'),
                'gateway' => $subnet->str('gateway'),
                'vswitch_id' => $subnet->int('vswitch_id'),
            ];
        }

        foreach ($payload->each('routes') as $route) {
            $destination = $route->str('destination');
            if ($destination === null) {
                continue;
            }

            $routes[] = [
                'network_id' => $networkId,
                'destination' => $destination,
                'gateway' => $route->str('gateway'),
            ];
        }

        return [
            'hcloud_network_subnet' => $subnets,
            'hcloud_network_route' => $routes,
        ];
    }
}
