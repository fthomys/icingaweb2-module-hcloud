<?php

declare(strict_types=1);

namespace Tests\Icinga\Module\Hcloud\Metric;

use Icinga\Module\Hcloud\Metric\Downsampler;
use PHPUnit\Framework\TestCase;

final class DownsamplerTest extends TestCase
{
    /**
     * @return list<array{ts: string, value: float}>
     */
    private static function ramp(int $count): array
    {
        $points = [];
        for ($i = 0; $i < $count; $i++) {
            $points[] = ['ts' => sprintf('2026-09-12 %02d:%02d:00', intdiv($i, 60), $i % 60), 'value' => (float) $i];
        }

        return $points;
    }

    public function testFewerPointsThanBucketsArePassedThroughUntouched(): void
    {
        $result = Downsampler::bucket(self::ramp(5), 100);

        $this->assertCount(5, $result);
        $this->assertSame(0.0, $result[0]['min']);
        $this->assertSame(0.0, $result[0]['max']);
        $this->assertSame(0.0, $result[0]['avg']);
    }

    public function testDensePointsAreReducedToTheBucketCount(): void
    {
        $this->assertCount(100, Downsampler::bucket(self::ramp(1400), 100));
    }

    public function testASpikeSurvivesDownsampling(): void
    {
        $points = self::ramp(1000);
        $points[500]['value'] = 99999.0;

        $result = Downsampler::bucket($points, 50);
        $peak = max(array_column($result, 'max'));

        $this->assertSame(99999.0, $peak, 'A spike must not be averaged away.');
    }

    public function testTheAverageStaysInsideTheEnvelope(): void
    {
        foreach (Downsampler::bucket(self::ramp(1000), 40) as $bucket) {
            $this->assertGreaterThanOrEqual($bucket['min'], $bucket['avg']);
            $this->assertLessThanOrEqual($bucket['max'], $bucket['avg']);
        }
    }

    public function testBucketsStayInChronologicalOrder(): void
    {
        $stamps = array_column(Downsampler::bucket(self::ramp(600), 30), 'ts');
        $sorted = $stamps;
        sort($sorted);

        $this->assertSame($sorted, $stamps);
    }

    public function testEveryPointLandsInExactlyOneBucket(): void
    {
        $result = Downsampler::bucket(self::ramp(1000), 37);

        $this->assertSame(0.0, $result[0]['min'], 'The first sample must open the first bucket.');
        $this->assertSame(999.0, $result[count($result) - 1]['max'], 'The last sample must close the last.');
    }

    public function testEmptyInputStaysEmpty(): void
    {
        $this->assertSame([], Downsampler::bucket([], 10));
    }

    public function testAZeroBucketRequestDoesNotDivideByZero(): void
    {
        $this->assertCount(1, Downsampler::bucket(self::ramp(100), 0));
    }

    public function testTimeBucketsSpanTheRequestedWindowEvenly(): void
    {
        $from = strtotime('2026-09-12 00:00:00 UTC');
        $to = $from + 3600;

        $points = [
            ['ts' => '2026-09-12 00:05:00', 'value' => 10.0],
            ['ts' => '2026-09-12 00:55:00', 'value' => 20.0],
        ];

        $result = Downsampler::overTime($points, 4, $from, $to);

        $this->assertCount(4, $result);
        $this->assertNotNull($result[0], 'The first quarter hour holds a sample.');
        $this->assertNull($result[1], 'The second holds none and must stay a gap.');
        $this->assertNull($result[2]);
        $this->assertNotNull($result[3]);
    }

    public function testDenseAndSparseStretchesKeepTheirRelativeWidth(): void
    {
        $from = strtotime('2026-09-12 00:00:00 UTC');
        $to = $from + 7200;

        $points = [];
        for ($i = 0; $i < 500; $i++) {
            $points[] = ['ts' => gmdate('Y-m-d H:i:s', $from + $i), 'value' => 1.0];
        }
        $points[] = ['ts' => gmdate('Y-m-d H:i:s', $from + 7000), 'value' => 5.0];

        $result = Downsampler::overTime($points, 8, $from, $to);
        $filled = count(array_filter($result, static fn ($b): bool => $b !== null));

        $this->assertLessThanOrEqual(
            2,
            $filled,
            'Five hundred samples inside the first nine minutes must not claim more than their slice of time.'
        );
    }

    public function testSamplesOutsideTheWindowAreIgnored(): void
    {
        $from = strtotime('2026-09-12 12:00:00 UTC');
        $to = $from + 3600;

        $result = Downsampler::overTime([
            ['ts' => '2026-09-12 09:00:00', 'value' => 99.0],
            ['ts' => '2026-09-12 12:30:00', 'value' => 5.0],
        ], 2, $from, $to);

        $this->assertNull($result[0]);
        $this->assertNotNull($result[1]);
        $this->assertSame(5.0, $result[1]['avg']);
    }

    public function testAnInvertedWindowDoesNotDivideByZero(): void
    {
        $from = strtotime('2026-09-12 12:00:00 UTC');

        $this->assertCount(3, Downsampler::overTime([], 3, $from, $from));
    }

    public function testABucketWidthNeverCollapsesToZero(): void
    {
        $from = strtotime('2026-09-12 00:00:00 UTC');

        $result = Downsampler::overTime(
            [['ts' => '2026-09-12 00:00:00', 'value' => 1.0]],
            216,
            $from,
            $from + 1
        );

        $this->assertCount(216, $result);
    }
}
