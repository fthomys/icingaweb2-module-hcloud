<?php

declare(strict_types=1);

namespace Tests\Icinga\Module\Hcloud\Web;

use PHPUnit\Framework\TestCase;

/**
 * A view that queries the database must first check that the database is usable.
 *
 * Without the guard an unconfigured module answers every page with a raw stack trace out of
 * Database::create(), which is the state every installation starts in.
 */
final class SetupGuardTest extends TestCase
{
    private const MODULE_DIR = __DIR__ . '/../../..';

    private const BASES_WITH_GUARD = ['ResourceController', 'DetailController'];

    public function testEveryDatabaseBackedControllerGuardsItsView(): void
    {
        $checked = 0;

        foreach (glob(self::MODULE_DIR . '/application/controllers/*Controller.php') ?: [] as $file) {
            $source = (string) file_get_contents($file);
            $name = basename($file, '.php');

            if (! self::touchesDatabase($source)) {
                continue;
            }

            $checked++;

            $inheritsGuard = false;
            foreach (self::BASES_WITH_GUARD as $base) {
                if (preg_match('/extends\s+' . $base . '\b/', $source)) {
                    $inheritsGuard = true;
                }
            }

            if ($inheritsGuard) {
                continue;
            }

            $this->assertStringContainsString(
                'databaseIsReady()',
                $source,
                sprintf('%s queries the database without calling databaseIsReady() first.', $name)
            );
        }

        $this->assertGreaterThan(0, $checked, 'No database backed controller was examined.');
    }

    public function testTheSharedBasesCarryTheGuard(): void
    {
        foreach (self::BASES_WITH_GUARD as $base) {
            $source = (string) file_get_contents(self::MODULE_DIR . '/library/Hcloud/Web/' . $base . '.php');

            $this->assertStringContainsString(
                'databaseIsReady()',
                $source,
                sprintf('%s is trusted to guard its subclasses but does not call databaseIsReady().', $base)
            );
        }
    }

    public function testTheGuardExistsOnTheBaseController(): void
    {
        $source = (string) file_get_contents(self::MODULE_DIR . '/library/Hcloud/Web/Controller.php');

        $this->assertStringContainsString('function databaseIsReady()', $source);
        $this->assertStringContainsString('Database::resourceName()', $source);
    }

    private static function touchesDatabase(string $source): bool
    {
        return str_contains($source, '$this->db()')
            || str_contains($source, 'Repository(')
            || (bool) preg_match('/extends\s+(ResourceController|DetailController)\b/', $source);
    }
}
