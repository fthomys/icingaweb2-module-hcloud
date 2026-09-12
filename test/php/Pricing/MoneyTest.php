<?php

declare(strict_types=1);

namespace Tests\Icinga\Module\Hcloud\Pricing;

use Icinga\Module\Hcloud\Pricing\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testHetznerStylePricesRoundTrip(): void
    {
        $this->assertSame('0.0060000000', Money::fromDecimal('0.0060000000')->toDecimal(10));
        $this->assertSame('5.83', Money::fromDecimal('5.8300000000')->toDecimal());
        $this->assertSame('0.01', Money::fromDecimal('0.0060000000')->toDecimal());
    }

    public function testAdditionIsExactWhereFloatWouldDrift(): void
    {
        $sum = Money::zero();
        for ($i = 0; $i < 10; $i++) {
            $sum = $sum->plus(Money::fromDecimal('0.1'));
        }

        $this->assertSame('1.0000000000', $sum->toDecimal(10));
        $this->assertSame('1.00', $sum->toDecimal());
    }

    public function testThreeTenthsSumExactly(): void
    {
        $sum = Money::fromDecimal('0.1')
            ->plus(Money::fromDecimal('0.2'));

        $this->assertSame('0.3000000000', $sum->toDecimal(10));
        $this->assertNotSame(0.1 + 0.2, 0.3, 'Float addition is what this type exists to avoid.');
    }

    public function testMultiplicationByACountIsExact(): void
    {
        $this->assertSame('58.30', Money::fromDecimal('5.83')->times(10)->toDecimal());
        $this->assertSame('0.00', Money::fromDecimal('5.83')->times(0)->toDecimal());
    }

    public function testFractionalMultiplicationRounds(): void
    {
        $this->assertSame('2.92', Money::fromDecimal('5.83')->timesFraction(0.5)->toDecimal());
        $this->assertSame('14.58', Money::fromDecimal('5.83')->timesFraction(2.5)->toDecimal());
    }

    public function testRoundingIsHalfUpAndCarriesOver(): void
    {
        $this->assertSame('1.00', Money::fromDecimal('0.995')->toDecimal());
        $this->assertSame('0.99', Money::fromDecimal('0.994')->toDecimal());
        $this->assertSame('10.00', Money::fromDecimal('9.999')->toDecimal());
    }

    public function testNegativeAmountsKeepTheirSign(): void
    {
        $this->assertSame('-5.83', Money::fromDecimal('-5.83')->toDecimal());
    }

    public function testNullAndEmptyBecomeZero(): void
    {
        $this->assertTrue(Money::fromDecimal(null)->isZero());
        $this->assertTrue(Money::fromDecimal('')->isZero());
    }

    public function testNonNumericInputIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::fromDecimal('not a price');
    }
}
