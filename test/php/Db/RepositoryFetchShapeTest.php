<?php

declare(strict_types=1);

namespace Tests\Icinga\Module\Hcloud\Db;

use PHPUnit\Framework\TestCase;

/**
 * ipl\Sql never sets PDO::ATTR_DEFAULT_FETCH_MODE, so fetchAll(), fetchRow() and fetchOne()
 * all return FETCH_BOTH arrays. fetchOne() is an alias of fetchRow(): it returns the first
 * ROW, not the first column. Casting that row to int yields 1 for any non empty row, which
 * silently produced a wrong foreign key. Reading those with -> is a runtime fatal that no type check catches,
 * because both methods are declared as returning plain array/mixed.
 *
 * Every read must therefore either pass an explicit fetch mode or cast the row.
 */
final class RepositoryFetchShapeTest extends TestCase
{
    private const MODULE_DIR = __DIR__ . '/../../..';

    /**
     * @return list<array{string}>
     */
    public static function sourceProvider(): array
    {
        $files = [];

        foreach (['application', 'library'] as $dir) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(self::MODULE_DIR . '/' . $dir)
            );

            /** @var \SplFileInfo $file */
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $files[] = [(string) $file->getRealPath()];
                }
            }
        }

        return $files;
    }

    /**
     * @dataProvider sourceProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('sourceProvider')]
    public function testNoRawFetchIsReadAsAnObject(string $file): void
    {
        $source = (string) file_get_contents($file);

        if (! preg_match('/->fetchAll\(|->fetchRow\(|->fetchOne\(/', $source)) {
            $this->assertTrue(true, 'No raw fetch in this file.');

            return;
        }

        $lines = explode("\n", $source);

        foreach ($lines as $number => $line) {
            if (! preg_match('/->fetchAll\(|->fetchRow\(|->fetchOne\(/', $line)) {
                continue;
            }

            $window = implode("\n", array_slice($lines, $number, 12));

            $this->assertMatchesRegularExpression(
                '/\(array\)|\bPDO::FETCH_ASSOC\b|\$rows\[\]|yieldAll/',
                $window,
                sprintf(
                    '%s:%d reads a raw fetch result without casting it to an array. '
                    . 'ipl\Sql returns FETCH_BOTH arrays, so property access fatals at runtime.',
                    basename($file),
                    $number + 1
                )
            );
        }
    }
}
