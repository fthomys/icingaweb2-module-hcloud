<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync\Mapper;

use Icinga\Module\Hcloud\Sync\Payload;

final class ServerTypeMapper extends Mapper
{
    public static function resource(): string
    {
        return 'server_types';
    }

    public static function path(): string
    {
        return '/server_types';
    }

    public static function table(): string
    {
        return 'hcloud_server_type';
    }

    public static function childTables(): array
    {
        return ['hcloud_server_type_location'];
    }

    public static function row(Payload $payload): array
    {
        return [
            'id' => $payload->int('id'),
            'name' => $payload->str('name'),
            'description' => $payload->str('description'),
            'cores' => $payload->int('cores'),
            'memory' => $payload->num('memory'),
            'disk' => $payload->num('disk'),
            'storage_type' => $payload->str('storage_type'),
            'cpu_type' => $payload->str('cpu_type'),
            'category' => $payload->str('category'),
            'architecture' => $payload->str('architecture'),
        ] + $payload->deprecation();
    }

    public static function children(Payload $payload): array
    {
        $serverTypeId = $payload->int('id');
        $rows = [];

        foreach ($payload->each('locations') as $location) {
            $locationId = $location->int('id');
            if ($locationId === null) {
                continue;
            }

            $rows[] = [
                'server_type_id' => $serverTypeId,
                'location_id' => $locationId,
                'location_name' => $location->str('name') ?? (string) $locationId,
                'recommended' => $location->flag('recommended'),
                'available' => $location->flag('available'),
            ] + $location->deprecation();
        }

        return ['hcloud_server_type_location' => $rows];
    }
}
