<?php

declare(strict_types=1);

namespace Tests\Icinga\Module\Hcloud\Model;

use ipl\Orm\Model;
use ipl\Orm\Relations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Icinga\Module\Hcloud\Lib\SchemaReader;

/**
 * ResourceController joins the project relation on every list view. A model that does not
 * declare it made ipl\Orm throw out of Resolver::resolveRelations(), which is how the sync
 * view died. Relation keys are just strings, so a typo only surfaces at runtime too.
 */
final class ModelRelationsTest extends TestCase
{
    private const MODULE_DIR = __DIR__ . '/../../..';

    /**
     * @return list<array{class-string<Model>}>
     */
    public static function modelProvider(): array
    {
        return ModelSchemaTest::modelProvider();
    }

    /**
     * @return list<array{class-string<Model>}>
     */
    public static function listedModelProvider(): array
    {
        $models = [];

        foreach (glob(self::MODULE_DIR . '/application/controllers/*Controller.php') ?: [] as $file) {
            $source = (string) file_get_contents($file);

            if (! preg_match('/extends\s+ResourceController\b/', $source)) {
                continue;
            }

            if (preg_match('/return\s+(\w+)::class;/', $source, $m)) {
                $class = 'Icinga\\Module\\Hcloud\\Model\\' . $m[1];
                if (class_exists($class)) {
                    $models[] = [$class];
                }
            }
        }

        return $models;
    }

    /**
     * @param class-string<Model> $class
     */
    #[DataProvider('listedModelProvider')]
    public function testEveryListedModelCanJoinItsProject(string $class): void
    {
        $this->assertTrue(
            self::relations($class)->has('project'),
            $class . ' backs a list view but declares no "project" relation to join.'
        );
    }

    /**
     * @param class-string<Model> $class
     */
    #[DataProvider('modelProvider')]
    public function testEveryRelationTargetsAnExistingModel(string $class): void
    {
        $relations = self::relations($class);
        $this->assertInstanceOf(Relations::class, $relations);

        foreach ($relations as $relation) {
            $target = $relation->getTargetClass();

            $this->assertTrue(
                class_exists($target),
                sprintf('%s relation "%s" targets missing class %s.', $class, $relation->getName(), $target)
            );
        }
    }

    /**
     * @param class-string<Model> $class
     */
    #[DataProvider('modelProvider')]
    public function testEveryRelationKeyIsARealColumn(string $class): void
    {
        $tables = SchemaReader::tables(self::MODULE_DIR . '/schema/mysql.sql', SchemaReader::MYSQL);
        $relations = self::relations($class);
        $this->assertInstanceOf(Relations::class, $relations);

        $model = new $class();

        foreach ($relations as $relation) {
            $targetClass = $relation->getTargetClass();
            if (! class_exists($targetClass)) {
                continue;
            }

            $target = new $targetClass();

            self::assertColumns(
                $this,
                $tables,
                $model->getTableName(),
                (array) ($relation->getCandidateKey() ?? []),
                sprintf('%s relation "%s" candidate key', $class, $relation->getName())
            );

            self::assertColumns(
                $this,
                $tables,
                $target->getTableName(),
                (array) ($relation->getForeignKey() ?? []),
                sprintf('%s relation "%s" foreign key', $class, $relation->getName())
            );
        }
    }

    /**
     * @param array<string, array{columns: array<string, string>, primary_key: list<string>}> $tables
     * @param array<int, mixed> $columns
     */
    private static function assertColumns(
        TestCase $test,
        array $tables,
        string $table,
        array $columns,
        string $what
    ): void {
        if ($columns === []) {
            $test->assertTrue(true, $what . ' uses the default key.');

            return;
        }

        foreach ($columns as $column) {
            $test->assertArrayHasKey(
                (string) $column,
                $tables[$table]['columns'] + array_flip($tables[$table]['primary_key']),
                sprintf('%s references %s.%s which the schema does not define.', $what, $table, (string) $column)
            );
        }
    }

    /**
     * @param class-string<Model> $class
     */
    private static function relations(string $class): Relations
    {
        $relations = new Relations();
        (new $class())->createRelations($relations);

        return $relations;
    }
}
