<?php

declare(strict_types=1);

namespace Tests\Icinga\Module\Hcloud\Sync;

use Icinga\Module\Hcloud\Sync\Mapper\Mapper;
use Icinga\Module\Hcloud\Sync\MapperRegistry;
use Icinga\Module\Hcloud\Sync\Payload;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Icinga\Module\Hcloud\Lib\SchemaReader;

final class MapperSchemaTest extends TestCase
{
    private const MODULE_DIR = __DIR__ . '/../../..';

    /**
     * @return list<array{class-string<Mapper>}>
     */
    public static function mapperProvider(): array
    {
        return array_map(static fn (string $class): array => [$class], MapperRegistry::all());
    }

    /**
     * @param class-string<Mapper> $mapper
     */
    #[DataProvider('mapperProvider')]
    public function testTheTargetTableExists(string $mapper): void
    {
        $this->assertArrayHasKey(
            $mapper::table(),
            self::tables(),
            $mapper . ' targets a table that is not in the schema.'
        );
    }

    /**
     * @param class-string<Mapper> $mapper
     */
    #[DataProvider('mapperProvider')]
    public function testEveryChildTableExists(string $mapper): void
    {
        $childTables = $mapper::childTables();

        $this->assertSame(
            array_values(array_unique($childTables)),
            $childTables,
            $mapper . ' declares the same child table more than once.'
        );

        foreach ($childTables as $table) {
            $this->assertArrayHasKey(
                $table,
                self::tables(),
                $mapper . ' declares child table ' . $table . ' which is not in the schema.'
            );
        }
    }

    /**
     * @param class-string<Mapper> $mapper
     */
    #[DataProvider('mapperProvider')]
    public function testEveryEmittedColumnExistsInTheSchema(string $mapper): void
    {
        $payload = self::fixtureFor($mapper);
        if ($payload === null) {
            $this->markTestSkipped('No fixture for ' . $mapper);
        }

        $tables = self::tables();

        $this->assertColumnsExist($mapper::table(), array_keys($mapper::row($payload)), $mapper, $tables);

        foreach ($mapper::children($payload) as $table => $rows) {
            foreach ($rows as $row) {
                $this->assertColumnsExist($table, array_keys($row), $mapper, $tables);
            }
        }
    }

    /**
     * @param class-string<Mapper> $mapper
     */
    #[DataProvider('mapperProvider')]
    public function testEveryDeclaredChildTableIsActuallyProduced(string $mapper): void
    {
        $payload = self::fixtureFor($mapper);
        if ($payload === null) {
            $this->markTestSkipped('No fixture for ' . $mapper);
        }

        $produced = array_keys($mapper::children($payload));
        $declared = $mapper::childTables();

        sort($produced);
        sort($declared);

        $this->assertSame(
            $declared,
            $produced,
            $mapper . ' declares child tables that do not match what children() returns.'
        );
    }

    /**
     * @param list<string> $columns
     * @param array<string, array{columns: array<string, string>, primary_key: list<string>}> $tables
     */
    private function assertColumnsExist(string $table, array $columns, string $mapper, array $tables): void
    {
        $this->assertArrayHasKey($table, $tables, $mapper . ' writes to unknown table ' . $table);

        foreach ($columns as $column) {
            $this->assertArrayHasKey(
                $column,
                $tables[$table]['columns'],
                sprintf('%s writes %s.%s which the schema does not define.', $mapper, $table, $column)
            );
        }
    }

    /**
     * @return array<string, array{columns: array<string, string>, primary_key: list<string>}>
     */
    private static function tables(): array
    {
        static $tables = null;
        $tables ??= SchemaReader::tables(self::MODULE_DIR . '/schema/mysql.sql', SchemaReader::MYSQL);

        return $tables;
    }

    /**
     * @param class-string<Mapper> $mapper
     */
    private static function fixtureFor(string $mapper): ?Payload
    {
        $map = [
            'locations' => ['locations', 'locations'],
            'server_types' => ['server_types', 'server_types'],
            'load_balancer_types' => ['load_balancer_types', 'load_balancer_types'],
            'storage_box_types' => ['storage_box_types', 'storage_box_types'],
            'images' => ['images', 'images'],
            'isos' => ['isos', 'isos'],
            'placement_groups' => ['placement_groups', 'placement_groups'],
            'ssh_keys' => ['ssh_keys', 'ssh_keys'],
            'networks' => ['networks', 'networks'],
            'firewalls' => ['firewalls', 'firewalls'],
            'certificates' => ['certificates', 'certificates'],
            'volumes' => ['volumes', 'volumes'],
            'load_balancers' => ['load_balancers', 'load_balancers'],
            'servers' => ['servers', 'servers'],
            'floating_ips' => ['floating_ips', 'floating_ips'],
            'primary_ips' => ['primary_ips', 'primary_ips'],
            'zones' => ['zones', 'zones'],
            'storage_boxes' => ['storage_boxes', 'storage_boxes'],
            'actions' => ['server_actions', 'actions'],
            'zone_rrsets' => ['zone_rrsets', 'rrsets'],
            'storage_box_subaccounts' => ['storage_box_subaccounts', 'subaccounts'],
            'storage_box_snapshots' => ['storage_box_snapshots', 'snapshots'],
        ];

        $resource = $mapper::resource();
        if (! isset($map[$resource])) {
            return null;
        }

        [$file, $key] = $map[$resource];
        $path = self::MODULE_DIR . '/dev/mock/' . $file . '.json';
        if (! is_file($path)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded) || ! isset($decoded[$key][0]) || ! is_array($decoded[$key][0])) {
            return null;
        }

        /** @var array<string, mixed> $entry */
        $entry = $decoded[$key][0];

        return new Payload($entry);
    }
}
