<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync\Mapper;

use Icinga\Module\Hcloud\Sync\Payload;

final class PlacementGroupMapper extends Mapper
{
    public static function resource(): string
    {
        return 'placement_groups';
    }

    public static function path(): string
    {
        return '/placement_groups';
    }

    public static function table(): string
    {
        return 'hcloud_placement_group';
    }

    public static function row(Payload $payload): array
    {
        return [
            'id' => $payload->int('id'),
            'name' => $payload->str('name'),
            'created' => $payload->time('created'),
            'type' => $payload->str('type'),
            'labels' => $payload->json('labels'),
        ];
    }
}
