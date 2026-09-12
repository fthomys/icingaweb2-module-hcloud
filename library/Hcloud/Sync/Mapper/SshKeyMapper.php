<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync\Mapper;

use Icinga\Module\Hcloud\Sync\Payload;

final class SshKeyMapper extends Mapper
{
    public static function resource(): string
    {
        return 'ssh_keys';
    }

    public static function path(): string
    {
        return '/ssh_keys';
    }

    public static function table(): string
    {
        return 'hcloud_ssh_key';
    }

    public static function row(Payload $payload): array
    {
        return [
            'id' => $payload->int('id'),
            'name' => $payload->str('name'),
            'fingerprint' => $payload->str('fingerprint'),
            'public_key' => $payload->str('public_key'),
            'created' => $payload->time('created'),
            'labels' => $payload->json('labels'),
        ];
    }
}
