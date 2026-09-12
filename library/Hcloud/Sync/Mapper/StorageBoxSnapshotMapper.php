<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync\Mapper;

use Icinga\Module\Hcloud\Api\Client;
use Icinga\Module\Hcloud\Sync\Payload;

final class StorageBoxSnapshotMapper extends Mapper
{
    public static function resource(): string
    {
        return 'storage_box_snapshots';
    }

    public static function path(): string
    {
        return '/storage_boxes/{id}/snapshots';
    }

    public static function table(): string
    {
        return 'hcloud_storage_box_snapshot';
    }

    public static function key(): string
    {
        return 'snapshots';
    }

    public static function baseUri(): string
    {
        return Client::STORAGE_BASE_URI;
    }

    public static function row(Payload $payload): array
    {
        return [
            'storage_box_id' => $payload->int('storage_box'),
            'id' => $payload->int('id'),
            'name' => $payload->str('name'),
            'description' => $payload->str('description'),
            'is_automatic' => $payload->flag('is_automatic'),
            'created' => $payload->time('created'),
            'stats_size' => $payload->int('stats.size'),
            'stats_size_filesystem' => $payload->int('stats.size_filesystem'),
            'labels' => $payload->json('labels'),
        ];
    }
}
