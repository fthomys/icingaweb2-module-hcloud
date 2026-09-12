<?php

declare(strict_types=1);

namespace Tests\Icinga\Module\Hcloud\Sync;

use Icinga\Module\Hcloud\Sync\Mapper\ServerMapper;
use Icinga\Module\Hcloud\Sync\Payload;
use PHPUnit\Framework\TestCase;

final class ServerMapperTest extends TestCase
{
    private const FIXTURE = __DIR__ . '/../../../dev/mock/servers.json';

    public function testScalarFieldsAreMapped(): void
    {
        $row = ServerMapper::row(self::server());

        $this->assertSame(42, $row['id']);
        $this->assertSame('my-resource', $row['name']);
        $this->assertSame('running', $row['status']);
        $this->assertSame('2016-01-30 23:55:00', $row['created']);
        $this->assertSame(0, $row['locked']);
        $this->assertSame(1, $row['server_type_id']);
        $this->assertSame(42, $row['location_id']);
    }

    public function testTimestampsAreStoredAsUtcWithoutTimezoneSuffix(): void
    {
        $row = ServerMapper::row(new Payload(['id' => 1, 'created' => '2026-03-01T12:30:00+02:00']));

        $this->assertSame('2026-03-01 10:30:00', $row['created']);
    }

    public function testTheIpv4PtrIsStoredAsASingleRow(): void
    {
        $ptrs = self::children()['hcloud_server_public_ip_dns_ptr'];
        $ipv4 = array_values(array_filter($ptrs, static fn (array $r): bool => $r['family'] === 'ipv4'));

        $this->assertCount(1, $ipv4);
        $this->assertSame('1.2.3.4', $ipv4[0]['ip']);
        $this->assertSame('server01.example.com', $ipv4[0]['dns_ptr']);
    }

    public function testTheIpv6PtrListIsExpandedPerAddress(): void
    {
        $ptrs = self::children()['hcloud_server_public_ip_dns_ptr'];
        $ipv6 = array_values(array_filter($ptrs, static fn (array $r): bool => $r['family'] === 'ipv6'));

        $this->assertCount(1, $ipv6);
        $this->assertSame('2001:db8::1', $ipv6[0]['ip']);
        $this->assertSame('server.example.com', $ipv6[0]['dns_ptr']);
    }

    public function testBothAddressFamiliesAreRecorded(): void
    {
        $ips = self::children()['hcloud_server_public_ip'];

        $this->assertSame(['ipv4', 'ipv6'], array_column($ips, 'family'));
        $this->assertSame('2001:db8::/64', $ips[1]['ip']);
        $this->assertSame(0, $ips[0]['blocked']);
    }

    public function testFirewallBindingsKeepTheirStatus(): void
    {
        $firewalls = self::children()['hcloud_server_firewall'];

        $this->assertCount(1, $firewalls);
        $this->assertSame(42, $firewalls[0]['firewall_id']);
        $this->assertSame('applied', $firewalls[0]['status']);
    }

    public function testPrivateNetworksAndAliasIpsAreSplitIntoRows(): void
    {
        $children = self::children();

        $this->assertCount(1, $children['hcloud_server_private_net']);
        $this->assertSame(4711, $children['hcloud_server_private_net'][0]['network_id']);
        $this->assertSame('10.0.0.2', $children['hcloud_server_private_net'][0]['ip']);

        $this->assertCount(1, $children['hcloud_server_private_net_alias_ip']);
        $this->assertSame(4711, $children['hcloud_server_private_net_alias_ip'][0]['network_id']);
    }

    public function testAServerWithoutPublicNetProducesNoIpRows(): void
    {
        $children = ServerMapper::children(new Payload(['id' => 7]));

        $this->assertSame([], $children['hcloud_server_public_ip']);
        $this->assertSame([], $children['hcloud_server_public_ip_dns_ptr']);
        $this->assertSame([], $children['hcloud_server_firewall']);
    }

    private static function server(): Payload
    {
        $decoded = json_decode((string) file_get_contents(self::FIXTURE), true);

        /** @var array<string, mixed> $entry */
        $entry = $decoded['servers'][0];

        return new Payload($entry);
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private static function children(): array
    {
        return ServerMapper::children(self::server());
    }
}
