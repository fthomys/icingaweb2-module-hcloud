<?php

declare(strict_types=1);

namespace Tests\Icinga\Module\Hcloud\Model;

use Icinga\Module\Hcloud\Model\HcloudModel;
use ipl\Orm\Model;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Icinga\Module\Hcloud\Lib\SchemaReader;

final class ModelSchemaTest extends TestCase
{
    private const MODULE_DIR = __DIR__ . '/../../..';

    /**
     * @return list<array{class-string<Model>}>
     */
    public static function modelProvider(): array
    {
        $models = [];

        foreach ((array) scandir(self::MODULE_DIR . '/library/Hcloud/Model') as $entry) {
            if (! is_string($entry) || ! str_ends_with($entry, '.php')) {
                continue;
            }

            $class = 'Icinga\\Module\\Hcloud\\Model\\' . basename($entry, '.php');
            if (! class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);
            if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Model::class)) {
                continue;
            }

            $models[] = [$class];
        }

        return $models;
    }

    /**
     * @param class-string<Model> $class
     */
    #[DataProvider('modelProvider')]
    public function testTheModelTableExists(string $class): void
    {
        $model = new $class();

        $this->assertArrayHasKey(
            $model->getTableName(),
            self::tables(),
            $class . ' maps to a table the schema does not define.'
        );
    }

    /**
     * @param class-string<Model> $class
     */
    #[DataProvider('modelProvider')]
    public function testModelColumnsMatchTheSchemaExactly(string $class): void
    {
        $model = new $class();
        $table = $model->getTableName();
        $tables = self::tables();

        $this->assertArrayHasKey($table, $tables);

        $declared = array_merge((array) $model->getKeyName(), $model->getColumns());
        sort($declared);

        $actual = array_keys($tables[$table]['columns']);
        sort($actual);

        $this->assertSame(
            $actual,
            $declared,
            sprintf('%s does not declare exactly the columns of %s.', $class, $table)
        );
    }

    /**
     * @param class-string<Model> $class
     */
    #[DataProvider('modelProvider')]
    public function testTheModelKeyMatchesThePrimaryKey(string $class): void
    {
        $model = new $class();
        $table = $model->getTableName();

        $declared = (array) $model->getKeyName();
        sort($declared);

        $actual = self::tables()[$table]['primary_key'];
        sort($actual);

        $this->assertSame($actual, $declared, $class . ' declares a key other than the primary key.');
    }

    /**
     * @param class-string<Model> $class
     */
    #[DataProvider('modelProvider')]
    public function testEveryColumnIsLabelled(string $class): void
    {
        $model = new $class();
        $definitions = $model->getColumnDefinitions();

        $labelled = array_keys($definitions);

        foreach ($model->getColumns() as $column) {
            $this->assertContains(
                $column,
                $labelled,
                sprintf('%s does not label column %s.', $class, (string) $column)
            );
        }

        $tables = self::tables();
        $columns = array_keys($tables[$model->getTableName()]['columns']);

        foreach ($labelled as $column) {
            $this->assertContains(
                $column,
                $columns,
                sprintf('%s labels %s, which is not a column of %s.', $class, $column, $model->getTableName())
            );
        }

        foreach ($definitions as $column => $label) {
            $this->assertNotSame('', $label, sprintf('%s labels %s with an empty string.', $class, $column));
        }
    }

    /**
     * @param class-string<Model> $class
     */
    #[DataProvider('modelProvider')]
    public function testProjectScopedModelsShareTheCompositeKey(string $class): void
    {
        $model = new $class();

        if (! $model instanceof HcloudModel) {
            $this->assertTrue(true, 'Not a project scoped model.');

            return;
        }

        $this->assertSame(['project_id', 'id'], $model->getKeyName());
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
}
