<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Controllers;

use Icinga\Module\Hcloud\Enum\ImageStatus;
use Icinga\Module\Hcloud\Model\Image;
use Icinga\Module\Hcloud\Web\DetailController;

class ImageController extends DetailController
{
    protected function modelClass(): string
    {
        return Image::class;
    }

    /**
     * @return array<string, class-string>
     */
    protected function enumColumns(): array
    {
        return ['status' => ImageStatus::class];
    }
}
