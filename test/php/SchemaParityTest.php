<?php

declare(strict_types=1);

namespace Tests\Icinga\Module\Hcloud;

use PHPUnit\Framework\TestCase;
use Tests\Icinga\Module\Hcloud\Lib\SchemaReader;

final class SchemaParityTest extends TestCase
{
    private const MODULE_DIR = __DIR__ . '/../..';

    public function testBothBaselinesDefineTheSameTables(): void
    {
        $mysql = array_keys(SchemaReader::tables(self::mysqlPath(), SchemaReader::MYSQL));
        $pgsql = array_keys(SchemaReader::tables(self::pgsqlPath(), SchemaReader::PGSQL));

        sort($mysql);
        sort($pgsql);

        $this->assertSame($pgsql, $mysql, 'The MySQL and PostgreSQL baselines define different tables.');
    }

    public function testBothBaselinesDefineTheSameColumns(): void
    {
        $mysql = SchemaReader::tables(self::mysqlPath(), SchemaReader::MYSQL);
        $pgsql = SchemaReader::tables(self::pgsqlPath(), SchemaReader::PGSQL);

        foreach ($mysql as $table => $definition) {
            $this->assertArrayHasKey(
                $table,
                $pgsql,
                sprintf('Table %s is missing from the PostgreSQL baseline.', $table)
            );

            $this->assertSame(
                array_keys($definition['columns']),
                array_keys($pgsql[$table]['columns']),
                sprintf('Table %s has different columns in the two baselines.', $table)
            );

            foreach ($definition['columns'] as $column => $type) {
                $this->assertSame(
                    $type,
                    $pgsql[$table]['columns'][$column],
                    sprintf('Column %s.%s differs between the two baselines.', $table, $column)
                );
            }
        }
    }

    public function testBothBaselinesDefineTheSamePrimaryKeys(): void
    {
        $mysql = SchemaReader::tables(self::mysqlPath(), SchemaReader::MYSQL);
        $pgsql = SchemaReader::tables(self::pgsqlPath(), SchemaReader::PGSQL);

        foreach ($mysql as $table => $definition) {
            $this->assertSame(
                $definition['primary_key'],
                $pgsql[$table]['primary_key'],
                sprintf('Table %s has a different primary key in the two baselines.', $table)
            );
        }
    }

    public function testBothBaselinesDefineTheSameIndexes(): void
    {
        $mysql = SchemaReader::indexes(self::mysqlPath(), SchemaReader::MYSQL);
        $pgsql = SchemaReader::indexes(self::pgsqlPath(), SchemaReader::PGSQL);

        ksort($mysql);
        ksort($pgsql);

        $this->assertSame($pgsql, $mysql, 'The MySQL and PostgreSQL baselines define different indexes.');
    }

    public function testEveryTableIsPrefixed(): void
    {
        foreach (array_keys(SchemaReader::tables(self::mysqlPath(), SchemaReader::MYSQL)) as $table) {
            $this->assertStringStartsWith('hcloud_', $table, sprintf('Table %s is missing the module prefix.', $table));
        }
    }

    public function testEveryTableHasAPrimaryKey(): void
    {
        foreach (SchemaReader::tables(self::mysqlPath(), SchemaReader::MYSQL) as $table => $definition) {
            $this->assertNotEmpty($definition['primary_key'], sprintf('Table %s has no primary key.', $table));
        }
    }

    private static function mysqlPath(): string
    {
        return self::MODULE_DIR . '/schema/mysql.sql';
    }

    private static function pgsqlPath(): string
    {
        return self::MODULE_DIR . '/schema/pgsql.sql';
    }
}
