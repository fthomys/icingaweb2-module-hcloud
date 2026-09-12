<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync\Mapper;

use Icinga\Module\Hcloud\Sync\Payload;

final class IsoImageMapper extends Mapper
{
    public static function resource(): string
    {
        return 'isos';
    }

    public static function path(): string
    {
        return '/isos';
    }

    public static function table(): string
    {
        return 'hcloud_iso';
    }

    public static function row(Payload $payload): array
    {
        return [
            'id' => $payload->int('id'),
            'name' => $payload->str('name'),
            'description' => $payload->str('description'),
            'type' => $payload->str('type'),
            'architecture' => $payload->str('architecture'),
        ] + $payload->deprecation();
    }
}
