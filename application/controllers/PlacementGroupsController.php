<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Controllers;

use Icinga\Module\Hcloud\Model\PlacementGroup;
use Icinga\Module\Hcloud\Web\ResourceController;

class PlacementGroupsController extends ResourceController
{
    protected function modelClass(): string
    {
        return PlacementGroup::class;
    }

    protected function listTitle(): string
    {
        return $this->translate('Placement Groups');
    }

    /**
     * @return list<string>
     */
    protected function tableColumns(): array
    {
        return ['name', 'type', 'created', 'labels'];
    }


    protected function detailUrl(): ?string
    {
        return '';
    }
}
