<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync\Mapper;

use Icinga\Module\Hcloud\Sync\Payload;

final class ImageMapper extends Mapper
{
    public static function resource(): string
    {
        return 'images';
    }

    public static function path(): string
    {
        return '/images';
    }

    public static function table(): string
    {
        return 'hcloud_image';
    }

    public static function row(Payload $payload): array
    {
        return [
            'id' => $payload->int('id'),
            'name' => $payload->str('name'),
            'description' => $payload->str('description'),
            'type' => $payload->str('type'),
            'status' => $payload->str('status'),
            'created' => $payload->time('created'),
            'image_size' => $payload->num('image_size'),
            'disk_size' => $payload->num('disk_size'),
            'architecture' => $payload->str('architecture'),
            'os_flavor' => $payload->str('os_flavor'),
            'os_version' => $payload->str('os_version'),
            'rapid_deploy' => $payload->flag('rapid_deploy'),
            'bound_to' => $payload->int('bound_to'),
            'created_from_id' => $payload->int('created_from.id'),
            'created_from_name' => $payload->str('created_from.name'),
            'deleted' => $payload->time('deleted'),
            'protection_delete' => $payload->flag('protection.delete'),
            'labels' => $payload->json('labels'),
        ] + $payload->deprecation();
    }
}
