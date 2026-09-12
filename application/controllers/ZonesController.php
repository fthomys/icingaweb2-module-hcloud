<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Controllers;

use Icinga\Module\Hcloud\Enum\DelegationStatus;
use Icinga\Module\Hcloud\Enum\ZoneStatus;
use Icinga\Module\Hcloud\Model\Zone;
use Icinga\Module\Hcloud\Web\ResourceController;

class ZonesController extends ResourceController
{
    protected function modelClass(): string
    {
        return Zone::class;
    }

    protected function listTitle(): string
    {
        return $this->translate('DNS Zones');
    }

    /**
     * @return list<string>
     */
    protected function tableColumns(): array
    {
        return ['name', 'status', 'mode', 'delegation_status', 'record_count', 'ttl', 'labels'];
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

    protected function detailUrl(): ?string
    {
        return 'hcloud/zone';
    }
}
