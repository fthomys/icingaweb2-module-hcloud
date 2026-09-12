<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\ProvidedHook\Director;

class PrimaryIpImportSource extends BaseImportSource
{
    public function getName(): string
    {
        return 'Hetzner Cloud Primary IPs';
    }

    protected function table(): string
    {
        return 'hcloud_primary_ip';
    }

    /**
     * @return list<string>
     */
    protected function columns(): array
    {
        return ['id', 'name', 'ip', 'type', 'blocked', 'assignee_type', 'assignee_id', 'location_id', 'labels'];
    }
}
