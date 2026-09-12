<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Model;

use ipl\Orm\Model;

class CertificateUsedBy extends Model
{
    public function getTableName(): string
    {
        return 'hcloud_certificate_used_by';
    }

    /**
     * @return list<string>
     */
    public function getKeyName(): array
    {
        return [
            'project_id',
            'certificate_id',
            'used_by_type',
            'used_by_id',
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
            'used_by_type' => mt('hcloud', 'Used By Type'),
            'used_by_id' => mt('hcloud', 'Used By ID'),
        ];
    }
}
