<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Controllers;

use Icinga\Module\Hcloud\Model\FloatingIp;
use Icinga\Module\Hcloud\Web\ResourceController;

class FloatingIpsController extends ResourceController
{
    protected function modelClass(): string
    {
        return FloatingIp::class;
    }

    protected function listTitle(): string
    {
        return $this->translate('Floating IP Addresses');
    }

    /**
     * @return list<string>
     */
    protected function tableColumns(): array
    {
        return ['ip', 'type', 'name', 'server_id', 'blocked', 'description', 'labels'];
    }

    protected function detailUrl(): ?string
    {
        return null;
    }
}
