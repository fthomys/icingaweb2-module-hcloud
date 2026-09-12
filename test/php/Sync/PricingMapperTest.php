<?php

declare(strict_types=1);

namespace Tests\Icinga\Module\Hcloud\Sync;

use DateTimeImmutable;
use DateTimeZone;
use Icinga\Module\Hcloud\Sync\PricingMapper;
use PHPUnit\Framework\TestCase;
use Tests\Icinga\Module\Hcloud\Lib\SchemaReader;

final class PricingMapperTest extends TestCase
{
    private const MODULE_DIR = __DIR__ . '/../../..';

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private static function rows(): array
    {
        $payload = json_decode(
            (string) file_get_contents(self::MODULE_DIR . '/dev/mock/pricing.json'),
            true
        );

        /** @var array<string, mixed> $payload */
        return PricingMapper::rows(
            $payload,
            new DateTimeImmutable('2026-03-01T10:30:00', new DateTimeZone('UTC'))
        );
    }

    public function testTheSingletonRowCarriesCurrencyAndPerGigabytePrices(): void
    {
        $main = self::rows()['hcloud_pricing'][0];

        $this->assertSame('EUR', $main['currency']);
        $this->assertSame('19.00', $main['vat_rate']);
        $this->assertSame('1.0000', $main['volume_price_per_gb_month_net']);
        $this->assertSame('1.1900', $main['image_price_per_gb_month_gross']);
        $this->assertSame('20.00', $main['server_backup_percentage']);
        $this->assertSame('2026-03-01 10:30:00', $main['updated']);
    }

    public function testServerTypePricesAreFlattenedPerLocation(): void
    {
        $rows = self::rows()['hcloud_pricing_server_type'];

        $this->assertNotEmpty($rows);
        $this->assertSame(104, $rows[0]['server_type_id']);
        $this->assertSame('fsn1', $rows[0]['location_name']);
        $this->assertSame('cpx22', $rows[0]['name']);
        $this->assertSame('1.0000', $rows[0]['price_monthly_net']);
        $this->assertSame(654321, $rows[0]['included_traffic']);
        $this->assertSame('1.0000', $rows[0]['price_per_tb_traffic_net']);
    }

    public function testFloatingIpPricesHaveNoHourlyRate(): void
    {
        $floating = self::rows()['hcloud_pricing_floating_ip'][0];
        $primary = self::rows()['hcloud_pricing_primary_ip'][0];

        $this->assertArrayNotHasKey('price_hourly_net', $floating);
        $this->assertArrayHasKey('price_hourly_net', $primary);
        $this->assertSame('ipv4', $floating['type']);
    }

    public function testPricesStayStringsSoTheyNeverBecomeFloats(): void
    {
        foreach (self::rows() as $table => $rows) {
            foreach ($rows as $row) {
                foreach ($row as $column => $value) {
                    if (str_contains($column, 'price') || str_contains($column, 'fee')) {
                        $this->assertTrue(
                            $value === null || is_string($value),
                            sprintf('%s.%s must stay a string, got %s', $table, $column, get_debug_type($value))
                        );
                    }
                }
            }
        }
    }

    public function testEveryEmittedColumnExistsInTheSchema(): void
    {
        $tables = SchemaReader::tables(self::MODULE_DIR . '/schema/mysql.sql', SchemaReader::MYSQL);

        foreach (self::rows() as $table => $rows) {
            $this->assertArrayHasKey($table, $tables, $table . ' is not in the schema.');

            foreach ($rows as $row) {
                foreach (array_keys($row) as $column) {
                    $this->assertArrayHasKey(
                        $column,
                        $tables[$table]['columns'] + array_flip($tables[$table]['primary_key']),
                        sprintf('PricingMapper writes %s.%s which the schema does not define.', $table, $column)
                    );
                }
            }
        }
    }

    public function testEveryDeclaredTableIsProduced(): void
    {
        $produced = array_keys(self::rows());
        sort($produced);

        $declared = PricingMapper::TABLES;
        sort($declared);

        $this->assertSame($declared, $produced);
    }

    public function testStorageBoxTypePricesRideAlongWithTheType(): void
    {
        $payload = json_decode(
            (string) file_get_contents(self::MODULE_DIR . '/dev/mock/storage_box_types.json'),
            true
        );

        /** @var array<string, mixed> $type */
        $type = $payload['storage_box_types'][0];
        $rows = PricingMapper::storageBoxTypeRows($type);

        $this->assertNotEmpty($rows, 'Storage box types carry their prices inline.');
        $this->assertArrayHasKey('setup_fee_net', $rows[0]);
        $this->assertArrayHasKey('storage_box_type_id', $rows[0]);
    }
}
