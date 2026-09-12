<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Controllers;

use Icinga\Module\Hcloud\Model\Firewall;
use Icinga\Module\Hcloud\Web\ResourceController;

class FirewallsController extends ResourceController
{
    protected function modelClass(): string
    {
        return Firewall::class;
    }

    protected function listTitle(): string
    {
        return $this->translate('Firewalls');
    }

    /**
     * @return list<string>
     */
    protected function tableColumns(): array
    {
        return ['name', 'created', 'labels'];
    }


    protected function detailUrl(): ?string
    {
        return 'hcloud/firewall';
    }
}
