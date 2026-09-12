<?php

declare(strict_types=1);

namespace Tests\Icinga\Module\Hcloud\Web;

use Icinga\Module\Hcloud\Web\ColumnFormat;
use Icinga\Module\Hcloud\Web\ColumnFormats;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Icinga\Module\Hcloud\Lib\SchemaReader;

/**
 * The detail views rendered every column through the plain text formatter, so a storage box
 * reported 129747648512 where it meant 120.8 GiB. Formats are resolved per table because the
 * same column name carries different units in different tables.
 */
final class ColumnFormatsTest extends TestCase
{
    private const SCHEMA = __DIR__ . '/../../../schema/mysql.sql';

    public function testSizeIsGigabytesOnVolumesAndBytesOnStorageBoxTypes(): void
    {
        $this->assertSame(ColumnFormat::Gigabytes, ColumnFormats::for('hcloud_volume', 'size'));
        $this->assertSame(ColumnFormat::Bytes, ColumnFormats::for('hcloud_storage_box_type', 'size'));
    }

    public function testStorageBoxStatisticsAreBytes(): void
    {
        foreach (['stats_size', 'stats_size_data', 'stats_size_snapshots'] as $column) {
            $this->assertSame(ColumnFormat::Bytes, ColumnFormats::for('hcloud_storage_box', $column));
        }
    }

    public function testPricesAreRecognisedByTheirSuffix(): void
    {
        $this->assertSame(ColumnFormat::Price, ColumnFormats::for('hcloud_pricing_server_type', 'price_hourly_net'));
        $this->assertSame(ColumnFormat::Price, ColumnFormats::for('hcloud_storage_box_type', 'setup_fee_gross'));
    }

    public function testAnUnknownColumnStaysText(): void
    {
        $this->assertSame(ColumnFormat::Text, ColumnFormats::for('hcloud_server', 'name'));
    }

    /**
     * Every TINYINT in this schema is a boolean flag. A new one that renders as 0 or 1 is a
     * defect, so the schema itself decides what has to be covered here.
     *
     * @return list<array{string, string}>
     */
    public static function flagColumnProvider(): array
    {
        $flags = [];

        foreach (SchemaReader::tables(self::SCHEMA, SchemaReader::MYSQL) as $table => $definition) {
            foreach ($definition['columns'] as $column => $type) {
                if (str_starts_with($type, 'smallint ')) {
                    $flags[] = [$table, $column];
                }
            }
        }

        return $flags;
    }

    #[DataProvider('flagColumnProvider')]
    public function testEveryFlagColumnRendersAsYesOrNo(string $table, string $column): void
    {
        $this->assertSame(
            ColumnFormat::YesNo,
            ColumnFormats::for($table, $column),
            sprintf('%s.%s is a TINYINT flag but would render as a raw number', $table, $column)
        );
    }

    /**
     * @return list<array{string, string}>
     */
    public static function byteColumnProvider(): array
    {
        $columns = [];

        foreach (SchemaReader::tables(self::SCHEMA, SchemaReader::MYSQL) as $table => $definition) {
            foreach ($definition['columns'] as $column => $type) {
                if (! str_starts_with($type, 'bigint ')) {
                    continue;
                }

                if (str_contains($column, 'size') || str_ends_with($column, '_traffic')) {
                    $columns[] = [$table, $column];
                }
            }
        }

        return $columns;
    }

    #[DataProvider('byteColumnProvider')]
    public function testEverySizeOrTrafficCounterRendersAsBytes(string $table, string $column): void
    {
        $this->assertSame(
            ColumnFormat::Bytes,
            ColumnFormats::for($table, $column),
            sprintf('%s.%s holds a byte count but would render unformatted', $table, $column)
        );
    }
}
