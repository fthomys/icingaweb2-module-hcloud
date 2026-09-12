<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Db;

use Icinga\Module\Hcloud\Pricing\CostInput;
use PDO;
use ipl\Sql\Connection;
use ipl\Sql\Select;

final class Repository
{
    public function __construct(private readonly Connection $db)
    {
    }

    /**
     * ipl\Sql leaves the PDO fetch mode at its default, which yields FETCH_BOTH arrays.
     * Every read in this class goes through here so the shape is never in doubt.
     *
     * @return list<array<string, mixed>>
     */
    private function rows(Select $select): array
    {
        $rows = [];

        foreach ($this->db->yieldAll($select, PDO::FETCH_ASSOC) as $row) {
            if (is_array($row)) {
                /** @var array<string, mixed> $row */
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @return list<array{id: int, config_key: string, name: string}>
     */
    public function projects(bool $onlyEnabled = true): array
    {
        $select = (new Select())
            ->from('hcloud_project')
            ->columns(['id', 'config_key', 'name'])
            ->orderBy('name');

        if ($onlyEnabled) {
            $select->where(['enabled = ?' => 1]);
        }

        $projects = [];
        foreach ($this->rows($select) as $row) {
            $projects[] = [
                'id' => (int) ($row['id'] ?? 0),
                'config_key' => (string) ($row['config_key'] ?? ''),
                'name' => (string) ($row['name'] ?? ''),
            ];
        }

        return $projects;
    }

    /**
     * @param array<string, mixed>|null $condition
     */
    public function countOf(string $table, int $projectId, ?array $condition = null): int
    {
        $where = ['project_id = ?' => $projectId] + ($condition ?? []);

        $select = (new Select())
            ->from($table)
            ->columns(['COUNT(*)'])
            ->where($where);

        return (int) $this->db->fetchScalar($select);
    }

    public function lastSuccessfulSync(int $projectId): ?string
    {
        $select = (new Select())
            ->from('hcloud_sync_run')
            ->columns(['MAX(ended)'])
            ->where(['project_id = ?' => $projectId, 'status = ?' => Store::STATUS_SUCCESS]);

        $value = $this->db->fetchScalar($select);

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function costInput(int $projectId): CostInput
    {
        $pricing = $this->rows(
            (new Select())->from('hcloud_pricing')->columns('*')->where(['project_id = ?' => $projectId])
        )[0] ?? [];

        $locations = $this->locationNames($projectId);

        return new CostInput(
            currency: isset($pricing['currency']) ? (string) $pricing['currency'] : null,
            vatRate: isset($pricing['vat_rate']) ? (string) $pricing['vat_rate'] : null,
            servers: $this->typedResources($projectId, 'hcloud_server', 'server_type_id', $locations, true),
            loadBalancers: $this->typedResources(
                $projectId,
                'hcloud_load_balancer',
                'load_balancer_type_id',
                $locations,
                true
            ),
            storageBoxes: $this->typedResources(
                $projectId,
                'hcloud_storage_box',
                'storage_box_type_id',
                $locations,
                false
            ),
            primaryIps: $this->ipResources($projectId, 'hcloud_primary_ip', 'location_id', $locations),
            floatingIps: $this->ipResources($projectId, 'hcloud_floating_ip', 'home_location_id', $locations),
            serverTypePrices: $this->typePrices(
                $projectId,
                'hcloud_pricing_server_type',
                'server_type_id',
                'hcloud_server_type'
            ),
            loadBalancerTypePrices: $this->typePrices(
                $projectId,
                'hcloud_pricing_load_balancer_type',
                'load_balancer_type_id',
                'hcloud_load_balancer_type'
            ),
            storageBoxTypePrices: $this->typePrices(
                $projectId,
                'hcloud_pricing_storage_box_type',
                'storage_box_type_id',
                'hcloud_storage_box_type'
            ),
            primaryIpPrices: $this->ipPrices($projectId, 'hcloud_pricing_primary_ip'),
            floatingIpPrices: $this->ipPrices($projectId, 'hcloud_pricing_floating_ip'),
            volumePricePerGbMonth: isset($pricing['volume_price_per_gb_month_net'])
                ? (string) $pricing['volume_price_per_gb_month_net']
                : null,
            volumePricePerGbMonthGross: isset($pricing['volume_price_per_gb_month_gross'])
                ? (string) $pricing['volume_price_per_gb_month_gross']
                : null,
            imagePricePerGbMonth: isset($pricing['image_price_per_gb_month_net'])
                ? (string) $pricing['image_price_per_gb_month_net']
                : null,
            imagePricePerGbMonthGross: isset($pricing['image_price_per_gb_month_gross'])
                ? (string) $pricing['image_price_per_gb_month_gross']
                : null,
            volumeGigabytes: (float) $this->db->fetchScalar(
                (new Select())->from('hcloud_volume')->columns(['COALESCE(SUM(size), 0)'])
                    ->where(['project_id = ?' => $projectId])
            ),
            volumeCount: $this->countOf('hcloud_volume', $projectId),
            snapshotGigabytes: (float) $this->db->fetchScalar(
                (new Select())->from('hcloud_image')->columns(['COALESCE(SUM(image_size), 0)'])
                    ->where(['project_id = ?' => $projectId, 'type IN (?)' => ['snapshot', 'backup']])
            ),
            snapshotCount: $this->countOf('hcloud_image', $projectId, ['type IN (?)' => ['snapshot', 'backup']])
        );
    }

    /**
     * @return array<int, string>
     */
    private function locationNames(int $projectId): array
    {
        $select = (new Select())
            ->from('hcloud_location')
            ->columns(['id', 'name'])
            ->where(['project_id = ?' => $projectId]);

        $names = [];
        foreach ($this->rows($select) as $row) {
            $names[(int) ($row['id'] ?? 0)] = (string) ($row['name'] ?? '');
        }

        return $names;
    }

    /**
     * @param array<int, string> $locations
     *
     * @return list<array{type_id: int|null, location: string|null, outgoing_traffic?: int|null,
     *     included_traffic?: int|null}>
     */
    private function typedResources(
        int $projectId,
        string $table,
        string $typeColumn,
        array $locations,
        bool $withTraffic
    ): array {
        $columns = [$typeColumn, 'location_id'];
        if ($withTraffic) {
            $columns[] = 'outgoing_traffic';
            $columns[] = 'included_traffic';
        }

        $select = (new Select())->from($table)->columns($columns)->where(['project_id = ?' => $projectId]);

        $rows = [];
        foreach ($this->rows($select) as $row) {
            $entry = [
                'type_id' => isset($row[$typeColumn]) ? (int) $row[$typeColumn] : null,
                'location' => $locations[(int) ($row['location_id'] ?? 0)] ?? null,
            ];

            if ($withTraffic) {
                $entry['outgoing_traffic'] = isset($row['outgoing_traffic'])
                    ? (int) $row['outgoing_traffic']
                    : null;
                $entry['included_traffic'] = isset($row['included_traffic'])
                    ? (int) $row['included_traffic']
                    : null;
            }

            $rows[] = $entry;
        }

        return $rows;
    }

    /**
     * @param array<int, string> $locations
     *
     * @return list<array{type: string|null, location: string|null}>
     */
    private function ipResources(int $projectId, string $table, string $locationColumn, array $locations): array
    {
        $select = (new Select())
            ->from($table)
            ->columns(['type', $locationColumn])
            ->where(['project_id = ?' => $projectId]);

        $rows = [];
        foreach ($this->rows($select) as $row) {
            $rows[] = [
                'type' => isset($row['type']) ? (string) $row['type'] : null,
                'location' => $locations[(int) ($row[$locationColumn] ?? 0)] ?? null,
            ];
        }

        return $rows;
    }

    /**
     * @return array<string, array{net: string|null, gross: string|null, name: string|null, per_tb?: string|null}>
     */
    private function typePrices(int $projectId, string $table, string $idColumn, string $typeTable): array
    {
        $names = [];
        $nameSelect = (new Select())
            ->from($typeTable)
            ->columns(['id', 'name'])
            ->where(['project_id = ?' => $projectId]);
        foreach ($this->rows($nameSelect) as $row) {
            $names[(int) ($row['id'] ?? 0)] = (string) ($row['name'] ?? '');
        }

        $select = (new Select())->from($table)->columns('*')->where(['project_id = ?' => $projectId]);

        $prices = [];
        foreach ($this->rows($select) as $row) {
            $typeId = (int) ($row[$idColumn] ?? 0);
            $key = $typeId . '@' . (string) ($row['location_name'] ?? '');

            $prices[$key] = [
                'net' => isset($row['price_monthly_net']) ? (string) $row['price_monthly_net'] : null,
                'gross' => isset($row['price_monthly_gross']) ? (string) $row['price_monthly_gross'] : null,
                'name' => $names[$typeId] ?? null,
                'per_tb' => isset($row['price_per_tb_traffic_net'])
                    ? (string) $row['price_per_tb_traffic_net']
                    : null,
            ];
        }

        return $prices;
    }

    /**
     * @return array<string, array{net: string|null, gross: string|null}>
     */
    private function ipPrices(int $projectId, string $table): array
    {
        $select = (new Select())->from($table)->columns('*')->where(['project_id = ?' => $projectId]);

        $prices = [];
        foreach ($this->rows($select) as $row) {
            $key = (string) ($row['type'] ?? '') . '@' . (string) ($row['location_name'] ?? '');
            $prices[$key] = [
                'net' => isset($row['price_monthly_net']) ? (string) $row['price_monthly_net'] : null,
                'gross' => isset($row['price_monthly_gross']) ? (string) $row['price_monthly_gross'] : null,
            ];
        }

        return $prices;
    }
}
