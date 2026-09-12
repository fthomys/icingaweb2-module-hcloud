<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync\Mapper;

use Icinga\Module\Hcloud\Api\Client;
use Icinga\Module\Hcloud\Sync\Payload;

final class StorageBoxMapper extends Mapper
{
    public static function resource(): string
    {
        return 'storage_boxes';
    }

    public static function path(): string
    {
        return '/storage_boxes';
    }

    public static function table(): string
    {
        return 'hcloud_storage_box';
    }

    public static function baseUri(): string
    {
        return Client::STORAGE_BASE_URI;
    }

    public static function row(Payload $payload): array
    {
        return [
            'id' => $payload->int('id'),
            'name' => $payload->str('name'),
            'created' => $payload->time('created'),
            'status' => $payload->str('status'),
            'username' => $payload->str('username'),
            'server' => $payload->str('server'),
            'system' => $payload->str('system'),
            'location_id' => $payload->int('location.id'),
            'storage_box_type_id' => $payload->int('storage_box_type.id'),
            'stats_size' => $payload->int('stats.size'),
            'stats_size_data' => $payload->int('stats.size_data'),
            'stats_size_snapshots' => $payload->int('stats.size_snapshots'),
            'access_reachable_externally' => $payload->flag('access_settings.reachable_externally'),
            'access_samba_enabled' => $payload->flag('access_settings.samba_enabled'),
            'access_ssh_enabled' => $payload->flag('access_settings.ssh_enabled'),
            'access_webdav_enabled' => $payload->flag('access_settings.webdav_enabled'),
            'access_zfs_enabled' => $payload->flag('access_settings.zfs_enabled'),
            'snapshot_plan_max_snapshots' => $payload->int('snapshot_plan.max_snapshots'),
            'snapshot_plan_minute' => $payload->int('snapshot_plan.minute'),
            'snapshot_plan_hour' => $payload->int('snapshot_plan.hour'),
            'snapshot_plan_day_of_week' => $payload->int('snapshot_plan.day_of_week'),
            'snapshot_plan_day_of_month' => $payload->int('snapshot_plan.day_of_month'),
            'protection_delete' => $payload->flag('protection.delete'),
            'labels' => $payload->json('labels'),
        ];
    }
}
