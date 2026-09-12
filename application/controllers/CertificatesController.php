<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Controllers;

use Icinga\Module\Hcloud\Model\Certificate;
use Icinga\Module\Hcloud\Web\ResourceController;

class CertificatesController extends ResourceController
{
    protected function modelClass(): string
    {
        return Certificate::class;
    }

    protected function listTitle(): string
    {
        return $this->translate('Certificates');
    }

    /**
     * @return list<string>
     */
    protected function tableColumns(): array
    {
        return ['name', 'type', 'not_valid_after', 'status_renewal', 'fingerprint', 'labels'];
    }


    protected function detailUrl(): ?string
    {
        return 'hcloud/certificate';
    }
}
