<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync\Mapper;

use Icinga\Module\Hcloud\Sync\Payload;

final class ActionMapper extends Mapper
{
    public static function resource(): string
    {
        return 'actions';
    }

    public static function path(): string
    {
        return '/servers/actions';
    }

    public static function table(): string
    {
        return 'hcloud_action';
    }

    public static function key(): string
    {
        return 'actions';
    }

    public static function childTables(): array
    {
        return ['hcloud_action_resource'];
    }

    public static function row(Payload $payload): array
    {
        return [
            'id' => $payload->int('id'),
            'command' => $payload->str('command') ?? 'unknown',
            'status' => $payload->str('status') ?? 'running',
            'progress' => $payload->int('progress'),
            'started' => $payload->time('started'),
            'finished' => $payload->time('finished'),
            'error_code' => $payload->str('error.code'),
            'error_message' => $payload->str('error.message'),
        ];
    }

    public static function children(Payload $payload): array
    {
        $actionId = $payload->int('id');
        $rows = [];

        foreach ($payload->each('resources') as $resource) {
            $resourceId = $resource->int('id');
            if ($resourceId === null) {
                continue;
            }

            $rows[] = [
                'action_id' => $actionId,
                'resource_type' => $resource->str('type') ?? 'unknown',
                'resource_id' => $resourceId,
            ];
        }

        return ['hcloud_action_resource' => $rows];
    }
}
