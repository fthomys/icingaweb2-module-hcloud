<?php

declare(strict_types=1);

namespace Tests\Icinga\Module\Hcloud\Metric;

use Icinga\Module\Hcloud\Metric\SeriesInfo;
use Icinga\Module\Hcloud\Metric\Unit;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UnitTest extends TestCase
{
    public function testThroughputScalesBinary(): void
    {
        $this->assertSame('0 B/s', Unit::BytesPerSecond->format(0));
        $this->assertSame('512 B/s', Unit::BytesPerSecond->format(512));
        $this->assertSame('1 KiB/s', Unit::BytesPerSecond->format(1024));
        $this->assertSame('1.5 MiB/s', Unit::BytesPerSecond->format(1.5 * 1024 ** 2));
        $this->assertSame('2 GiB/s', Unit::BytesPerSecond->format(2 * 1024 ** 3));
    }

    public function testRatesScaleDecimal(): void
    {
        $this->assertSame('850 pps', Unit::PacketsPerSecond->format(850));
        $this->assertSame('12.5k pps', Unit::PacketsPerSecond->format(12500));
        $this->assertSame('340 IOPS', Unit::OperationsPerSecond->format(340));
        $this->assertSame('1.2k req/s', Unit::RequestsPerSecond->format(1200));
    }

    public function testPercentKeepsOneDecimalAndTrimsZeroes(): void
    {
        $this->assertSame('12.5 %', Unit::Percent->format(12.5));
        $this->assertSame('7 %', Unit::Percent->format(7.0));
        $this->assertSame('100 %', Unit::Percent->format(100.0));
    }

    public function testCpuPercentRoundsUpToWholeCores(): void
    {
        $this->assertSame(100.0, Unit::Percent->axisMaximum(12.5));
        $this->assertSame(100.0, Unit::Percent->axisMaximum(95.6));
        $this->assertSame(300.0, Unit::Percent->axisMaximum(277.3));
        $this->assertSame(400.0, Unit::Percent->axisMaximum(392.4));
    }

    public function testAnIdleServerStillGetsAFullFirstCoreOnTheAxis(): void
    {
        $this->assertSame(100.0, Unit::Percent->axisMaximum(0.0));
        $this->assertSame(100.0, Unit::Percent->axisMaximum(3.9));
    }

    public function testOtherUnitsScaleToTheirPeak(): void
    {
        $this->assertSame(1234.0, Unit::BytesPerSecond->axisMaximum(1234.0));
        $this->assertSame(7.0, Unit::Count->axisMaximum(7.0));
    }

    public function testNegativeValuesKeepTheirSign(): void
    {
        $this->assertSame('-1 KiB/s', Unit::BytesPerSecond->format(-1024));
    }

    /**
     * @return list<array{string, string, string}>
     */
    public static function seriesProvider(): array
    {
        return [
            ['cpu', 'cpu', Unit::Percent->value],
            ['disk.0.iops.read', 'disk-iops', Unit::OperationsPerSecond->value],
            ['disk.0.iops.write', 'disk-iops', Unit::OperationsPerSecond->value],
            ['disk.0.bandwidth.read', 'disk-bandwidth', Unit::BytesPerSecond->value],
            ['network.0.pps.in', 'network-pps', Unit::PacketsPerSecond->value],
            ['network.0.bandwidth.out', 'network-bandwidth', Unit::BytesPerSecond->value],
            ['open_connections', 'connections', Unit::Count->value],
            ['requests_per_second', 'requests', Unit::RequestsPerSecond->value],
            ['bandwidth.in', 'lb-bandwidth', Unit::BytesPerSecond->value],
        ];
    }

    #[DataProvider('seriesProvider')]
    public function testEveryKnownSeriesResolvesToItsUnitAndGroup(
        string $name,
        string $group,
        string $unit
    ): void {
        $info = SeriesInfo::forName($name);

        $this->assertSame($group, $info->group, $name . ' landed in the wrong chart group.');
        $this->assertSame($unit, $info->unit->value, $name . ' got the wrong unit.');
        $this->assertNotSame($name, $info->label, $name . ' kept its raw API name as a label.');
    }

    public function testReadAndWriteShareAGroupSoTheyLandInOneChart(): void
    {
        $this->assertSame(
            SeriesInfo::forName('disk.0.iops.read')->group,
            SeriesInfo::forName('disk.0.iops.write')->group
        );

        $this->assertNotSame(
            SeriesInfo::forName('disk.0.iops.read')->group,
            SeriesInfo::forName('disk.0.bandwidth.read')->group,
            'IOPS and throughput have different units and must not share an axis.'
        );
    }

    public function testTheDiskIndexSurvivesIntoTheLabel(): void
    {
        $this->assertStringContainsString('0', SeriesInfo::forName('disk.0.iops.read')->label);
    }

    public function testAnUnknownSeriesKeepsItsRawNameVisible(): void
    {
        $info = SeriesInfo::forName('something.hetzner.added');

        $this->assertSame('something.hetzner.added', $info->label);
        $this->assertSame(Unit::Count, $info->unit);
    }
}
