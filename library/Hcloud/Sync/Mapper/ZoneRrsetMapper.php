<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync\Mapper;

use Icinga\Module\Hcloud\Sync\Payload;

final class ZoneRrsetMapper extends Mapper
{
    public static function resource(): string
    {
        return 'zone_rrsets';
    }

    public static function path(): string
    {
        return '/zones/{id}/rrsets';
    }

    public static function table(): string
    {
        return 'hcloud_zone_rrset';
    }

    public static function key(): string
    {
        return 'rrsets';
    }

    public static function childTables(): array
    {
        return ['hcloud_zone_rrset_record'];
    }

    public static function row(Payload $payload): array
    {
        return [
            'zone_id' => $payload->int('zone'),
            'id' => $payload->str('id'),
            'name' => $payload->str('name'),
            'type' => $payload->str('type'),
            'ttl' => $payload->int('ttl'),
            'protection_change' => $payload->flag('protection.change'),
            'labels' => $payload->json('labels'),
        ];
    }

    public static function children(Payload $payload): array
    {
        $rows = [];

        foreach ($payload->each('records') as $index => $record) {
            $rows[] = [
                'zone_id' => $payload->int('zone'),
                'rrset_id' => $payload->str('id'),
                'record_index' => $index,
                'value' => $record->str('value') ?? '',
                'comment' => $record->str('comment'),
            ];
        }

        return ['hcloud_zone_rrset_record' => $rows];
    }
}
