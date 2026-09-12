<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Clicommands;

use Icinga\Cli\Command;
use Icinga\Module\Hcloud\Common\Database;
use Icinga\Module\Hcloud\Db\Migrator;
use Throwable;

class MigrateCommand extends Command
{
    /**
     * Show the current schema version and any pending upgrades
     *
     * USAGE
     *
     *   icingacli hcloud migrate list
     */
    public function listAction(): void
    {
        $migrator = $this->migrator();

        printf("Current schema version: %s\n", $migrator->currentVersion());

        $pending = $migrator->pending();
        if ($pending === []) {
            print("No pending migrations.\n");

            return;
        }

        foreach ($pending as $version => $path) {
            printf("  pending: %s (%s)\n", $version, basename($path));
        }
    }

    /**
     * Apply every pending schema upgrade
     *
     * USAGE
     *
     *   icingacli hcloud migrate run
     */
    public function runAction(): void
    {
        $migrator = $this->migrator();
        $pending = $migrator->pending();

        if ($pending === []) {
            print("No pending migrations.\n");

            return;
        }

        foreach ($pending as $version => $path) {
            printf("Applying %s ...\n", $version);

            try {
                $migrator->apply($version, $path);
            } catch (Throwable $e) {
                fwrite(STDERR, sprintf("Migration %s failed: %s\n", $version, $e->getMessage()));

                exit(1);
            }
        }

        printf("Schema is now at version %s.\n", $migrator->currentVersion());
    }

    private function migrator(): Migrator
    {
        return new Migrator(Database::get(), dirname(__DIR__, 2));
    }
}
