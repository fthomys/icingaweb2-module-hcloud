<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use ipl\Orm\Model;
use ReflectionClass;

/**
 * Table name to model lookup, so a raw ipl\Sql read can still use the translated column
 * labels a model declares instead of inventing English ones.
 */
final class ModelIndex
{
    /** @var array<string, class-string<Model>>|null */
    private static ?array $byTable = null;

    /**
     * @return array<string, class-string<Model>>
     */
    public static function byTable(): array
    {
        if (self::$byTable !== null) {
            return self::$byTable;
        }

        $index = [];

        foreach ((array) scandir(__DIR__) as $entry) {
            if (! is_string($entry) || ! str_ends_with($entry, '.php')) {
                continue;
            }

            $class = __NAMESPACE__ . '\\' . basename($entry, '.php');
            if (! class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);
            if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Model::class)) {
                continue;
            }

            $model = $reflection->newInstance();
            if (! $model instanceof Model) {
                continue;
            }

            $index[$model->getTableName()] = $reflection->getName();
        }

        self::$byTable = $index;

        return $index;
    }

    /**
     * Translated labels for the given columns of the given table.
     *
     * @param list<string> $columns
     *
     * @return array<string, string>
     */
    public static function labelsFor(string $table, array $columns): array
    {
        $class = self::byTable()[$table] ?? null;
        $definitions = [];

        if ($class !== null) {
            $model = new $class();
            $definitions = $model->getColumnDefinitions();
        }

        $labels = [];
        foreach ($columns as $column) {
            $labels[$column] = $definitions[$column] ?? ucwords(str_replace('_', ' ', $column));
        }

        return $labels;
    }
}
