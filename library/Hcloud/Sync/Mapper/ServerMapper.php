<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync\Mapper;

use Icinga\Module\Hcloud\Sync\Payload;

final class ServerMapper extends Mapper
{
    public static function resource(): string
    {
        return 'servers';
    }

    public static function path(): string
    {
        return '/servers';
    }

    public static function table(): string
    {
        return 'hcloud_server';
    }

    public static function childTables(): array
    {
        return [
            'hcloud_server_public_ip',
            'hcloud_server_public_ip_dns_ptr',
            'hcloud_server_firewall',
            'hcloud_server_private_net',
            'hcloud_server_private_net_alias_ip',
        ];
    }

    public static function row(Payload $payload): array
    {
        return [
            'id' => $payload->int('id'),
            'name' => $payload->str('name'),
            'status' => $payload->str('status'),
            'created' => $payload->time('created'),
            'locked' => $payload->flag('locked'),
            'rescue_enabled' => $payload->flag('rescue_enabled'),
            'backup_window' => $payload->str('backup_window'),
            'outgoing_traffic' => $payload->int('outgoing_traffic'),
            'ingoing_traffic' => $payload->int('ingoing_traffic'),
            'included_traffic' => $payload->int('included_traffic'),
            'primary_disk_size' => $payload->num('primary_disk_size'),
            'server_type_id' => $payload->int('server_type.id'),
            'location_id' => $payload->int('location.id'),
            'image_id' => $payload->int('image.id'),
            'iso_id' => $payload->int('iso.id'),
            'placement_group_id' => $payload->int('placement_group.id'),
            'protection_delete' => $payload->flag('protection.delete'),
            'protection_rebuild' => $payload->flag('protection.rebuild'),
            'labels' => $payload->json('labels'),
        ];
    }

    public static function children(Payload $payload): array
    {
        $serverId = $payload->int('id');

        $publicIps = [];
        $dnsPtrs = [];
        $firewalls = [];
        $privateNets = [];
        $aliasIps = [];

        foreach (['ipv4', 'ipv6'] as $family) {
            $net = $payload->child('public_net.' . $family);
            if ($net === null) {
                continue;
            }

            $ip = $net->str('ip');

            $publicIps[] = [
                'server_id' => $serverId,
                'family' => $family,
                'ip_id' => $net->int('id'),
                'ip' => $ip,
                'blocked' => $net->flag('blocked'),
            ];

            foreach (self::dnsPtrRows($net, $ip) as $row) {
                $dnsPtrs[] = ['server_id' => $serverId, 'family' => $family] + $row;
            }
        }

        foreach ($payload->each('public_net.firewalls') as $firewall) {
            $firewallId = $firewall->int('id');
            if ($firewallId === null) {
                continue;
            }

            $firewalls[] = [
                'server_id' => $serverId,
                'firewall_id' => $firewallId,
                'status' => $firewall->str('status'),
            ];
        }

        foreach ($payload->each('private_net') as $privateNet) {
            $networkId = $privateNet->int('network');
            if ($networkId === null) {
                continue;
            }

            $privateNets[] = [
                'server_id' => $serverId,
                'network_id' => $networkId,
                'ip' => $privateNet->str('ip'),
                'mac_address' => $privateNet->str('mac_address'),
            ];

            foreach ($privateNet->strings('alias_ips') as $aliasIp) {
                $aliasIps[] = [
                    'server_id' => $serverId,
                    'network_id' => $networkId,
                    'alias_ip' => $aliasIp,
                ];
            }
        }

        return [
            'hcloud_server_public_ip' => $publicIps,
            'hcloud_server_public_ip_dns_ptr' => $dnsPtrs,
            'hcloud_server_firewall' => $firewalls,
            'hcloud_server_private_net' => $privateNets,
            'hcloud_server_private_net_alias_ip' => $aliasIps,
        ];
    }

    /**
     * @return list<array{ip: string, dns_ptr: ?string}>
     */
    private static function dnsPtrRows(Payload $net, ?string $ip): array
    {
        $entries = $net->each('dns_ptr');

        if ($entries !== []) {
            $rows = [];
            foreach ($entries as $entry) {
                $entryIp = $entry->str('ip');
                if ($entryIp !== null) {
                    $rows[] = ['ip' => $entryIp, 'dns_ptr' => $entry->str('dns_ptr')];
                }
            }

            return $rows;
        }

        $scalar = $net->str('dns_ptr');
        if ($scalar === null || $ip === null) {
            return [];
        }

        return [['ip' => $ip, 'dns_ptr' => $scalar]];
    }
}
