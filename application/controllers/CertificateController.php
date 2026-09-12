<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Controllers;

use Icinga\Module\Hcloud\Model\Certificate;
use Icinga\Module\Hcloud\Web\DetailController;

class CertificateController extends DetailController
{
    protected function modelClass(): string
    {
        return Certificate::class;
    }

    /**
     * @return array<string, array{table: string, foreign: string, columns: list<string>}>
     */
    protected function relatedTables(): array
    {
        return [
            $this->translate('Domains') => [
                'table' => 'hcloud_certificate_domain',
                'foreign' => 'certificate_id',
                'columns' => ['domain_name'],
            ],
            $this->translate('Used By') => [
                'table' => 'hcloud_certificate_used_by',
                'foreign' => 'certificate_id',
                'columns' => ['used_by_type', 'used_by_id'],
            ],
        ];
    }
}
