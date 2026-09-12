<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync\Mapper;

use Icinga\Module\Hcloud\Sync\Payload;

final class ZoneMapper extends Mapper
{
    public static function resource(): string
    {
        return 'zones';
    }

    public static function path(): string
    {
        return '/zones';
    }

    public static function table(): string
    {
        return 'hcloud_zone';
    }

    public static function childTables(): array
    {
        return ['hcloud_zone_nameserver', 'hcloud_zone_primary_nameserver'];
    }

    public static function row(Payload $payload): array
    {
        return [
            'id' => $payload->int('id'),
            'name' => $payload->str('name'),
            'created' => $payload->time('created'),
            'mode' => $payload->str('mode'),
            'ttl' => $payload->int('ttl'),
            'status' => $payload->str('status'),
            'record_count' => $payload->int('record_count'),
            'registrar' => $payload->str('registrar'),
            'delegation_last_check' => $payload->time('authoritative_nameservers.delegation_last_check'),
            'delegation_status' => $payload->str('authoritative_nameservers.delegation_status'),
            'protection_delete' => $payload->flag('protection.delete'),
            'labels' => $payload->json('labels'),
        ];
    }

    public static function children(Payload $payload): array
    {
        $zoneId = $payload->int('id');

        $nameservers = [];
        foreach (['assigned', 'delegated'] as $kind) {
            foreach ($payload->strings('authoritative_nameservers.' . $kind) as $address) {
                $nameservers[] = ['zone_id' => $zoneId, 'kind' => $kind, 'address' => $address];
            }
        }

        $primaries = [];
        foreach ($payload->each('primary_nameservers') as $primary) {
            $address = $primary->str('address');
            if ($address === null) {
                continue;
            }

            $primaries[] = [
                'zone_id' => $zoneId,
                'address' => $address,
                'port' => $primary->int('port'),
                'tsig_algorithm' => $primary->str('tsig_algorithm'),
            ];
        }

        return [
            'hcloud_zone_nameserver' => $nameservers,
            'hcloud_zone_primary_nameserver' => $primaries,
        ];
    }
}
