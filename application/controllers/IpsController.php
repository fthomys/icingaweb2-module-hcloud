<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Controllers;

use Icinga\Module\Hcloud\Enum\PrimaryIpAssigneeType;
use Icinga\Module\Hcloud\Model\PrimaryIp;
use Icinga\Module\Hcloud\Web\ResourceController;

class IpsController extends ResourceController
{
    protected function modelClass(): string
    {
        return PrimaryIp::class;
    }

    protected function listTitle(): string
    {
        return $this->translate('Primary IP Addresses');
    }

    /**
     * @return list<string>
     */
    protected function tableColumns(): array
    {
        return ['ip', 'type', 'name', 'assignee_type', 'assignee_id', 'blocked', 'auto_delete', 'labels'];
    }

    /**
     * @return array<string, class-string>
     */
    protected function enumColumns(): array
    {
        return ['assignee_type' => PrimaryIpAssigneeType::class];
    }

    protected function detailUrl(): ?string
    {
        return null;
    }
}
