<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Controllers;

use Icinga\Module\Hcloud\Model\LoadBalancer;
use Icinga\Module\Hcloud\Web\DetailController;

class LoadBalancerController extends DetailController
{
    protected function modelClass(): string
    {
        return LoadBalancer::class;
    }

    /**
     * @return array<string, array{table: string, foreign: string, columns: list<string>}>
     */
    protected function relatedTables(): array
    {
        return [
            $this->translate('Services') => [
                'table' => 'hcloud_load_balancer_service',
                'foreign' => 'load_balancer_id',
                'columns' => ['listen_port', 'protocol', 'destination_port', 'health_check_protocol'],
            ],
            $this->translate('Targets') => [
                'table' => 'hcloud_load_balancer_target',
                'foreign' => 'load_balancer_id',
                'columns' => ['target_index', 'type', 'server_id', 'server_ip', 'label_selector'],
            ],
            $this->translate('Target Health') => [
                'table' => 'hcloud_load_balancer_target_health',
                'foreign' => 'load_balancer_id',
                'columns' => ['target_index', 'listen_port', 'status', 'detail', 'http_status_code'],
            ],
        ];
    }

    protected function metricResourceType(): ?string
    {
        return 'load_balancer';
    }
}
