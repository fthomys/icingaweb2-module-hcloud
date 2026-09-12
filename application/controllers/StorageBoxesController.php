<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Controllers;

use Icinga\Module\Hcloud\Enum\StorageBoxStatus;
use Icinga\Module\Hcloud\Model\StorageBox;
use Icinga\Module\Hcloud\Web\ResourceController;

class StorageBoxesController extends ResourceController
{
    protected function modelClass(): string
    {
        return StorageBox::class;
    }

    protected function listTitle(): string
    {
        return $this->translate('Storage Boxes');
    }

    /**
     * @return list<string>
     */
    protected function tableColumns(): array
    {
        return ['name', 'status', 'server', 'stats_size', 'stats_size_data', 'stats_size_snapshots', 'labels'];
    }

    /**
     * @return array<string, class-string>
     */
    protected function enumColumns(): array
    {
        return ['status' => StorageBoxStatus::class];
    }

    protected function detailUrl(): ?string
    {
        return 'hcloud/storage-box';
    }
}
