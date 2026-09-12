<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Pricing;

final class CostInput
{
    /**
     * @param list<array{type_id: int|null, location: string|null, outgoing_traffic?: int|null,
     *     included_traffic?: int|null}> $servers
     * @param list<array{type_id: int|null, location: string|null, outgoing_traffic?: int|null,
     *     included_traffic?: int|null}> $loadBalancers
     * @param list<array{type_id: int|null, location: string|null}> $storageBoxes
     * @param list<array{type: string|null, location: string|null}> $primaryIps
     * @param list<array{type: string|null, location: string|null}> $floatingIps
     * @param array<string, array{net: string|null, gross: string|null, name: string|null,
     *     per_tb?: string|null}> $serverTypePrices
     * @param array<string, array{net: string|null, gross: string|null, name: string|null,
     *     per_tb?: string|null}> $loadBalancerTypePrices
     * @param array<string, array{net: string|null, gross: string|null, name: string|null}> $storageBoxTypePrices
     * @param array<string, array{net: string|null, gross: string|null}> $primaryIpPrices
     * @param array<string, array{net: string|null, gross: string|null}> $floatingIpPrices
     */
    public function __construct(
        public readonly ?string $currency = null,
        public readonly ?string $vatRate = null,
        public readonly array $servers = [],
        public readonly array $loadBalancers = [],
        public readonly array $storageBoxes = [],
        public readonly array $primaryIps = [],
        public readonly array $floatingIps = [],
        public readonly array $serverTypePrices = [],
        public readonly array $loadBalancerTypePrices = [],
        public readonly array $storageBoxTypePrices = [],
        public readonly array $primaryIpPrices = [],
        public readonly array $floatingIpPrices = [],
        public readonly ?string $volumePricePerGbMonth = null,
        public readonly ?string $volumePricePerGbMonthGross = null,
        public readonly ?string $imagePricePerGbMonth = null,
        public readonly ?string $imagePricePerGbMonthGross = null,
        public readonly float $volumeGigabytes = 0.0,
        public readonly int $volumeCount = 0,
        public readonly float $snapshotGigabytes = 0.0,
        public readonly int $snapshotCount = 0
    ) {
    }
}
