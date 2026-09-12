<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Web;

use PDO;
use ipl\Sql\Connection;
use ipl\Sql\Select;
use ipl\Web\Url;

/**
 * Turns the stored foreign ids of a row into the names they point at, so a detail view shows
 * "fsn1" where the column holds 1. Declared per column rather than read from the ORM relations,
 * because most of these columns carry no relation and adding one only to render a label would
 * put a join on every list query that uses the model.
 */
final class ReferenceResolver
{
    private const TARGETS = [
        'certificate_id' => ['hcloud_certificate', ['name']],
        'created_from_id' => ['hcloud_image', ['name', 'description']],
        'firewall_id' => ['hcloud_firewall', ['name']],
        'floating_ip_id' => ['hcloud_floating_ip', ['ip', 'name']],
        'home_location_id' => ['hcloud_location', ['name']],
        'image_id' => ['hcloud_image', ['name', 'description']],
        'iso_id' => ['hcloud_iso', ['name', 'description']],
        'load_balancer_id' => ['hcloud_load_balancer', ['name']],
        'load_balancer_type_id' => ['hcloud_load_balancer_type', ['name']],
        'location_id' => ['hcloud_location', ['name']],
        'network_id' => ['hcloud_network', ['name']],
        'placement_group_id' => ['hcloud_placement_group', ['name']],
        'primary_ip_id' => ['hcloud_primary_ip', ['ip', 'name']],
        'server_id' => ['hcloud_server', ['name']],
        'server_type_id' => ['hcloud_server_type', ['name']],
        'storage_box_id' => ['hcloud_storage_box', ['name']],
        'storage_box_type_id' => ['hcloud_storage_box_type', ['name']],
        'zone_id' => ['hcloud_zone', ['name']],
    ];

    private const ROUTES = [
        'hcloud_certificate' => 'hcloud/certificate',
        'hcloud_firewall' => 'hcloud/firewall',
        'hcloud_image' => 'hcloud/image',
        'hcloud_load_balancer' => 'hcloud/load-balancer',
        'hcloud_network' => 'hcloud/network',
        'hcloud_server' => 'hcloud/server',
        'hcloud_storage_box' => 'hcloud/storage-box',
        'hcloud_volume' => 'hcloud/volume',
        'hcloud_zone' => 'hcloud/zone',
    ];

    public function __construct(private readonly Connection $db)
    {
    }

    /**
     * @return array<string, array{0: string, 1: list<string>}>
     */
    public static function targets(): array
    {
        return self::TARGETS;
    }

    /**
     * @return array<string, string>
     */
    public static function routes(): array
    {
        return self::ROUTES;
    }

    private static function title(string $column): string
    {
        return match ($column) {
            'certificate_id' => mt('hcloud', 'Certificate'),
            'created_from_id' => mt('hcloud', 'Created From'),
            'firewall_id' => mt('hcloud', 'Firewall'),
            'floating_ip_id' => mt('hcloud', 'Floating IP'),
            'home_location_id' => mt('hcloud', 'Home Location'),
            'image_id' => mt('hcloud', 'Image'),
            'iso_id' => mt('hcloud', 'ISO'),
            'load_balancer_id' => mt('hcloud', 'Load Balancer'),
            'load_balancer_type_id' => mt('hcloud', 'Load Balancer Type'),
            'location_id' => mt('hcloud', 'Location'),
            'network_id' => mt('hcloud', 'Network'),
            'placement_group_id' => mt('hcloud', 'Placement Group'),
            'primary_ip_id' => mt('hcloud', 'Primary IP'),
            'server_id' => mt('hcloud', 'Server'),
            'server_type_id' => mt('hcloud', 'Server Type'),
            'storage_box_id' => mt('hcloud', 'Storage Box'),
            'storage_box_type_id' => mt('hcloud', 'Storage Box Type'),
            'zone_id' => mt('hcloud', 'Zone'),
            default => $column,
        };
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, Reference>
     */
    public function forRow(array $values, int $projectId): array
    {
        $references = [];

        foreach ($values as $column => $value) {
            $target = self::TARGETS[$column] ?? null;
            if ($target === null || $value === null || $value === '' || ! is_scalar($value)) {
                continue;
            }

            $reference = $this->resolve($column, $target[0], $target[1], $projectId, (string) $value);
            if ($reference !== null) {
                $references[$column] = $reference;
            }
        }

        return $references;
    }

    /**
     * @param list<string> $displayColumns
     */
    private function resolve(
        string $column,
        string $table,
        array $displayColumns,
        int $projectId,
        string $id
    ): ?Reference {
        $select = (new Select())
            ->from($table)
            ->columns($displayColumns)
            ->where(['project_id = ?' => $projectId, 'id = ?' => $id])
            ->limit(1);

        $row = null;
        foreach ($this->db->yieldAll($select, PDO::FETCH_ASSOC) as $candidate) {
            $row = is_array($candidate) ? $candidate : null;
            break;
        }

        if ($row === null) {
            return null;
        }

        $label = null;
        foreach ($displayColumns as $displayColumn) {
            $value = $row[$displayColumn] ?? null;
            if (is_scalar($value) && (string) $value !== '') {
                $label = (string) $value;
                break;
            }
        }

        if ($label === null) {
            return null;
        }

        $route = self::ROUTES[$table] ?? null;

        return new Reference(
            self::title($column),
            $label,
            $route === null ? null : Url::fromPath($route, ['project_id' => $projectId, 'id' => $id])
        );
    }
}
