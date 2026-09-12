<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Controllers;

use Icinga\Module\Hcloud\Enum\VolumeStatus;
use Icinga\Module\Hcloud\Model\Volume;
use Icinga\Module\Hcloud\Web\ResourceController;

class VolumesController extends ResourceController
{
    protected function modelClass(): string
    {
        return Volume::class;
    }

    protected function listTitle(): string
    {
        return $this->translate('Volumes');
    }

    /**
     * @return list<string>
     */
    protected function tableColumns(): array
    {
        return ['name', 'status', 'size', 'format', 'server_id', 'created', 'labels'];
    }

    /**
     * @return array<string, class-string>
     */
    protected function enumColumns(): array
    {
        return ['status' => VolumeStatus::class];
    }

    protected function detailUrl(): ?string
    {
        return 'hcloud/volume';
    }
}
