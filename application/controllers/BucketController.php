<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Controllers;

use Icinga\Module\Hcloud\Model\ObjectStorageBucket;
use Icinga\Module\Hcloud\Web\DetailController;

class BucketController extends DetailController
{
    protected function modelClass(): string
    {
        return ObjectStorageBucket::class;
    }
}
