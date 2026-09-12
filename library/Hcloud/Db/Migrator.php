<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Db;

use DirectoryIterator;
use ipl\Sql\Adapter\Pgsql;
use ipl\Sql\Connection;
use ipl\Sql\Select;
use RuntimeException;

final class Migrator
{
    public const MYSQL_UPGRADE_DIR = 'schema/mysql-upgrades';
    public const PGSQL_UPGRADE_DIR = 'schema/pgsql-upgrades';

    public function __construct(
        private readonly Connection $db,
        private readonly string $moduleDir
    ) {
    }

    public function currentVersion(): string
    {
        $select = (new Select())
            ->from('hcloud_schema')
            ->columns(['version'])
            ->where(["success = ?" => 'y'])
            ->orderBy('id', SORT_DESC)
            ->limit(1);

        $version = $this->db->fetchScalar($select);

        return is_string($version) && $version !== '' ? $version : '0.0.0';
    }

    public function upgradeDir(): string
    {
        $dir = $this->db->getAdapter() instanceof Pgsql ? self::PGSQL_UPGRADE_DIR : self::MYSQL_UPGRADE_DIR;

        return rtrim($this->moduleDir, '/') . '/' . $dir;
    }

    /**
     * @return array<string, string> version => script path, ascending
     */
    public function pending(): array
    {
        $current = $this->currentVersion();
        $dir = $this->upgradeDir();

        if (! is_dir($dir)) {
            return [];
        }

        $pending = [];

        foreach (new DirectoryIterator($dir) as $file) {
            if ($file->isDot() || ! $file->isFile()) {
                continue;
            }

            if (! preg_match('/^(?:v)?([^_]+)(?:_(\w+))?\.sql$/', $file->getFilename(), $matches)) {
                continue;
            }

            $version = $matches[1];
            if (version_compare($version, $current, '>')) {
                $pending[$version] = (string) $file->getRealPath();
            }
        }

        uksort($pending, static fn (string $a, string $b): int => version_compare($a, $b));

        return $pending;
    }

    public function apply(string $version, string $scriptPath): void
    {
        $statements = @file_get_contents($scriptPath);

        if ($statements === false || trim($statements) === '') {
            throw new RuntimeException(sprintf('Upgrade script %s is missing or empty.', $scriptPath));
        }

        $this->db->exec($statements);

        if ($this->currentVersion() !== $version) {
            throw new RuntimeException(sprintf(
                'Upgrade script %s did not record version %s in hcloud_schema.',
                basename($scriptPath),
                $version
            ));
        }
    }
}
