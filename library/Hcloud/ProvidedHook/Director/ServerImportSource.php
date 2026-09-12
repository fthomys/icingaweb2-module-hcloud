<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\ProvidedHook\Director;

class ServerImportSource extends BaseImportSource
{
    public function getName(): string
    {
        return 'Hetzner Cloud Servers';
    }

    protected function table(): string
    {
        return 'hcloud_server';
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
            'created',
            'locked',
            'rescue_enabled',
            'backup_window',
            'server_type_id',
            'location_id',
            'image_id',
            'labels',
        ];
    }
}
