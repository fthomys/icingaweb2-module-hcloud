<?php

declare(strict_types=1);

namespace Tests\Icinga\Module\Hcloud;

use PHPUnit\Framework\TestCase;

final class UpgradeScriptsTest extends TestCase
{
    private const MODULE_DIR = __DIR__ . '/../..';

    public function testBothEnginesShipTheSameUpgradeScripts(): void
    {
        $this->assertSame(
            self::scripts('pgsql'),
            self::scripts('mysql'),
            'The MySQL and PostgreSQL upgrade directories contain different scripts.'
        );
    }

    public function testBothUpgradeDirectoriesExist(): void
    {
        foreach (['mysql', 'pgsql'] as $engine) {
            $this->assertDirectoryExists(
                sprintf('%s/schema/%s-upgrades', self::MODULE_DIR, $engine),
                sprintf('The %s upgrade directory is not shipped.', $engine)
            );
        }
    }

    public function testEveryUpgradeScriptRecordsItsOwnVersionLast(): void
    {
        if (self::scripts('mysql') === [] && self::scripts('pgsql') === []) {
            $this->markTestSkipped('This release ships no upgrade scripts yet.');
        }

        foreach (['mysql', 'pgsql'] as $engine) {
            foreach (self::scripts($engine) as $script) {
                $version = basename($script, '.sql');
                $path = sprintf('%s/schema/%s-upgrades/%s', self::MODULE_DIR, $engine, $script);
                $statements = self::statements((string) file_get_contents($path));

                $this->assertNotEmpty($statements, sprintf('%s/%s is empty.', $engine, $script));

                $last = end($statements);
                $this->assertStringContainsString(
                    'hcloud_schema',
                    $last,
                    sprintf('%s/%s does not record its version as its final statement.', $engine, $script)
                );
                $this->assertStringContainsString(
                    $version,
                    $last,
                    sprintf('%s/%s records a version other than %s.', $engine, $script, $version)
                );
            }
        }
    }

    public function testEveryUpgradeScriptIsNamedAfterASemanticVersion(): void
    {
        if (self::scripts('mysql') === [] && self::scripts('pgsql') === []) {
            $this->markTestSkipped('This release ships no upgrade scripts yet.');
        }

        foreach (['mysql', 'pgsql'] as $engine) {
            foreach (self::scripts($engine) as $script) {
                $version = basename($script, '.sql');
                $this->assertMatchesRegularExpression(
                    '/^\d+\.\d+\.\d+$/',
                    $version,
                    sprintf('%s/%s is not named after a semantic version.', $engine, $script)
                );
            }
        }
    }

    /**
     * @return list<string>
     */
    private static function scripts(string $engine): array
    {
        $dir = sprintf('%s/schema/%s-upgrades', self::MODULE_DIR, $engine);
        if (! is_dir($dir)) {
            return [];
        }

        $scripts = array_values(array_filter(
            (array) scandir($dir),
            static fn ($entry): bool => is_string($entry) && str_ends_with($entry, '.sql')
        ));

        sort($scripts);

        /** @var list<string> $scripts */
        return $scripts;
    }

    /**
     * @return list<string>
     */
    private static function statements(string $sql): array
    {
        $statements = array_map('trim', explode(';', $sql));

        return array_values(array_filter($statements, static fn (string $s): bool => $s !== ''));
    }
}
