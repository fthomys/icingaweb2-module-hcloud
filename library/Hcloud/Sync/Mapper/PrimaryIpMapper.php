<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync\Mapper;

use Icinga\Module\Hcloud\Sync\Payload;

final class PrimaryIpMapper extends Mapper
{
    public static function resource(): string
    {
        return 'primary_ips';
    }

    public static function path(): string
    {
        return '/primary_ips';
    }

    public static function table(): string
    {
        return 'hcloud_primary_ip';
    }

    public static function childTables(): array
    {
        return ['hcloud_primary_ip_dns_ptr'];
    }

    public static function row(Payload $payload): array
    {
        return [
            'id' => $payload->int('id'),
            'name' => $payload->str('name'),
            'created' => $payload->time('created'),
            'ip' => $payload->str('ip'),
            'type' => $payload->str('type'),
            'blocked' => $payload->flag('blocked'),
            'auto_delete' => $payload->flag('auto_delete'),
            'assignee_type' => $payload->str('assignee_type'),
            'assignee_id' => $payload->int('assignee_id'),
            'location_id' => $payload->int('location.id'),
            'protection_delete' => $payload->flag('protection.delete'),
            'labels' => $payload->json('labels'),
        ];
    }

    public static function children(Payload $payload): array
    {
        $primaryIpId = $payload->int('id');
        $rows = [];

        foreach ($payload->each('dns_ptr') as $entry) {
            $ip = $entry->str('ip');
            if ($ip === null) {
                continue;
            }

            $rows[] = [
                'primary_ip_id' => $primaryIpId,
                'ip' => $ip,
                'dns_ptr' => $entry->str('dns_ptr'),
            ];
        }

        return ['hcloud_primary_ip_dns_ptr' => $rows];
    }
}
