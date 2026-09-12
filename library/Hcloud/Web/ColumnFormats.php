<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Web;

/**
 * How a stored column is rendered. Resolved by table and column rather than by column alone,
 * because the same name carries different units in different tables: hcloud_volume.size is a
 * gigabyte count while hcloud_storage_box_type.size is a byte count.
 */
final class ColumnFormats
{
    private const PER_TABLE = [
        'hcloud_storage_box_type' => [
            'size' => ColumnFormat::Bytes,
        ],
        'hcloud_object_storage_bucket' => [
            'size' => ColumnFormat::Bytes,
        ],
    ];

    private const BY_COLUMN = [
        'included_traffic' => ColumnFormat::Bytes,
        'ingoing_traffic' => ColumnFormat::Bytes,
        'outgoing_traffic' => ColumnFormat::Bytes,
        'stats_size' => ColumnFormat::Bytes,
        'stats_size_data' => ColumnFormat::Bytes,
        'stats_size_filesystem' => ColumnFormat::Bytes,
        'stats_size_snapshots' => ColumnFormat::Bytes,

        'disk' => ColumnFormat::Gigabytes,
        'disk_size' => ColumnFormat::Gigabytes,
        'image_size' => ColumnFormat::Gigabytes,
        'memory' => ColumnFormat::Gigabytes,
        'primary_disk_size' => ColumnFormat::Gigabytes,
        'size' => ColumnFormat::Gigabytes,

        'access_reachable_externally' => ColumnFormat::YesNo,
        'access_readonly' => ColumnFormat::YesNo,
        'access_samba_enabled' => ColumnFormat::YesNo,
        'access_ssh_enabled' => ColumnFormat::YesNo,
        'access_webdav_enabled' => ColumnFormat::YesNo,
        'access_zfs_enabled' => ColumnFormat::YesNo,
        'auto_delete' => ColumnFormat::YesNo,
        'available' => ColumnFormat::YesNo,
        'blocked' => ColumnFormat::YesNo,
        'enabled' => ColumnFormat::YesNo,
        'expose_routes_to_vswitch' => ColumnFormat::YesNo,
        'health_check_http_tls' => ColumnFormat::YesNo,
        'http_redirect_http' => ColumnFormat::YesNo,
        'http_sticky_sessions' => ColumnFormat::YesNo,
        'is_automatic' => ColumnFormat::YesNo,
        'locked' => ColumnFormat::YesNo,
        'protection_change' => ColumnFormat::YesNo,
        'protection_delete' => ColumnFormat::YesNo,
        'protection_rebuild' => ColumnFormat::YesNo,
        'proxyprotocol' => ColumnFormat::YesNo,
        'public_enabled' => ColumnFormat::YesNo,
        'rapid_deploy' => ColumnFormat::YesNo,
        'recommended' => ColumnFormat::YesNo,
        'rescue_enabled' => ColumnFormat::YesNo,
        'success' => ColumnFormat::YesNo,
        'usage_complete' => ColumnFormat::YesNo,
        'use_private_ip' => ColumnFormat::YesNo,

        'progress' => ColumnFormat::Percent,
        'server_backup_percentage' => ColumnFormat::Percent,
        'vat_rate' => ColumnFormat::Percent,

        'health_check_interval' => ColumnFormat::Seconds,
        'health_check_timeout' => ColumnFormat::Seconds,
        'http_cookie_lifetime' => ColumnFormat::Seconds,
        'http_timeout_idle' => ColumnFormat::Seconds,
        'ttl' => ColumnFormat::Seconds,

        'duration_ms' => ColumnFormat::Milliseconds,

        'labels' => ColumnFormat::Labels,
    ];

    private const PRICE_SUFFIXES = ['_net', '_gross'];

    public static function for(?string $table, string $column): ColumnFormat
    {
        $perTable = $table === null ? null : (self::PER_TABLE[$table][$column] ?? null);
        if ($perTable !== null) {
            return $perTable;
        }

        $byColumn = self::BY_COLUMN[$column] ?? null;
        if ($byColumn !== null) {
            return $byColumn;
        }

        foreach (self::PRICE_SUFFIXES as $suffix) {
            if (str_ends_with($column, $suffix)) {
                return ColumnFormat::Price;
            }
        }

        return ColumnFormat::Text;
    }
}
