<?php

declare(strict_types=1);

namespace Tests\Icinga\Module\Hcloud\Web;

use Icinga\Module\Hcloud\Web\ValueFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ValueFormatterTest extends TestCase
{
    /**
     * @return list<array{string, string}>
     */
    public static function priceProvider(): array
    {
        return [
            ['0.0052000000', '0.0052'],
            ['12.5000000000', '12.50'],
            ['5.0000000000', '5.00'],
            ['0.0000000000', '0.00'],
            ['73', '73.00'],
            ['0.0000004321', '0.000000'],
        ];
    }

    #[DataProvider('priceProvider')]
    public function testPriceKeepsTheDecimalsThatCarryInformation(string $stored, string $expected): void
    {
        $this->assertSame($expected, ValueFormatter::price($stored));
    }

    public function testPriceRejectsNonNumericInput(): void
    {
        $this->assertSame('-', ValueFormatter::price('n/a'));
        $this->assertSame('-', ValueFormatter::price(null));
    }

    /**
     * @return list<array{int, string}>
     */
    public static function secondsProvider(): array
    {
        return [
            [0, '0 s'],
            [45, '45 s'],
            [60, '1 min'],
            [90, '1.5 min'],
            [3600, '1 h'],
            [5400, '1.5 h'],
            [86400, '1 d'],
            [172800, '2 d'],
        ];
    }

    #[DataProvider('secondsProvider')]
    public function testSecondsScaleToTheLargestWholeUnit(int $seconds, string $expected): void
    {
        $this->assertSame($expected, ValueFormatter::seconds($seconds));
    }

    public function testMillisecondsBecomeSecondsOnceTheyPassOne(): void
    {
        $this->assertSame('340 ms', ValueFormatter::milliseconds(340));
        $this->assertSame('1.5 s', ValueFormatter::milliseconds(1500));
    }

    public function testPercentageDropsAnEmptyFraction(): void
    {
        $this->assertSame('19 %', ValueFormatter::percentage('19.0000'));
        $this->assertSame('19.5 %', ValueFormatter::percentage('19.5'));
        $this->assertSame('12.1 %', ValueFormatter::percentage(12.1));
        $this->assertSame('0 %', ValueFormatter::percentage(0.0));
    }

    public function testBytesUseBinaryUnits(): void
    {
        $this->assertSame('120.8 GiB', ValueFormatter::bytes(129747648512));
        $this->assertSame('0 B', ValueFormatter::bytes(0));
        $this->assertSame('512 B', ValueFormatter::bytes(512));
    }
}
