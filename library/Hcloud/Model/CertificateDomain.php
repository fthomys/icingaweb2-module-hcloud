<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use ipl\Orm\Model;

class CertificateDomain extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_certificate_domain';
    }

    /**
     * @return list<string>
     */
    public function getKeyName(): array
    {
        return [
            'project_id',
            'certificate_id',
            'domain_name',
        ];
    }

    /**
     * @return list<string>
     */
    public function getColumns(): array
    {
        return [
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getColumnDefinitions(): array
    {
        return [
            'certificate_id' => mt('hcloud', 'Certificate ID'),
            'domain_name' => mt('hcloud', 'Domain Name'),
        ];
    }
}
