<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync\Mapper;

use Icinga\Module\Hcloud\Api\Client;
use Icinga\Module\Hcloud\Sync\Payload;

final class StorageBoxSubaccountMapper extends Mapper
{
    public static function resource(): string
    {
        return 'storage_box_subaccounts';
    }

    public static function path(): string
    {
        return '/storage_boxes/{id}/subaccounts';
    }

    public static function table(): string
    {
        return 'hcloud_storage_box_subaccount';
    }

    public static function key(): string
    {
        return 'subaccounts';
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
            'username' => $payload->str('username'),
            'home_directory' => $payload->str('home_directory'),
            'description' => $payload->str('description'),
            'server' => $payload->str('server'),
            'created' => $payload->time('created'),
            'access_reachable_externally' => $payload->flag('access_settings.reachable_externally'),
            'access_samba_enabled' => $payload->flag('access_settings.samba_enabled'),
            'access_ssh_enabled' => $payload->flag('access_settings.ssh_enabled'),
            'access_webdav_enabled' => $payload->flag('access_settings.webdav_enabled'),
            'access_readonly' => $payload->flag('access_settings.readonly'),
            'labels' => $payload->json('labels'),
        ];
    }
}
