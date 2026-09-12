<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Pricing;

final class CostReport
{
    /**
     * @param list<CostLine> $lines
     */
    public function __construct(
        public readonly ?string $currency,
        public readonly array $lines,
        public readonly Money $totalNet,
        public readonly Money $totalGross,
        public readonly bool $hasPricingData = true
    ) {
    }

    /**
     * @return array<string, Money>
     */
    public function netByCategory(): array
    {
        $totals = [];

        foreach ($this->lines as $line) {
            $totals[$line->category] = ($totals[$line->category] ?? Money::zero())->plus($line->monthlyNet);
        }

        return $totals;
    }

    /**
     * @return array<string, Money>
     */
    public function netByLocation(): array
    {
        $totals = [];

        foreach ($this->lines as $line) {
            $key = $line->location ?? '-';
            $totals[$key] = ($totals[$key] ?? Money::zero())->plus($line->monthlyNet);
        }

        return $totals;
    }
}
