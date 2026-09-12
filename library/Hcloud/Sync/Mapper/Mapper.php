<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync\Mapper;

use Icinga\Module\Hcloud\Api\Client;
use Icinga\Module\Hcloud\Sync\Payload;

abstract class Mapper
{
    abstract public static function resource(): string;

    abstract public static function path(): string;

    abstract public static function table(): string;

    /**
     * @return array<string, mixed>
     */
    abstract public static function row(Payload $payload): array;

    public static function key(): string
    {
        return Client::collectionKey(static::path());
    }

    public static function baseUri(): string
    {
        return Client::CLOUD_BASE_URI;
    }

    /**
     * @return list<string>
     */
    public static function childTables(): array
    {
        return [];
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public static function children(Payload $payload): array
    {
        return [];
    }
}
