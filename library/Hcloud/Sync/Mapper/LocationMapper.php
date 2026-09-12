<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync\Mapper;

use Icinga\Module\Hcloud\Sync\Payload;

final class LocationMapper extends Mapper
{
    public static function resource(): string
    {
        return 'locations';
    }

    public static function path(): string
    {
        return '/locations';
    }

    public static function table(): string
    {
        return 'hcloud_location';
    }

    public static function row(Payload $payload): array
    {
        return [
            'id' => $payload->int('id'),
            'name' => $payload->str('name'),
            'description' => $payload->str('description'),
            'country' => $payload->str('country'),
            'city' => $payload->str('city'),
            'latitude' => $payload->num('latitude'),
            'longitude' => $payload->num('longitude'),
            'network_zone' => $payload->str('network_zone'),
        ];
    }
}
