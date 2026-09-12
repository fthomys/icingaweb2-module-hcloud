<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync\Mapper;

use Icinga\Module\Hcloud\Sync\Payload;

final class FloatingIpMapper extends Mapper
{
    public static function resource(): string
    {
        return 'floating_ips';
    }

    public static function path(): string
    {
        return '/floating_ips';
    }

    public static function table(): string
    {
        return 'hcloud_floating_ip';
    }

    public static function childTables(): array
    {
        return ['hcloud_floating_ip_dns_ptr'];
    }

    public static function row(Payload $payload): array
    {
        return [
            'id' => $payload->int('id'),
            'name' => $payload->str('name'),
            'description' => $payload->str('description'),
            'created' => $payload->time('created'),
            'ip' => $payload->str('ip'),
            'type' => $payload->str('type'),
            'server_id' => $payload->int('server'),
            'blocked' => $payload->flag('blocked'),
            'home_location_id' => $payload->int('home_location.id'),
            'protection_delete' => $payload->flag('protection.delete'),
            'labels' => $payload->json('labels'),
        ];
    }

    public static function children(Payload $payload): array
    {
        $floatingIpId = $payload->int('id');
        $rows = [];

        foreach ($payload->each('dns_ptr') as $entry) {
            $ip = $entry->str('ip');
            if ($ip === null) {
                continue;
            }

            $rows[] = [
                'floating_ip_id' => $floatingIpId,
                'ip' => $ip,
                'dns_ptr' => $entry->str('dns_ptr'),
            ];
        }

        return ['hcloud_floating_ip_dns_ptr' => $rows];
    }
}
