<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\ProvidedHook\Director;

class StorageBoxImportSource extends BaseImportSource
{
    public function getName(): string
    {
        return 'Hetzner Cloud Storage Boxes';
    }

    protected function table(): string
    {
        return 'hcloud_storage_box';
    }

    /**
     * @return list<string>
     */
    protected function columns(): array
    {
        return [
            'id',
            'name',
            'status',
            'server',
            'username',
            'stats_size',
            'location_id',
            'storage_box_type_id',
            'labels',
        ];
    }
}
