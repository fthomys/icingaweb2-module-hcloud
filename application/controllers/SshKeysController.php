<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Controllers;

use Icinga\Module\Hcloud\Model\SshKey;
use Icinga\Module\Hcloud\Web\ResourceController;

class SshKeysController extends ResourceController
{
    protected function modelClass(): string
    {
        return SshKey::class;
    }

    protected function listTitle(): string
    {
        return $this->translate('SSH Keys');
    }

    /**
     * @return list<string>
     */
    protected function tableColumns(): array
    {
        return ['name', 'fingerprint', 'created', 'labels'];
    }


    protected function detailUrl(): ?string
    {
        return '';
    }
}
