<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Sync;

use Icinga\Module\Hcloud\Sync\Mapper\ActionMapper;
use Icinga\Module\Hcloud\Sync\Mapper\CertificateMapper;
use Icinga\Module\Hcloud\Sync\Mapper\FirewallMapper;
use Icinga\Module\Hcloud\Sync\Mapper\FloatingIpMapper;
use Icinga\Module\Hcloud\Sync\Mapper\ImageMapper;
use Icinga\Module\Hcloud\Sync\Mapper\IsoImageMapper;
use Icinga\Module\Hcloud\Sync\Mapper\LoadBalancerMapper;
use Icinga\Module\Hcloud\Sync\Mapper\LoadBalancerTypeMapper;
use Icinga\Module\Hcloud\Sync\Mapper\LocationMapper;
use Icinga\Module\Hcloud\Sync\Mapper\Mapper;
use Icinga\Module\Hcloud\Sync\Mapper\NetworkMapper;
use Icinga\Module\Hcloud\Sync\Mapper\PlacementGroupMapper;
use Icinga\Module\Hcloud\Sync\Mapper\PrimaryIpMapper;
use Icinga\Module\Hcloud\Sync\Mapper\ServerMapper;
use Icinga\Module\Hcloud\Sync\Mapper\ServerTypeMapper;
use Icinga\Module\Hcloud\Sync\Mapper\SshKeyMapper;
use Icinga\Module\Hcloud\Sync\Mapper\StorageBoxMapper;
use Icinga\Module\Hcloud\Sync\Mapper\StorageBoxSnapshotMapper;
use Icinga\Module\Hcloud\Sync\Mapper\StorageBoxSubaccountMapper;
use Icinga\Module\Hcloud\Sync\Mapper\StorageBoxTypeMapper;
use Icinga\Module\Hcloud\Sync\Mapper\VolumeMapper;
use Icinga\Module\Hcloud\Sync\Mapper\ZoneMapper;
use Icinga\Module\Hcloud\Sync\Mapper\ZoneRrsetMapper;

final class MapperRegistry
{
    /**
     * @return list<class-string<Mapper>>
     */
    public static function collections(): array
    {
        return [
            LocationMapper::class,
            ServerTypeMapper::class,
            LoadBalancerTypeMapper::class,
            StorageBoxTypeMapper::class,
            ImageMapper::class,
            IsoImageMapper::class,
            PlacementGroupMapper::class,
            SshKeyMapper::class,
            NetworkMapper::class,
            FirewallMapper::class,
            CertificateMapper::class,
            VolumeMapper::class,
            LoadBalancerMapper::class,
            ServerMapper::class,
            FloatingIpMapper::class,
            PrimaryIpMapper::class,
            ZoneMapper::class,
            StorageBoxMapper::class,
        ];
    }

    /**
     * @return array<string, class-string<Mapper>>
     */
    public static function perParent(): array
    {
        return [
            'hcloud_zone' => ZoneRrsetMapper::class,
            'hcloud_storage_box_subaccount' => StorageBoxSubaccountMapper::class,
            'hcloud_storage_box_snapshot' => StorageBoxSnapshotMapper::class,
        ];
    }

    /**
     * @return list<class-string<Mapper>>
     */
    public static function all(): array
    {
        return array_merge(
            self::collections(),
            [ZoneRrsetMapper::class, StorageBoxSubaccountMapper::class, StorageBoxSnapshotMapper::class],
            [ActionMapper::class]
        );
    }

    /**
     * @return list<string>
     */
    public static function actionEndpoints(): array
    {
        return [
            '/servers/actions',
            '/volumes/actions',
            '/load_balancers/actions',
            '/firewalls/actions',
            '/networks/actions',
            '/images/actions',
            '/certificates/actions',
            '/floating_ips/actions',
            '/primary_ips/actions',
            '/zones/actions',
        ];
    }

    /**
     * @return list<string>
     */
    public static function storageActionEndpoints(): array
    {
        return ['/storage_boxes/actions'];
    }
}
