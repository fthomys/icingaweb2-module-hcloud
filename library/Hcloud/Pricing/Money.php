<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Pricing;

use InvalidArgumentException;

final class Money
{
    public const SCALE = 10;

    private const FACTOR = 10000000000;

    private function __construct(private readonly int $units)
    {
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public static function fromDecimal(string|int|float|null $value): self
    {
        if ($value === null || $value === '') {
            return self::zero();
        }

        $text = is_string($value) ? trim($value) : (string) $value;

        if (! preg_match('/^(-?)(\d*)(?:\.(\d*))?$/', $text, $matches)) {
            throw new InvalidArgumentException(sprintf('"%s" is not a decimal number.', $text));
        }

        $sign = $matches[1] === '-' ? -1 : 1;
        $whole = $matches[2] === '' ? '0' : $matches[2];
        $fraction = $matches[3] ?? '';

        $fraction = substr(str_pad($fraction, self::SCALE, '0'), 0, self::SCALE);

        return new self($sign * ((int) $whole * self::FACTOR + (int) $fraction));
    }

    public function plus(self $other): self
    {
        return new self($this->units + $other->units);
    }

    public function times(int $factor): self
    {
        return new self($this->units * $factor);
    }

    public function timesFraction(float $factor): self
    {
        return new self((int) round($this->units * $factor));
    }

    public function isZero(): bool
    {
        return $this->units === 0;
    }

    public function isPositive(): bool
    {
        return $this->units > 0;
    }

    public function units(): int
    {
        return $this->units;
    }

    public function toDecimal(int $decimals = 2): string
    {
        $negative = $this->units < 0;
        $units = abs($this->units);

        $whole = intdiv($units, self::FACTOR);
        $fraction = $units % self::FACTOR;

        if ($decimals >= self::SCALE) {
            $text = sprintf('%d.%0' . self::SCALE . 'd', $whole, $fraction);

            return $negative ? '-' . $text : $text;
        }

        $divisor = (int) (self::FACTOR / (10 ** $decimals));
        $rounded = intdiv($fraction + intdiv($divisor, 2), $divisor);

        if ($rounded >= 10 ** $decimals) {
            $whole++;
            $rounded = 0;
        }

        $text = $decimals === 0
            ? (string) $whole
            : sprintf('%d.%0' . $decimals . 'd', $whole, $rounded);

        return $negative ? '-' . $text : $text;
    }
}
