<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\ProvidedHook\Director;

class VolumeImportSource extends BaseImportSource
{
    public function getName(): string
    {
        return 'Hetzner Cloud Volumes';
    }

    protected function table(): string
    {
        return 'hcloud_volume';
    }

    /**
     * @return list<string>
     */
    protected function columns(): array
    {
        return ['id', 'name', 'status', 'size', 'format', 'server_id', 'linux_device', 'location_id', 'labels'];
    }
}
