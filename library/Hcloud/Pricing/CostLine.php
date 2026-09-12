<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Pricing;

final class CostLine
{
    public function __construct(
        public readonly string $category,
        public readonly string $label,
        public readonly ?string $location,
        public readonly int $count,
        public readonly Money $monthlyNet,
        public readonly Money $monthlyGross
    ) {
    }
}
