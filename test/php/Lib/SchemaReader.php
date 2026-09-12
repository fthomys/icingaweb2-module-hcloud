<?php

declare(strict_types=1);

namespace Tests\Icinga\Module\Hcloud\Lib;

final class SchemaReader
{
    public const MYSQL = 'mysql';
    public const PGSQL = 'pgsql';

    private const JSON_COLUMNS = [
        'labels',
        'health_check_http_status_codes',
        'http_certificates',
        'source_ips',
        'destination_ips',
    ];

    /**
     * @return array<string, array{columns: array<string, string>, primary_key: list<string>}>
     */
    public static function tables(string $path, string $dialect): array
    {
        $sql = (string) file_get_contents($path);
        $tables = [];

        if (! preg_match_all('/CREATE TABLE (\w+) \((.*?)\n\)/s', $sql, $matches, PREG_SET_ORDER)) {
            return $tables;
        }

        foreach ($matches as $match) {
            $columns = [];
            $primaryKey = [];

            foreach (self::splitTopLevel($match[2]) as $line) {
                if (preg_match('/^PRIMARY KEY \((.+)\)$/', $line, $pk)) {
                    $primaryKey = array_map('trim', explode(',', $pk[1]));
                    continue;
                }

                if (preg_match('/^(CONSTRAINT|KEY|UNIQUE|FOREIGN)\b/', $line)) {
                    continue;
                }

                if (preg_match('/^(\w+) (.+)$/', $line, $col)) {
                    $columns[$col[1]] = self::canonicalType($col[1], $col[2]);
                }
            }

            $tables[$match[1]] = ['columns' => $columns, 'primary_key' => $primaryKey];
        }

        return $tables;
    }

    /**
     * @return array<string, string>
     */
    public static function indexes(string $path, string $dialect): array
    {
        $sql = (string) file_get_contents($path);
        $indexes = [];

        if ($dialect === self::MYSQL) {
            if (preg_match_all('/CREATE TABLE (\w+) \((.*?)\n\)/s', $sql, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    foreach (self::splitTopLevel($match[2]) as $line) {
                        if (preg_match('/^KEY (\w+) \((.+)\)$/', $line, $key)) {
                            $indexes[$key[1]] = $match[1] . '(' . self::normaliseColumnList($key[2]) . ')';
                        }
                    }
                }
            }

            return $indexes;
        }

        if (preg_match_all('/CREATE INDEX (\w+) ON (\w+) \((.+?)\);/', $sql, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $indexes[$match[1]] = $match[2] . '(' . self::normaliseColumnList($match[3]) . ')';
            }
        }

        return $indexes;
    }

    private static function normaliseColumnList(string $columns): string
    {
        return implode(',', array_map('trim', explode(',', $columns)));
    }

    /**
     * @return list<string>
     */
    private static function splitTopLevel(string $body): array
    {
        $lines = [];
        $current = '';
        $depth = 0;

        foreach (str_split($body) as $char) {
            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
            }

            if ($char === ',' && $depth === 0) {
                $lines[] = self::squash($current);
                $current = '';
                continue;
            }

            $current .= $char;
        }

        if (self::squash($current) !== '') {
            $lines[] = self::squash($current);
        }

        return array_values(array_filter($lines, static fn (string $line): bool => $line !== ''));
    }

    private static function squash(string $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $value));
    }

    private static function canonicalType(string $column, string $definition): string
    {
        $definition = strtoupper($definition);

        if (str_contains($definition, 'AUTO_INCREMENT') || str_contains($definition, 'SERIAL')) {
            return str_contains($definition, 'BIG') ? 'bigserial' : 'serial';
        }

        $nullable = ! str_contains($definition, 'NOT NULL');
        $default = null;
        if (preg_match('/DEFAULT (\'[^\']*\'|[\w.]+)/', $definition, $match)) {
            $default = strtolower($match[1]);
        }

        $type = match (true) {
            (bool) preg_match('/^ENUM\(/', $definition) => 'varchar(1)',
            (bool) preg_match('/^VARCHAR\((\d+)\)/', $definition, $m) => 'varchar(' . $m[1] . ')',
            (bool) preg_match('/^DECIMAL\((\d+), ?(\d+)\)/', $definition, $m) => "decimal({$m[1]},{$m[2]})",
            in_array($column, self::JSON_COLUMNS, true) => 'json',
            str_starts_with($definition, 'JSONB') => 'json',
            str_starts_with($definition, 'TEXT') => 'text',
            str_starts_with($definition, 'BIGINT') => 'bigint',
            str_starts_with($definition, 'TINYINT'), str_starts_with($definition, 'SMALLINT') => 'smallint',
            str_starts_with($definition, 'INT') => 'int',
            str_starts_with($definition, 'DATETIME'), str_starts_with($definition, 'TIMESTAMP') => 'timestamp',
            default => 'unknown:' . $definition,
        };

        return sprintf('%s %s %s', $type, $nullable ? 'null' : 'notnull', $default ?? '-');
    }
}
