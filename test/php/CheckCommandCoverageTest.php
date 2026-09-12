<?php

declare(strict_types=1);

namespace Tests\Icinga\Module\Hcloud;

use PHPUnit\Framework\TestCase;

/**
 * Every check named in the documentation must exist, and every check that exists must be
 * documented. A documented command that was never written answers "Available actions:".
 */
final class CheckCommandCoverageTest extends TestCase
{
    private const MODULE_DIR = __DIR__ . '/../..';

    public function testDocumentedChecksAreImplemented(): void
    {
        $documented = self::documented();
        $implemented = self::implemented();

        $this->assertNotEmpty($documented, 'The monitoring chapter documents no check at all.');

        foreach ($documented as $check) {
            $this->assertContains(
                $check,
                $implemented,
                sprintf('doc/05-Monitoring.md documents "check %s" but CheckCommand has no %sAction().', $check, $check)
            );
        }
    }

    public function testImplementedChecksAreDocumented(): void
    {
        $documented = self::documented();

        foreach (self::implemented() as $check) {
            $this->assertContains(
                $check,
                $documented,
                sprintf('CheckCommand implements "%s" but doc/05-Monitoring.md never mentions it.', $check)
            );
        }
    }

    /**
     * @return list<string>
     */
    private static function documented(): array
    {
        $doc = (string) file_get_contents(self::MODULE_DIR . '/doc/05-Monitoring.md');

        preg_match_all('/icingacli hcloud check ([a-z]+)/', $doc, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * @return list<string>
     */
    private static function implemented(): array
    {
        $source = (string) file_get_contents(self::MODULE_DIR . '/application/clicommands/CheckCommand.php');

        preg_match_all('/public function (\w+)Action\(\): void/', $source, $matches);

        return array_values(array_unique($matches[1]));
    }
}
