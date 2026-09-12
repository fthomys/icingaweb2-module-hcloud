<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Controllers;

use Icinga\Module\Hcloud\Model\LoadBalancer;
use Icinga\Module\Hcloud\Web\ResourceController;

class LoadBalancersController extends ResourceController
{
    protected function modelClass(): string
    {
        return LoadBalancer::class;
    }

    protected function listTitle(): string
    {
        return $this->translate('Load Balancers');
    }

    /**
     * @return list<string>
     */
    protected function tableColumns(): array
    {
        return ['name', 'algorithm_type', 'public_ipv4', 'outgoing_traffic', 'included_traffic', 'created', 'labels'];
    }


    protected function detailUrl(): ?string
    {
        return 'hcloud/load-balancer';
    }
}
