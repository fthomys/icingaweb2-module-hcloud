<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\ProvidedHook;

use Icinga\Application\Hook\DbMigrationHook;
use Icinga\Module\Hcloud\Common\Database;
use Icinga\Module\Hcloud\Model\Schema;
use ipl\Orm\Query;
use ipl\Sql\Connection;

class DbMigration extends DbMigrationHook
{
    public const BASELINE_VERSION = '0.1.0';

    public function __construct()
    {
        $this->translationDomain = Database::MODULE_NAME;
    }

    public function getName(): string
    {
        return $this->translate('Hetzner Cloud');
    }

    public function providedDescriptions(): array
    {
        return [];
    }

    public function getVersion(): string
    {
        if ($this->version === null) {
            $conn = $this->getDb();
            $schema = $this->getSchemaQuery()
                ->columns(['version', 'success'])
                ->orderBy('id', SORT_DESC)
                ->limit(2);

            if (! static::tableExists($conn, $schema->getModel()->getTableName())) {
                $this->version = '0.0.0';

                return $this->version;
            }

            /** @var Schema $entry */
            foreach ($schema as $entry) {
                if ($entry->success) {
                    $this->version = $entry->version;

                    break;
                }
            }

            if (! $this->version) {
                $this->version = self::BASELINE_VERSION;
            }
        }

        return $this->version;
    }

    public function getDb(): Connection
    {
        return Database::get();
    }

    protected function getSchemaQuery(): Query
    {
        return Schema::on($this->getDb());
    }
}
