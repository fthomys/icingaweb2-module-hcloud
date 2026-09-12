<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Pricing;

final class CostCalculator
{
    public const CATEGORY_SERVER = 'server';
    public const CATEGORY_LOAD_BALANCER = 'load_balancer';
    public const CATEGORY_VOLUME = 'volume';
    public const CATEGORY_PRIMARY_IP = 'primary_ip';
    public const CATEGORY_FLOATING_IP = 'floating_ip';
    public const CATEGORY_STORAGE_BOX = 'storage_box';
    public const CATEGORY_SNAPSHOT = 'snapshot';
    public const CATEGORY_TRAFFIC = 'traffic';

    private const BYTES_PER_TB = 1000 ** 4;

    /**
     * @param CostInput $input
     */
    public function calculate(CostInput $input): CostReport
    {
        $lines = [];

        foreach ($this->groupedTypeCosts($input->servers, $input->serverTypePrices, self::CATEGORY_SERVER) as $line) {
            $lines[] = $line;
        }

        foreach (
            $this->groupedTypeCosts(
                $input->loadBalancers,
                $input->loadBalancerTypePrices,
                self::CATEGORY_LOAD_BALANCER
            ) as $line
        ) {
            $lines[] = $line;
        }

        foreach (
            $this->groupedTypeCosts(
                $input->storageBoxes,
                $input->storageBoxTypePrices,
                self::CATEGORY_STORAGE_BOX
            ) as $line
        ) {
            $lines[] = $line;
        }

        foreach ($this->ipCosts($input->primaryIps, $input->primaryIpPrices, self::CATEGORY_PRIMARY_IP) as $line) {
            $lines[] = $line;
        }

        foreach ($this->ipCosts($input->floatingIps, $input->floatingIpPrices, self::CATEGORY_FLOATING_IP) as $line) {
            $lines[] = $line;
        }

        $volumeLine = $this->perGigabyteLine(
            self::CATEGORY_VOLUME,
            mt('hcloud', 'Volumes'),
            $input->volumeGigabytes,
            $input->volumeCount,
            $input->volumePricePerGbMonth,
            $input->volumePricePerGbMonthGross,
            $input->vatRate
        );
        if ($volumeLine !== null) {
            $lines[] = $volumeLine;
        }

        $snapshotLine = $this->perGigabyteLine(
            self::CATEGORY_SNAPSHOT,
            mt('hcloud', 'Snapshots and backups'),
            $input->snapshotGigabytes,
            $input->snapshotCount,
            $input->imagePricePerGbMonth,
            $input->imagePricePerGbMonthGross,
            $input->vatRate
        );
        if ($snapshotLine !== null) {
            $lines[] = $snapshotLine;
        }

        foreach ($this->trafficOverage($input) as $line) {
            $lines[] = $line;
        }

        $net = Money::zero();
        $gross = Money::zero();
        foreach ($lines as $line) {
            $net = $net->plus($line->monthlyNet);
            $gross = $gross->plus($line->monthlyGross);
        }

        $hasPricingData = $input->currency !== null
            || $input->serverTypePrices !== []
            || $input->loadBalancerTypePrices !== []
            || $input->primaryIpPrices !== []
            || $input->floatingIpPrices !== []
            || $input->storageBoxTypePrices !== []
            || $input->volumePricePerGbMonth !== null;

        return new CostReport($input->currency, $lines, $net, $gross, $hasPricingData);
    }

    /**
     * @param list<array{type_id: int|null, location: string|null}> $resources
     * @param array<string, array{net: string|null, gross: string|null, name: string|null}> $prices
     *
     * @return list<CostLine>
     */
    private function groupedTypeCosts(array $resources, array $prices, string $category): array
    {
        $groups = [];

        foreach ($resources as $resource) {
            $key = ($resource['type_id'] ?? 0) . '@' . ($resource['location'] ?? '');
            $groups[$key] ??= ['count' => 0, 'type_id' => $resource['type_id'], 'location' => $resource['location']];
            $groups[$key]['count']++;
        }

        $lines = [];
        foreach ($groups as $key => $group) {
            $price = $prices[$key] ?? null;
            $net = Money::fromDecimal($price['net'] ?? null)->times($group['count']);
            $gross = $price['gross'] ?? null;

            $lines[] = new CostLine(
                $category,
                $price['name'] ?? (string) ($group['type_id'] ?? '-'),
                $group['location'],
                $group['count'],
                $net,
                $gross !== null
                    ? Money::fromDecimal($gross)->times($group['count'])
                    : $this->addVat($net, null)
            );
        }

        return $lines;
    }

    /**
     * @param list<array{type: string|null, location: string|null}> $ips
     * @param array<string, array{net: string|null, gross: string|null}> $prices
     *
     * @return list<CostLine>
     */
    private function ipCosts(array $ips, array $prices, string $category): array
    {
        $groups = [];

        foreach ($ips as $ip) {
            $key = ($ip['type'] ?? '') . '@' . ($ip['location'] ?? '');
            $groups[$key] ??= ['count' => 0, 'type' => $ip['type'], 'location' => $ip['location']];
            $groups[$key]['count']++;
        }

        $lines = [];
        foreach ($groups as $key => $group) {
            $price = $prices[$key] ?? null;
            $net = Money::fromDecimal($price['net'] ?? null)->times($group['count']);
            $gross = $price['gross'] ?? null;

            $lines[] = new CostLine(
                $category,
                strtoupper((string) ($group['type'] ?? '-')),
                $group['location'],
                $group['count'],
                $net,
                $gross !== null ? Money::fromDecimal($gross)->times($group['count']) : $this->addVat($net, null)
            );
        }

        return $lines;
    }

    private function perGigabyteLine(
        string $category,
        string $label,
        float $gigabytes,
        int $count,
        ?string $pricePerGbMonth,
        ?string $grossPerGbMonth,
        ?string $vatRate
    ): ?CostLine {
        if ($pricePerGbMonth === null || $gigabytes <= 0.0) {
            return null;
        }

        $net = Money::fromDecimal($pricePerGbMonth)->timesFraction($gigabytes);
        $gross = $grossPerGbMonth !== null
            ? Money::fromDecimal($grossPerGbMonth)->timesFraction($gigabytes)
            : $this->addVat($net, $vatRate);

        return new CostLine($category, $label, null, $count, $net, $gross);
    }

    /**
     * @return list<CostLine>
     */
    private function trafficOverage(CostInput $input): array
    {
        $lines = [];

        foreach (
            [
            [self::CATEGORY_SERVER, $input->servers, $input->serverTypePrices, mt('hcloud', 'Server traffic overage')],
            [
                self::CATEGORY_LOAD_BALANCER,
                $input->loadBalancers,
                $input->loadBalancerTypePrices,
                mt('hcloud', 'Load balancer traffic overage'),
            ],
            ] as [$category, $resources, $prices, $label]
        ) {
            $net = Money::zero();
            $affected = 0;

            foreach ($resources as $resource) {
                $outgoing = (int) ($resource['outgoing_traffic'] ?? 0);
                $included = (int) ($resource['included_traffic'] ?? 0);

                if ($included <= 0 || $outgoing <= $included) {
                    continue;
                }

                $key = ($resource['type_id'] ?? 0) . '@' . ($resource['location'] ?? '');
                $perTb = $prices[$key]['per_tb'] ?? null;
                if ($perTb === null) {
                    continue;
                }

                $affected++;
                $excessTb = ($outgoing - $included) / self::BYTES_PER_TB;
                $net = $net->plus(Money::fromDecimal($perTb)->timesFraction($excessTb));
            }

            if ($affected > 0 && $net->isPositive()) {
                $lines[] = new CostLine(
                    self::CATEGORY_TRAFFIC,
                    $label,
                    null,
                    $affected,
                    $net,
                    $this->addVat($net, $input->vatRate)
                );
            }
        }

        return $lines;
    }

    private function addVat(Money $net, ?string $vatRate): Money
    {
        if ($vatRate === null || $vatRate === '') {
            return $net;
        }

        return $net->timesFraction(1 + ((float) $vatRate / 100));
    }
}
