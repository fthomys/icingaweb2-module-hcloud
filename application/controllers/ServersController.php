<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Controllers;

use Icinga\Module\Hcloud\Enum\ServerStatus;
use Icinga\Module\Hcloud\Model\Server;
use Icinga\Module\Hcloud\Web\ResourceController;

class ServersController extends ResourceController
{
    protected function modelClass(): string
    {
        return Server::class;
    }

    protected function listTitle(): string
    {
        return $this->translate('Servers');
    }

    /**
     * @return list<string>
     */
    protected function tableColumns(): array
    {
        return ['name', 'status', 'outgoing_traffic', 'included_traffic', 'locked', 'created', 'labels'];
    }

    /**
     * @return array<string, class-string>
     */
    protected function enumColumns(): array
    {
        return ['status' => ServerStatus::class];
    }

    protected function detailUrl(): ?string
    {
        return 'hcloud/server';
    }
}
