<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync\Mapper;

use Icinga\Module\Hcloud\Sync\Payload;

final class LoadBalancerMapper extends Mapper
{
    public static function resource(): string
    {
        return 'load_balancers';
    }

    public static function path(): string
    {
        return '/load_balancers';
    }

    public static function table(): string
    {
        return 'hcloud_load_balancer';
    }

    public static function childTables(): array
    {
        return [
            'hcloud_load_balancer_service',
            'hcloud_load_balancer_target',
            'hcloud_load_balancer_target_health',
            'hcloud_load_balancer_private_net',
        ];
    }

    public static function row(Payload $payload): array
    {
        return [
            'id' => $payload->int('id'),
            'name' => $payload->str('name'),
            'created' => $payload->time('created'),
            'algorithm_type' => $payload->str('algorithm.type'),
            'outgoing_traffic' => $payload->int('outgoing_traffic'),
            'ingoing_traffic' => $payload->int('ingoing_traffic'),
            'included_traffic' => $payload->int('included_traffic'),
            'location_id' => $payload->int('location.id'),
            'load_balancer_type_id' => $payload->int('load_balancer_type.id'),
            'public_enabled' => $payload->flag('public_net.enabled'),
            'public_ipv4' => $payload->str('public_net.ipv4.ip'),
            'public_ipv4_dns_ptr' => $payload->str('public_net.ipv4.dns_ptr'),
            'public_ipv6' => $payload->str('public_net.ipv6.ip'),
            'public_ipv6_dns_ptr' => $payload->str('public_net.ipv6.dns_ptr'),
            'protection_delete' => $payload->flag('protection.delete'),
            'labels' => $payload->json('labels'),
        ];
    }

    public static function children(Payload $payload): array
    {
        $loadBalancerId = $payload->int('id');

        $services = [];
        foreach ($payload->each('services') as $service) {
            $listenPort = $service->int('listen_port');
            if ($listenPort === null) {
                continue;
            }

            $services[] = [
                'load_balancer_id' => $loadBalancerId,
                'listen_port' => $listenPort,
                'protocol' => $service->str('protocol') ?? 'tcp',
                'destination_port' => $service->int('destination_port'),
                'proxyprotocol' => $service->flag('proxyprotocol'),
                'health_check_protocol' => $service->str('health_check.protocol'),
                'health_check_port' => $service->int('health_check.port'),
                'health_check_interval' => $service->int('health_check.interval'),
                'health_check_timeout' => $service->int('health_check.timeout'),
                'health_check_retries' => $service->int('health_check.retries'),
                'health_check_http_domain' => $service->str('health_check.http.domain'),
                'health_check_http_path' => $service->str('health_check.http.path'),
                'health_check_http_response' => $service->str('health_check.http.response'),
                'health_check_http_status_codes' => $service->json('health_check.http.status_codes'),
                'health_check_http_tls' => $service->flag('health_check.http.tls'),
                'http_cookie_name' => $service->str('http.cookie_name'),
                'http_cookie_lifetime' => $service->int('http.cookie_lifetime'),
                'http_timeout_idle' => $service->int('http.timeout_idle'),
                'http_sticky_sessions' => $service->flag('http.sticky_sessions'),
                'http_redirect_http' => $service->flag('http.redirect_http'),
                'http_certificates' => $service->json('http.certificates'),
            ];
        }

        $targets = [];
        $health = [];
        $index = 0;

        foreach ($payload->each('targets') as $target) {
            $index = self::collectTarget($loadBalancerId, $target, null, $index, $targets, $health);
        }

        $privateNets = [];
        foreach ($payload->each('private_net') as $privateNet) {
            $networkId = $privateNet->int('network');
            if ($networkId === null) {
                continue;
            }

            $privateNets[] = [
                'load_balancer_id' => $loadBalancerId,
                'network_id' => $networkId,
                'ip' => $privateNet->str('ip'),
            ];
        }

        return [
            'hcloud_load_balancer_service' => $services,
            'hcloud_load_balancer_target' => $targets,
            'hcloud_load_balancer_target_health' => $health,
            'hcloud_load_balancer_private_net' => $privateNets,
        ];
    }

    /**
     * @param list<array<string, mixed>> $targets
     * @param list<array<string, mixed>> $health
     */
    private static function collectTarget(
        ?int $loadBalancerId,
        Payload $target,
        ?int $parentIndex,
        int $index,
        array &$targets,
        array &$health
    ): int {
        $ownIndex = $index;

        $targets[] = [
            'load_balancer_id' => $loadBalancerId,
            'target_index' => $ownIndex,
            'parent_index' => $parentIndex,
            'type' => $target->str('type') ?? 'server',
            'server_id' => $target->int('server.id'),
            'server_ip' => $target->str('server.ip'),
            'ip_address' => $target->str('ip.ip'),
            'label_selector' => $target->str('label_selector.selector'),
            'use_private_ip' => $target->flag('use_private_ip'),
        ];

        foreach ($target->each('health_status') as $status) {
            $listenPort = $status->int('listen_port');
            if ($listenPort === null) {
                continue;
            }

            $health[] = [
                'load_balancer_id' => $loadBalancerId,
                'target_index' => $ownIndex,
                'listen_port' => $listenPort,
                'status' => $status->str('status') ?? 'unknown',
                'detail' => $status->str('detail'),
                'http_status_code' => $status->int('http_status_code'),
            ];
        }

        $index++;

        foreach ($target->each('targets') as $resolved) {
            $index = self::collectTarget($loadBalancerId, $resolved, $ownIndex, $index, $targets, $health);
        }

        return $index;
    }
}
