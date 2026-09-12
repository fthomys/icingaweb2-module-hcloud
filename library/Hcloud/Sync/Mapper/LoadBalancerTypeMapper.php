<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync\Mapper;

use Icinga\Module\Hcloud\Sync\Payload;

final class LoadBalancerTypeMapper extends Mapper
{
    public static function resource(): string
    {
        return 'load_balancer_types';
    }

    public static function path(): string
    {
        return '/load_balancer_types';
    }

    public static function table(): string
    {
        return 'hcloud_load_balancer_type';
    }

    public static function row(Payload $payload): array
    {
        return [
            'id' => $payload->int('id'),
            'name' => $payload->str('name'),
            'description' => $payload->str('description'),
            'max_connections' => $payload->int('max_connections'),
            'max_services' => $payload->int('max_services'),
            'max_targets' => $payload->int('max_targets'),
            'max_assigned_certificates' => $payload->int('max_assigned_certificates'),
        ] + $payload->deprecation();
    }
}
