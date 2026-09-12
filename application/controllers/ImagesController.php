<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Controllers;

use Icinga\Module\Hcloud\Enum\ImageStatus;
use Icinga\Module\Hcloud\Model\Image;
use Icinga\Module\Hcloud\Web\ResourceController;

class ImagesController extends ResourceController
{
    protected function modelClass(): string
    {
        return Image::class;
    }

    protected function listTitle(): string
    {
        return $this->translate('Images');
    }

    /**
     * @return list<string>
     */
    protected function tableColumns(): array
    {
        return ['name', 'type', 'status', 'os_flavor', 'os_version', 'architecture', 'disk_size', 'created'];
    }

    /**
     * @return array<string, class-string>
     */
    protected function enumColumns(): array
    {
        return ['status' => ImageStatus::class];
    }

    protected function detailUrl(): ?string
    {
        return 'hcloud/image';
    }
}
