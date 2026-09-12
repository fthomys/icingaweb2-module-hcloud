<?php

declare(strict_types=1);

namespace Tests\Icinga\Module\Hcloud\Sync;

use Icinga\Module\Hcloud\Sync\Mapper\LoadBalancerMapper;
use Icinga\Module\Hcloud\Sync\Payload;
use PHPUnit\Framework\TestCase;

final class LoadBalancerMapperTest extends TestCase
{
    /**
     * A label_selector target carries its resolved server targets one level down. Flattening has to
     * descend into targets[].targets[] or a label driven load balancer looks like it has no targets.
     */
    private const LABEL_SELECTOR_PAYLOAD = [
        'id' => 4711,
        'name' => 'lb',
        'targets' => [
            [
                'type' => 'label_selector',
                'label_selector' => ['selector' => 'env=prod'],
                'use_private_ip' => false,
                'targets' => [
                    [
                        'type' => 'server',
                        'server' => ['id' => 101, 'ip' => '10.0.0.1'],
                        'use_private_ip' => true,
                        'health_status' => [
                            ['listen_port' => 443, 'status' => 'healthy'],
                        ],
                    ],
                    [
                        'type' => 'server',
                        'server' => ['id' => 102, 'ip' => '10.0.0.2'],
                        'use_private_ip' => true,
                        'health_status' => [
                            [
                                'listen_port' => 443,
                                'status' => 'unhealthy',
                                'detail' => 'layer7_timeout',
                                'http_status_code' => 503,
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    public function testResolvedLabelSelectorTargetsAreFlattened(): void
    {
        $targets = self::children()['hcloud_load_balancer_target'];

        $this->assertCount(3, $targets, 'The selector itself plus its two resolved servers.');
        $this->assertSame('label_selector', $targets[0]['type']);
        $this->assertSame('env=prod', $targets[0]['label_selector']);
        $this->assertNull($targets[0]['parent_index']);

        $this->assertSame('server', $targets[1]['type']);
        $this->assertSame(101, $targets[1]['server_id']);
        $this->assertSame(0, $targets[1]['parent_index']);

        $this->assertSame(102, $targets[2]['server_id']);
        $this->assertSame(0, $targets[2]['parent_index']);
    }

    public function testTargetIndexesAreUniqueAcrossNestingLevels(): void
    {
        $indexes = array_column(self::children()['hcloud_load_balancer_target'], 'target_index');

        $this->assertSame([0, 1, 2], $indexes);
        $this->assertSame($indexes, array_unique($indexes));
    }

    public function testHealthStatusIsAttachedToTheResolvedTargetNotTheSelector(): void
    {
        $health = self::children()['hcloud_load_balancer_target_health'];

        $this->assertCount(2, $health);
        $this->assertSame(1, $health[0]['target_index']);
        $this->assertSame('healthy', $health[0]['status']);
        $this->assertSame(2, $health[1]['target_index']);
        $this->assertSame('unhealthy', $health[1]['status']);
    }

    public function testHealthCheckDetailAndHttpStatusAreKept(): void
    {
        $health = self::children()['hcloud_load_balancer_target_health'];

        $this->assertSame('layer7_timeout', $health[1]['detail']);
        $this->assertSame(503, $health[1]['http_status_code']);
        $this->assertNull($health[0]['detail']);
    }

    public function testPlainServerTargetsStayFlat(): void
    {
        $children = LoadBalancerMapper::children(new Payload([
            'id' => 1,
            'targets' => [
                ['type' => 'server', 'server' => ['id' => 5, 'ip' => '1.2.3.4']],
            ],
        ]));

        $targets = $children['hcloud_load_balancer_target'];

        $this->assertCount(1, $targets);
        $this->assertSame(5, $targets[0]['server_id']);
        $this->assertNull($targets[0]['parent_index']);
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private static function children(): array
    {
        return LoadBalancerMapper::children(new Payload(self::LABEL_SELECTOR_PAYLOAD));
    }
}
