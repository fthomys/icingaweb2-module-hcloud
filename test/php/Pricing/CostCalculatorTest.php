<?php

declare(strict_types=1);

namespace Tests\Icinga\Module\Hcloud\Pricing;

use Icinga\Module\Hcloud\Pricing\CostCalculator;
use Icinga\Module\Hcloud\Pricing\CostInput;
use Icinga\Module\Hcloud\Pricing\CostReport;
use PHPUnit\Framework\TestCase;

final class CostCalculatorTest extends TestCase
{
    public function testIdenticalServersAreGroupedAndMultiplied(): void
    {
        $report = $this->calculate(new CostInput(
            currency: 'EUR',
            servers: [
                ['type_id' => 1, 'location' => 'fsn1'],
                ['type_id' => 1, 'location' => 'fsn1'],
                ['type_id' => 1, 'location' => 'nbg1'],
            ],
            serverTypePrices: [
                '1@fsn1' => ['net' => '5.8300000000', 'gross' => '6.9377000000', 'name' => 'cx22'],
                '1@nbg1' => ['net' => '5.8300000000', 'gross' => '6.9377000000', 'name' => 'cx22'],
            ]
        ));

        $this->assertCount(2, $report->lines);
        $this->assertSame('EUR', $report->currency);

        $fsn = $report->lines[0];
        $this->assertSame('cx22', $fsn->label);
        $this->assertSame('fsn1', $fsn->location);
        $this->assertSame(2, $fsn->count);
        $this->assertSame('11.66', $fsn->monthlyNet->toDecimal());

        $this->assertSame('17.49', $report->totalNet->toDecimal());
        $this->assertSame('20.81', $report->totalGross->toDecimal());
    }

    public function testVolumesAreChargedPerGigabyte(): void
    {
        $report = $this->calculate(new CostInput(
            currency: 'EUR',
            volumePricePerGbMonth: '0.0440000000',
            volumeGigabytes: 100.0,
            volumeCount: 2
        ));

        $this->assertCount(1, $report->lines);
        $this->assertSame(CostCalculator::CATEGORY_VOLUME, $report->lines[0]->category);
        $this->assertSame('4.40', $report->lines[0]->monthlyNet->toDecimal());
        $this->assertSame(2, $report->lines[0]->count);
    }

    public function testVolumeCountIsReportedIndependentlyOfTheChargedSize(): void
    {
        $report = $this->calculate(new CostInput(
            volumePricePerGbMonth: '0.0440000000',
            volumeGigabytes: 230.0,
            volumeCount: 3
        ));

        $this->assertSame(3, $report->lines[0]->count);
    }

    public function testAGrossPricePerGigabyteWinsOverADerivedVatRate(): void
    {
        $report = $this->calculate(new CostInput(
            vatRate: '19.00',
            volumePricePerGbMonth: '1.0000000000',
            volumePricePerGbMonthGross: '1.2500000000',
            volumeGigabytes: 100.0,
            volumeCount: 1
        ));

        $this->assertSame('100.00', $report->totalNet->toDecimal());
        $this->assertSame('125.00', $report->totalGross->toDecimal());
    }

    public function testTrafficOverageIsChargedPerExcessTerabyte(): void
    {
        $tb = 1000 ** 4;

        $report = $this->calculate(new CostInput(
            servers: [[
                'type_id' => 1,
                'location' => 'fsn1',
                'outgoing_traffic' => 22 * $tb,
                'included_traffic' => 20 * $tb,
            ]],
            serverTypePrices: [
                '1@fsn1' => [
                    'net' => '0.0000000000',
                    'gross' => null,
                    'name' => 'cx22',
                    'per_tb' => '1.0000000000',
                ],
            ]
        ));

        $traffic = array_values(array_filter(
            $report->lines,
            static fn ($l): bool => $l->category === CostCalculator::CATEGORY_TRAFFIC
        ));

        $this->assertCount(1, $traffic);
        $this->assertSame('2.00', $traffic[0]->monthlyNet->toDecimal());
        $this->assertSame(1, $traffic[0]->count);
    }

    public function testTrafficWithinTheIncludedQuotaCostsNothing(): void
    {
        $tb = 1000 ** 4;

        $report = $this->calculate(new CostInput(
            servers: [[
                'type_id' => 1,
                'location' => 'fsn1',
                'outgoing_traffic' => 5 * $tb,
                'included_traffic' => 20 * $tb,
            ]],
            serverTypePrices: [
                '1@fsn1' => ['net' => '0', 'gross' => null, 'name' => 'cx22', 'per_tb' => '1.0'],
            ]
        ));

        $traffic = array_filter(
            $report->lines,
            static fn ($l): bool => $l->category === CostCalculator::CATEGORY_TRAFFIC
        );

        $this->assertSame([], array_values($traffic));
    }

    public function testVatIsDerivedWhenTheApiGivesNoGrossPrice(): void
    {
        $report = $this->calculate(new CostInput(
            vatRate: '19.00',
            volumePricePerGbMonth: '1.0000000000',
            volumeGigabytes: 100.0
        ));

        $this->assertSame('100.00', $report->totalNet->toDecimal());
        $this->assertSame('119.00', $report->totalGross->toDecimal());
    }

    public function testAResourceWithoutAPriceStillAppearsWithZeroCost(): void
    {
        $report = $this->calculate(new CostInput(
            servers: [['type_id' => 99, 'location' => 'hel1']],
            serverTypePrices: []
        ));

        $this->assertCount(1, $report->lines);
        $this->assertSame('99', $report->lines[0]->label);
        $this->assertTrue($report->lines[0]->monthlyNet->isZero());
    }

    public function testTotalsAggregateByCategoryAndLocation(): void
    {
        $report = $this->calculate(new CostInput(
            servers: [
                ['type_id' => 1, 'location' => 'fsn1'],
                ['type_id' => 1, 'location' => 'nbg1'],
            ],
            primaryIps: [['type' => 'ipv4', 'location' => 'fsn1']],
            serverTypePrices: [
                '1@fsn1' => ['net' => '10.00', 'gross' => null, 'name' => 'cx22'],
                '1@nbg1' => ['net' => '10.00', 'gross' => null, 'name' => 'cx22'],
            ],
            primaryIpPrices: ['ipv4@fsn1' => ['net' => '0.50', 'gross' => null]]
        ));

        $byCategory = $report->netByCategory();
        $this->assertSame('20.00', $byCategory[CostCalculator::CATEGORY_SERVER]->toDecimal());
        $this->assertSame('0.50', $byCategory[CostCalculator::CATEGORY_PRIMARY_IP]->toDecimal());

        $byLocation = $report->netByLocation();
        $this->assertSame('10.50', $byLocation['fsn1']->toDecimal());
        $this->assertSame('10.00', $byLocation['nbg1']->toDecimal());
    }

    private function calculate(CostInput $input): CostReport
    {
        return (new CostCalculator())->calculate($input);
    }
}
