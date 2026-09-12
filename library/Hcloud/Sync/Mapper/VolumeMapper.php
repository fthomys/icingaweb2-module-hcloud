<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync\Mapper;

use Icinga\Module\Hcloud\Sync\Payload;

final class VolumeMapper extends Mapper
{
    public static function resource(): string
    {
        return 'volumes';
    }

    public static function path(): string
    {
        return '/volumes';
    }

    public static function table(): string
    {
        return 'hcloud_volume';
    }

    public static function row(Payload $payload): array
    {
        return [
            'id' => $payload->int('id'),
            'name' => $payload->str('name'),
            'created' => $payload->time('created'),
            'status' => $payload->str('status'),
            'server_id' => $payload->int('server'),
            'linux_device' => $payload->str('linux_device'),
            'size' => $payload->num('size'),
            'format' => $payload->str('format'),
            'location_id' => $payload->int('location.id'),
            'protection_delete' => $payload->flag('protection.delete'),
            'labels' => $payload->json('labels'),
        ];
    }
}
