<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Controllers;

use Icinga\Module\Hcloud\Api\ProjectRegistry;
use Icinga\Module\Hcloud\Db\Repository;
use Icinga\Module\Hcloud\Web\Controller;
use Icinga\Module\Hcloud\Web\Widget\ProjectSummary;

class DashboardController extends Controller
{
    public function indexAction(): void
    {
        $this->setTitle($this->translate('Hetzner Cloud'));

        if (! $this->databaseIsReady()) {
            return;
        }

        $repository = new Repository($this->db());
        $synced = $repository->projects();
        $syncedKeys = array_column($synced, 'config_key');

        $unsynced = [];
        foreach (ProjectRegistry::enabled() as $configured) {
            if (! in_array($configured->key, $syncedKeys, true)) {
                $unsynced[] = $configured->name;
            }
        }

        $projects = [];

        foreach ($synced as $project) {
            $id = $project['id'];

            $projects[] = [
                'name' => $project['name'],
                'last_sync' => $repository->lastSuccessfulSync($id),
                'counts' => [
                    $this->translate('Servers') => $repository->countOf('hcloud_server', $id),
                    $this->translate('Volumes') => $repository->countOf('hcloud_volume', $id),
                    $this->translate('Load Balancers') => $repository->countOf('hcloud_load_balancer', $id),
                    $this->translate('Networks') => $repository->countOf('hcloud_network', $id),
                    $this->translate('Firewalls') => $repository->countOf('hcloud_firewall', $id),
                    $this->translate('DNS Zones') => $repository->countOf('hcloud_zone', $id),
                    $this->translate('Storage Boxes') => $repository->countOf('hcloud_storage_box', $id),
                    $this->translate('Buckets') => $repository->countOf('hcloud_object_storage_bucket', $id),
                ],
                'problems' => array_filter([
                    $this->translate('Servers not running') => $repository->countOf(
                        'hcloud_server',
                        $id,
                        ['status != ?' => 'running']
                    ),
                    $this->translate('Unhealthy LB targets') => $repository->countOf(
                        'hcloud_load_balancer_target_health',
                        $id,
                        ['status = ?' => 'unhealthy']
                    ),
                    $this->translate('Pending firewall bindings') => $repository->countOf(
                        'hcloud_server_firewall',
                        $id,
                        ['status = ?' => 'pending']
                    ),
                    $this->translate('Failed actions') => $repository->countOf(
                        'hcloud_action',
                        $id,
                        ['status = ?' => 'error']
                    ),
                    $this->translate('Blocked IPs') => $repository->countOf(
                        'hcloud_primary_ip',
                        $id,
                        ['blocked = ?' => 1]
                    ),
                ]),
            ];
        }

        $this->addContent(new ProjectSummary($projects, $unsynced));
    }
}
