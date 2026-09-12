<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Controllers;

use Icinga\Module\Hcloud\Enum\VolumeStatus;
use Icinga\Module\Hcloud\Model\Volume;
use Icinga\Module\Hcloud\Web\DetailController;

class VolumeController extends DetailController
{
    protected function modelClass(): string
    {
        return Volume::class;
    }

    /**
     * @return array<string, class-string>
     */
    protected function enumColumns(): array
    {
        return ['status' => VolumeStatus::class];
    }
}
