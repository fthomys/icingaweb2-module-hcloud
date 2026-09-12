<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Controllers;

use Icinga\Module\Hcloud\Model\ObjectStorageBucket;
use Icinga\Module\Hcloud\Web\ResourceController;

class BucketsController extends ResourceController
{
    protected function modelClass(): string
    {
        return ObjectStorageBucket::class;
    }

    protected function listTitle(): string
    {
        return $this->translate('Object Storage');
    }

    /**
     * @return list<string>
     */
    protected function tableColumns(): array
    {
        return ['name', 'location', 'size', 'object_count', 'created', 'usage_scanned'];
    }

    protected function detailUrl(): ?string
    {
        return 'hcloud/bucket';
    }
}
