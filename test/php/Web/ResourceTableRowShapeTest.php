<?php

declare(strict_types=1);

namespace Tests\Icinga\Module\Hcloud\Web;

use Icinga\Module\Hcloud\Enum\ServerStatus;
use Icinga\Module\Hcloud\Web\Widget\ResourceTable;
use PHPUnit\Framework\TestCase;

/**
 * ipl\Orm hands the table hydrated models, a raw ipl\Sql read hands it associative arrays.
 *
 * ipl\Sql leaves the PDO fetch mode at PDO::FETCH_BOTH, so rows from fetchAll() are arrays,
 * not objects. Reading them as objects produced "Attempt to read property on array".
 */
final class ResourceTableRowShapeTest extends TestCase
{
    private const COLUMNS = ['name', 'status'];

    /** @var array<string, string> */
    private const LABELS = ['name' => 'Name', 'status' => 'Status'];

    public function testArrayRowsRender(): void
    {
        $html = $this->render([['name' => 'web01', 'status' => 'running']]);

        $this->assertStringContainsString('web01', $html);
        $this->assertStringContainsString('running', $html);
    }

    public function testObjectRowsRender(): void
    {
        $html = $this->render([(object) ['name' => 'web01', 'status' => 'running']]);

        $this->assertStringContainsString('web01', $html);
        $this->assertStringContainsString('running', $html);
    }

    public function testBothShapesProduceTheSameOutput(): void
    {
        $this->assertSame(
            $this->render([(object) ['name' => 'web01', 'status' => 'off']]),
            $this->render([['name' => 'web01', 'status' => 'off']])
        );
    }

    public function testMissingColumnsDoNotFatal(): void
    {
        $html = $this->render([['name' => 'web01']]);

        $this->assertStringContainsString('web01', $html);
    }

    public function testEnumColumnsResolveForArrayRows(): void
    {
        $table = new ResourceTable(
            [['name' => 'web01', 'status' => 'running']],
            self::COLUMNS,
            self::LABELS,
            ['status' => ServerStatus::class]
        );

        $this->assertStringContainsString('state-ok', $table->render());
    }

    public function testAnEmptyResultRendersTheEmptyState(): void
    {
        $this->assertStringContainsString('No results found.', $this->render([]));
    }

    /**
     * @param list<object|array<string, mixed>> $rows
     */
    private function render(array $rows): string
    {
        return (new ResourceTable($rows, self::COLUMNS, self::LABELS))->render();
    }
}
