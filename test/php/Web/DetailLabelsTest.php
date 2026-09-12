<?php

declare(strict_types=1);

namespace Tests\Icinga\Module\Hcloud\Web;

use Icinga\Module\Hcloud\Model\ModelIndex;
use PHPUnit\Framework\TestCase;

/**
 * The sub tables on a detail page are read with raw ipl\Sql, so they have no model to take
 * headings from unless one is looked up. They used to invent English headings with ucwords(),
 * which bypassed the translation catalog entirely.
 */
final class DetailLabelsTest extends TestCase
{
    private const MODULE_DIR = __DIR__ . '/../../..';

    /**
     * @return list<array{string, string, list<string>}>
     */
    public static function relatedTableProvider(): array
    {
        $specs = [];

        foreach (glob(self::MODULE_DIR . '/application/controllers/*Controller.php') ?: [] as $file) {
            $source = (string) file_get_contents($file);

            if (
                ! preg_match_all(
                    "/'table'\s*=>\s*'(\w+)',\s*\n\s*'foreign'\s*=>\s*'\w+',\s*\n\s*'columns'\s*=>\s*\[(.*?)\],/s",
                    $source,
                    $matches,
                    PREG_SET_ORDER
                )
            ) {
                continue;
            }

            foreach ($matches as $match) {
                preg_match_all("/'(\w+)'/", $match[2], $columns);
                $specs[] = [basename($file, '.php'), $match[1], $columns[1]];
            }
        }

        return $specs;
    }

    /**
     * @param list<string> $columns
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('relatedTableProvider')]
    public function testEveryRelatedColumnHasAModelLabel(string $controller, string $table, array $columns): void
    {
        $this->assertArrayHasKey(
            $table,
            ModelIndex::byTable(),
            sprintf('%s shows %s but no model maps to that table.', $controller, $table)
        );

        $model = ModelIndex::byTable()[$table];
        $definitions = (new $model())->getColumnDefinitions();

        foreach ($columns as $column) {
            $this->assertArrayHasKey(
                $column,
                $definitions,
                sprintf(
                    '%s shows %s.%s, which %s does not label, so the heading would fall back to English.',
                    $controller,
                    $table,
                    $column,
                    $model
                )
            );
        }
    }

    public function testTheIndexCoversEveryTable(): void
    {
        $this->assertGreaterThan(40, count(ModelIndex::byTable()));
    }
}
