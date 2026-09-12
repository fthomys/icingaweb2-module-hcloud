<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\ProvidedHook\Director;

class LoadBalancerImportSource extends BaseImportSource
{
    public function getName(): string
    {
        return 'Hetzner Cloud Load Balancers';
    }

    protected function table(): string
    {
        return 'hcloud_load_balancer';
    }

    /**
     * @return list<string>
     */
    protected function columns(): array
    {
        return [
            'id',
            'name',
            'algorithm_type',
            'public_ipv4',
            'public_ipv6',
            'location_id',
            'load_balancer_type_id',
            'labels',
        ];
    }
}
