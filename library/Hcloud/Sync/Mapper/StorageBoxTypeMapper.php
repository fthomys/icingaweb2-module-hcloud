<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync\Mapper;

use Icinga\Module\Hcloud\Api\Client;
use Icinga\Module\Hcloud\Sync\Payload;
use Icinga\Module\Hcloud\Sync\PricingMapper;

final class StorageBoxTypeMapper extends Mapper
{
    public static function resource(): string
    {
        return 'storage_box_types';
    }

    public static function path(): string
    {
        return '/storage_box_types';
    }

    public static function table(): string
    {
        return 'hcloud_storage_box_type';
    }

    public static function baseUri(): string
    {
        return Client::STORAGE_BASE_URI;
    }

    /**
     * @return list<string>
     */
    public static function childTables(): array
    {
        return ['hcloud_pricing_storage_box_type'];
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public static function children(Payload $payload): array
    {
        return ['hcloud_pricing_storage_box_type' => PricingMapper::storageBoxTypeRows($payload->raw())];
    }

    public static function row(Payload $payload): array
    {
        return [
            'id' => $payload->int('id'),
            'name' => $payload->str('name'),
            'description' => $payload->str('description'),
            'size' => $payload->int('size'),
            'snapshot_limit' => $payload->int('snapshot_limit'),
            'automatic_snapshot_limit' => $payload->int('automatic_snapshot_limit'),
            'subaccounts_limit' => $payload->int('subaccounts_limit'),
        ] + $payload->deprecation();
    }
}
