<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Controllers;

use Icinga\Module\Hcloud\Enum\DelegationStatus;
use Icinga\Module\Hcloud\Enum\ZoneStatus;
use Icinga\Module\Hcloud\Model\Zone;
use Icinga\Module\Hcloud\Web\DetailController;

class ZoneController extends DetailController
{
    protected function modelClass(): string
    {
        return Zone::class;
    }

    /**
     * @return array<string, class-string>
     */
    protected function enumColumns(): array
    {
        return [
            'status' => ZoneStatus::class,
            'delegation_status' => DelegationStatus::class,
        ];
    }

    /**
     * @return array<string, array{table: string, foreign: string, columns: list<string>}>
     */
    protected function relatedTables(): array
    {
        return [
            $this->translate('Record Sets') => [
                'table' => 'hcloud_zone_rrset',
                'foreign' => 'zone_id',
                'columns' => ['name', 'type', 'ttl'],
            ],
            $this->translate('Nameservers') => [
                'table' => 'hcloud_zone_nameserver',
                'foreign' => 'zone_id',
                'columns' => ['kind', 'address'],
            ],
        ];
    }
}
