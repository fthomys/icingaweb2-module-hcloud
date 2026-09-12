<?php

declare(strict_types=1);

namespace Tests\Icinga\Module\Hcloud\Web;

use Icinga\Module\Hcloud\Web\ReferenceResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Icinga\Module\Hcloud\Lib\SchemaReader;

/**
 * The reference map is plain strings, so a renamed table or display column would only surface
 * as a detail view quietly falling back to the raw id.
 */
final class ReferenceResolverTest extends TestCase
{
    private const MODULE_DIR = __DIR__ . '/../../..';

    /**
     * @return list<array{string, string, list<string>}>
     */
    public static function targetProvider(): array
    {
        $targets = [];

        foreach (ReferenceResolver::targets() as $column => [$table, $displayColumns]) {
            $targets[] = [$column, $table, $displayColumns];
        }

        return $targets;
    }

    /**
     * @param list<string> $displayColumns
     */
    #[DataProvider('targetProvider')]
    public function testEveryTargetTableCarriesItsDisplayColumns(
        string $column,
        string $table,
        array $displayColumns
    ): void {
        $tables = SchemaReader::tables(self::MODULE_DIR . '/schema/mysql.sql', SchemaReader::MYSQL);

        $this->assertArrayHasKey($table, $tables, sprintf('%s points at a table that does not exist', $column));

        foreach ($displayColumns as $displayColumn) {
            $this->assertArrayHasKey(
                $displayColumn,
                $tables[$table]['columns'],
                sprintf('%s.%s is missing', $table, $displayColumn)
            );
        }

        $this->assertArrayHasKey('project_id', $tables[$table]['columns']);
        $this->assertArrayHasKey('id', $tables[$table]['columns']);
    }

    /**
     * @return list<array{string}>
     */
    public static function referencedColumnProvider(): array
    {
        return array_map(
            static fn (string $column): array => [$column],
            array_keys(ReferenceResolver::targets())
        );
    }

    #[DataProvider('referencedColumnProvider')]
    public function testEveryReferencedColumnExistsSomewhereInTheSchema(string $column): void
    {
        $tables = SchemaReader::tables(self::MODULE_DIR . '/schema/mysql.sql', SchemaReader::MYSQL);

        foreach ($tables as $definition) {
            if (isset($definition['columns'][$column])) {
                $this->assertTrue(true);

                return;
            }
        }

        $this->fail(sprintf('No table declares %s, so the reference can never resolve', $column));
    }

    /**
     * @return list<array{string, string}>
     */
    public static function routeProvider(): array
    {
        $routes = [];

        foreach (ReferenceResolver::routes() as $table => $route) {
            $routes[] = [$table, $route];
        }

        return $routes;
    }

    #[DataProvider('routeProvider')]
    public function testEveryRouteHasAController(string $table, string $route): void
    {
        $action = substr($route, strlen('hcloud/'));
        $controller = str_replace(' ', '', ucwords(str_replace('-', ' ', $action))) . 'Controller';

        $this->assertFileExists(
            self::MODULE_DIR . '/application/controllers/' . $controller . '.php',
            sprintf('%s links to %s, which has no controller', $table, $route)
        );
    }

    public function testEveryTargetTableWithADetailViewIsLinked(): void
    {
        $linked = array_keys(ReferenceResolver::routes());

        foreach (ReferenceResolver::targets() as $column => [$table]) {
            if (in_array($table, $linked, true)) {
                continue;
            }

            $this->assertFalse(
                is_file(self::MODULE_DIR . '/application/controllers/' . self::controllerFor($table) . '.php'),
                sprintf('%s could link to the %s detail view but does not', $column, $table)
            );
        }
    }

    private static function controllerFor(string $table): string
    {
        $name = substr($table, strlen('hcloud_'));

        return str_replace(' ', '', ucwords(str_replace('_', ' ', $name))) . 'Controller';
    }
}
