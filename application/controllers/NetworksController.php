<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Controllers;

use Icinga\Module\Hcloud\Model\Network;
use Icinga\Module\Hcloud\Web\ResourceController;

class NetworksController extends ResourceController
{
    protected function modelClass(): string
    {
        return Network::class;
    }

    protected function listTitle(): string
    {
        return $this->translate('Networks');
    }

    /**
     * @return list<string>
     */
    protected function tableColumns(): array
    {
        return ['name', 'ip_range', 'expose_routes_to_vswitch', 'created', 'labels'];
    }


    protected function detailUrl(): ?string
    {
        return 'hcloud/network';
    }
}
