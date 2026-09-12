<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync\Mapper;

use Icinga\Module\Hcloud\Sync\Payload;

final class FirewallMapper extends Mapper
{
    public static function resource(): string
    {
        return 'firewalls';
    }

    public static function path(): string
    {
        return '/firewalls';
    }

    public static function table(): string
    {
        return 'hcloud_firewall';
    }

    public static function childTables(): array
    {
        return ['hcloud_firewall_rule', 'hcloud_firewall_applied_to', 'hcloud_firewall_applied_resource'];
    }

    public static function row(Payload $payload): array
    {
        return [
            'id' => $payload->int('id'),
            'name' => $payload->str('name'),
            'created' => $payload->time('created'),
            'labels' => $payload->json('labels'),
        ];
    }

    public static function children(Payload $payload): array
    {
        $firewallId = $payload->int('id');
        $rules = [];
        $appliedTo = [];
        $appliedResources = [];

        foreach ($payload->each('rules') as $index => $rule) {
            $rules[] = [
                'firewall_id' => $firewallId,
                'rule_index' => $index,
                'direction' => $rule->str('direction') ?? 'in',
                'protocol' => $rule->str('protocol') ?? 'tcp',
                'port' => $rule->str('port'),
                'description' => $rule->str('description'),
                'source_ips' => $rule->json('source_ips'),
                'destination_ips' => $rule->json('destination_ips'),
            ];
        }

        foreach ($payload->each('applied_to') as $index => $applied) {
            $appliedTo[] = [
                'firewall_id' => $firewallId,
                'applied_index' => $index,
                'type' => $applied->str('type') ?? 'server',
                'server_id' => $applied->int('server.id'),
                'label_selector' => $applied->str('label_selector.selector'),
            ];

            foreach ($applied->each('applied_to_resources') as $resource) {
                $serverId = $resource->int('server.id');
                if ($serverId === null) {
                    continue;
                }

                $appliedResources[] = [
                    'firewall_id' => $firewallId,
                    'applied_index' => $index,
                    'resource_type' => $resource->str('type') ?? 'server',
                    'server_id' => $serverId,
                ];
            }
        }

        return [
            'hcloud_firewall_rule' => $rules,
            'hcloud_firewall_applied_to' => $appliedTo,
            'hcloud_firewall_applied_resource' => $appliedResources,
        ];
    }
}
