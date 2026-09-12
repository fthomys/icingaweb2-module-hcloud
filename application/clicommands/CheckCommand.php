<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Clicommands;

use Icinga\Cli\Command;
use Icinga\Module\Hcloud\Check\CheckResult;
use Icinga\Module\Hcloud\Common\Database;
use Icinga\Module\Hcloud\Db\Repository;
use ipl\Sql\Connection;
use ipl\Sql\Select;
use Throwable;

class CheckCommand extends Command
{
    /**
     * Check that every configured project synced recently
     *
     * USAGE
     *
     *   icingacli hcloud check sync [--warning=<minutes>] [--critical=<minutes>]
     */
    public function syncAction(): void
    {
        $warning = (int) $this->params->get('warning', 30);
        $critical = (int) $this->params->get('critical', 120);

        $this->run(function (Connection $db) use ($warning, $critical): CheckResult {
            $repository = new Repository($db);
            $projects = $repository->projects();

            if ($projects === []) {
                return new CheckResult(CheckResult::UNKNOWN, 'no project configured');
            }

            $result = CheckResult::ok(sprintf('%d projects synced', count($projects)));
            $oldest = 0;
            $neverSynced = 0;

            foreach ($projects as $project) {
                $lastSync = $repository->lastSuccessfulSync($project['id']);

                if ($lastSync === null) {
                    $neverSynced++;
                    $result->raiseTo(CheckResult::CRITICAL)
                        ->addDetail(sprintf('%s: never synced', $project['name']));
                    continue;
                }

                $ageMinutes = (int) floor((time() - (int) strtotime($lastSync . ' UTC')) / 60);
                $oldest = max($oldest, $ageMinutes);

                if ($ageMinutes >= $critical) {
                    $result->raiseTo(CheckResult::CRITICAL);
                } elseif ($ageMinutes >= $warning) {
                    $result->raiseTo(CheckResult::WARNING);
                }

                $result->addDetail(sprintf('%s: %d minutes ago', $project['name'], $ageMinutes));
            }

            if ($neverSynced > 0) {
                $result->setSummary(sprintf(
                    '%d of %d projects have never been synced',
                    $neverSynced,
                    count($projects)
                ));
            } elseif ($result->getState() !== CheckResult::OK) {
                $result->setSummary(sprintf('oldest successful sync is %d minutes old', $oldest));
            }

            return $result->addPerfData('sync_age_minutes', $oldest, $warning, $critical);
        });
    }

    /**
     * Check server states
     *
     * USAGE
     *
     *   icingacli hcloud check server [--project=<key>]
     */
    public function serverAction(): void
    {
        $this->run(function (Connection $db): CheckResult {
            $total = $this->count($db, 'hcloud_server');
            $notRunning = $this->count($db, 'hcloud_server', ['status != ?' => 'running']);
            $locked = $this->count($db, 'hcloud_server', ['locked = ?' => 1]);

            $result = CheckResult::ok(sprintf('%d of %d servers running', $total - $notRunning, $total));

            if ($notRunning > 0) {
                $result->raiseTo(CheckResult::WARNING)
                    ->setSummary(sprintf('%d of %d servers are not running', $notRunning, $total));
            }

            return $result
                ->addPerfData('servers', $total)
                ->addPerfData('not_running', $notRunning)
                ->addPerfData('locked', $locked);
        });
    }

    /**
     * Check volume states and attachment
     *
     * USAGE
     *
     *   icingacli hcloud check volume [--warn-detached]
     *
     * OPTIONS
     *
     *   --warn-detached   Treat a volume that is not attached to a server as a warning
     */
    public function volumeAction(): void
    {
        $warnDetached = (bool) $this->params->get('warn-detached', false);

        $this->run(function (Connection $db) use ($warnDetached): CheckResult {
            $total = $this->count($db, 'hcloud_volume');
            $notAvailable = $this->count($db, 'hcloud_volume', ['status != ?' => 'available']);
            $detached = $this->count($db, 'hcloud_volume', 'server_id IS NULL');

            if ($total === 0) {
                return new CheckResult(CheckResult::OK, 'no volumes');
            }

            $result = CheckResult::ok(sprintf('%d of %d volumes available', $total - $notAvailable, $total));

            if ($notAvailable > 0) {
                $result->raiseTo(CheckResult::WARNING)
                    ->setSummary(sprintf('%d of %d volumes are not available', $notAvailable, $total));
            }

            if ($warnDetached && $detached > 0) {
                $result->raiseTo(CheckResult::WARNING)
                    ->setSummary(sprintf('%d of %d volumes are not attached to a server', $detached, $total));
            }

            return $result
                ->addPerfData('volumes', $total)
                ->addPerfData('not_available', $notAvailable)
                ->addPerfData('detached', $detached);
        });
    }

    /**
     * Check load balancer target health
     *
     * USAGE
     *
     *   icingacli hcloud check loadbalancer
     */
    public function loadbalancerAction(): void
    {
        $this->run(function (Connection $db): CheckResult {
            $total = $this->count($db, 'hcloud_load_balancer_target_health');
            $unhealthy = $this->count($db, 'hcloud_load_balancer_target_health', ['status = ?' => 'unhealthy']);
            $unknown = $this->count($db, 'hcloud_load_balancer_target_health', ['status = ?' => 'unknown']);

            $result = CheckResult::ok(sprintf('%d of %d targets healthy', $total - $unhealthy - $unknown, $total));

            if ($unknown > 0) {
                $result->raiseTo(CheckResult::WARNING);
            }

            if ($unhealthy > 0) {
                $result->raiseTo(CheckResult::CRITICAL)
                    ->setSummary(sprintf('%d of %d load balancer targets are unhealthy', $unhealthy, $total));
            }

            return $result
                ->addPerfData('targets', $total)
                ->addPerfData('unhealthy', $unhealthy)
                ->addPerfData('unknown', $unknown);
        });
    }

    /**
     * Check certificate expiry and managed certificate renewal
     *
     * USAGE
     *
     *   icingacli hcloud check certificate [--warning=<days>] [--critical=<days>]
     */
    public function certificateAction(): void
    {
        $warning = (int) $this->params->get('warning', 28);
        $critical = (int) $this->params->get('critical', 7);

        $this->run(function (Connection $db) use ($warning, $critical): CheckResult {
            $select = (new Select())
                ->from('hcloud_certificate')
                ->columns(['name', 'not_valid_after', 'status_renewal']);

            $result = CheckResult::ok('all certificates valid');
            $soonest = null;
            $count = 0;

            foreach ($db->fetchAll($select) as $row) {
                $count++;
                $data = (array) $row;
                $name = isset($data['name']) ? (string) $data['name'] : '';

                if (($data['status_renewal'] ?? null) === 'failed') {
                    $result->raiseTo(CheckResult::CRITICAL)
                        ->addDetail(sprintf('%s: renewal failed', $name));
                }

                $notValidAfter = $data['not_valid_after'] ?? null;
                if (! is_string($notValidAfter) || $notValidAfter === '') {
                    continue;
                }

                $days = (int) floor(((int) strtotime($notValidAfter . ' UTC') - time()) / 86400);
                $soonest = $soonest === null ? $days : min($soonest, $days);

                if ($days <= $critical) {
                    $result->raiseTo(CheckResult::CRITICAL)
                        ->addDetail(sprintf('%s expires in %d days', $name, $days));
                } elseif ($days <= $warning) {
                    $result->raiseTo(CheckResult::WARNING)
                        ->addDetail(sprintf('%s expires in %d days', $name, $days));
                }
            }

            if ($count === 0) {
                return new CheckResult(CheckResult::OK, 'no certificates');
            }

            if ($result->getState() !== CheckResult::OK && $soonest !== null) {
                $result->setSummary(sprintf('next certificate expires in %d days', $soonest));
            }

            $result->addPerfData('certificates', $count);

            return $soonest === null ? $result : $result->addPerfData('days_until_expiry', $soonest);
        });
    }

    /**
     * Check for servers whose firewall assignment has not been applied yet
     *
     * USAGE
     *
     *   icingacli hcloud check firewall
     */
    public function firewallAction(): void
    {
        $this->run(function (Connection $db): CheckResult {
            $pending = $this->count($db, 'hcloud_server_firewall', ['status = ?' => 'pending']);
            $total = $this->count($db, 'hcloud_server_firewall');

            $result = CheckResult::ok(sprintf('%d firewall bindings applied', $total));

            if ($pending > 0) {
                $result->raiseTo(CheckResult::WARNING)
                    ->setSummary(sprintf('%d of %d firewall bindings are still pending', $pending, $total));
            }

            return $result->addPerfData('bindings', $total)->addPerfData('pending', $pending);
        });
    }

    /**
     * Check DNS zone status and delegation
     *
     * USAGE
     *
     *   icingacli hcloud check zone
     */
    public function zoneAction(): void
    {
        $this->run(function (Connection $db): CheckResult {
            $total = $this->count($db, 'hcloud_zone');
            $failing = $this->count($db, 'hcloud_zone', ['status = ?' => 'error']);
            $badDelegation = $this->count($db, 'hcloud_zone', ['delegation_status IN (?)' => ['invalid', 'lame']]);

            $result = CheckResult::ok(sprintf('%d zones healthy', $total));

            if ($badDelegation > 0) {
                $result->raiseTo(CheckResult::WARNING)
                    ->setSummary(sprintf('%d zones have a broken delegation', $badDelegation));
            }

            if ($failing > 0) {
                $result->raiseTo(CheckResult::CRITICAL)
                    ->setSummary(sprintf('%d zones are in error state', $failing));
            }

            return $result
                ->addPerfData('zones', $total)
                ->addPerfData('error', $failing)
                ->addPerfData('bad_delegation', $badDelegation);
        });
    }

    /**
     * Check storage box quota
     *
     * USAGE
     *
     *   icingacli hcloud check storagebox [--warning=<percent>] [--critical=<percent>]
     */
    public function storageboxAction(): void
    {
        $warning = (int) $this->params->get('warning', 80);
        $critical = (int) $this->params->get('critical', 90);

        $this->run(function (Connection $db) use ($warning, $critical): CheckResult {
            $select = (new Select())
                ->from('hcloud_storage_box b')
                ->columns(['b.name', 'b.stats_size', 't.size'])
                ->joinLeft(
                    'hcloud_storage_box_type t',
                    'b.project_id = t.project_id AND b.storage_box_type_id = t.id'
                );

            $result = CheckResult::ok('all storage boxes below quota');
            $highest = 0.0;
            $count = 0;

            foreach ($db->fetchAll($select) as $row) {
                $count++;
                $data = (array) $row;
                $capacity = (int) ($data['size'] ?? 0);
                if ($capacity <= 0) {
                    continue;
                }

                $name = isset($data['name']) ? (string) $data['name'] : '';
                $percent = round(((int) ($data['stats_size'] ?? 0) / $capacity) * 100, 1);
                $highest = max($highest, $percent);

                if ($percent >= $critical) {
                    $result->raiseTo(CheckResult::CRITICAL)
                        ->addDetail(sprintf('%s at %.1f%%', $name, $percent));
                } elseif ($percent >= $warning) {
                    $result->raiseTo(CheckResult::WARNING)
                        ->addDetail(sprintf('%s at %.1f%%', $name, $percent));
                }
            }

            if ($count === 0) {
                return new CheckResult(CheckResult::OK, 'no storage boxes');
            }

            if ($result->getState() !== CheckResult::OK) {
                $result->setSummary(sprintf('fullest storage box at %.1f%%', $highest));
            }

            return $result
                ->addPerfData('storage_boxes', $count)
                ->addPerfData('max_usage_percent', $highest, $warning, $critical);
        });
    }

    /**
     * Check for failed Hetzner actions
     *
     * USAGE
     *
     *   icingacli hcloud check actions
     */
    /**
     * Check Object Storage buckets
     *
     * Reports the total size stored across all buckets of the configured projects. Hetzner
     * publishes no quota for Object Storage, so the thresholds are gigabytes you choose.
     *
     * USAGE
     *
     *   icingacli hcloud check objectstorage [--warning=<gb>] [--critical=<gb>]
     */
    public function objectstorageAction(): void
    {
        $warning = $this->params->get('warning');
        $critical = $this->params->get('critical');

        $this->run(function (Connection $db) use ($warning, $critical): CheckResult {
            $select = (new Select())
                ->from('hcloud_object_storage_bucket')
                ->columns(['name', 'location', 'size', 'object_count', 'usage_complete']);

            $buckets = 0;
            $objects = 0;
            $bytes = 0;
            $partial = [];

            foreach ($db->fetchAll($select) as $row) {
                $data = (array) $row;
                $buckets++;
                $objects += (int) ($data['object_count'] ?? 0);
                $bytes += (int) ($data['size'] ?? 0);

                if ((int) ($data['usage_complete'] ?? 0) !== 1) {
                    $partial[] = (string) ($data['name'] ?? '');
                }
            }

            if ($buckets === 0) {
                return new CheckResult(CheckResult::OK, 'no buckets');
            }

            $gigabytes = round($bytes / (1024 ** 3), 2);
            $result = CheckResult::ok(sprintf('%d buckets, %.2f GiB in %d objects', $buckets, $gigabytes, $objects));

            if ($critical !== null && $gigabytes >= (float) $critical) {
                $result->raiseTo(CheckResult::CRITICAL);
            } elseif ($warning !== null && $gigabytes >= (float) $warning) {
                $result->raiseTo(CheckResult::WARNING);
            }

            if ($partial !== []) {
                $result->raiseTo(CheckResult::UNKNOWN)
                    ->addDetail(sprintf('size incomplete for %s', implode(', ', $partial)));
            }

            return $result
                ->addPerfData('buckets', $buckets)
                ->addPerfData('objects', $objects)
                ->addPerfData(
                    'size_gb',
                    $gigabytes,
                    $warning === null ? null : (float) $warning,
                    $critical === null ? null : (float) $critical
                );
        });
    }

    public function actionsAction(): void
    {
        $this->run(function (Connection $db): CheckResult {
            $failed = $this->count($db, 'hcloud_action', ['status = ?' => 'error']);
            $running = $this->count($db, 'hcloud_action', ['status = ?' => 'running']);

            $result = CheckResult::ok('no failed actions');

            if ($failed > 0) {
                $result->raiseTo(CheckResult::WARNING)
                    ->setSummary(sprintf('%d actions failed within the retention window', $failed));
            }

            return $result->addPerfData('failed', $failed)->addPerfData('running', $running);
        });
    }

    /**
     * A plain string condition carries no placeholder, which is how IS NULL has to be written.
     *
     * @param array<string, mixed>|string|null $condition
     */
    private function count(Connection $db, string $table, array|string|null $condition = null): int
    {
        $select = (new Select())->from($table)->columns(['COUNT(*)']);

        if ($condition !== null) {
            $select->where($condition);
        }

        return (int) $db->fetchScalar($select);
    }

    /**
     * @param callable(Connection): CheckResult $check
     */
    private function run(callable $check): never
    {
        try {
            $result = $check(Database::get());
        } catch (Throwable $e) {
            print('UNKNOWN - ' . $e->getMessage() . "\n");

            exit(CheckResult::UNKNOWN);
        }

        print($result->render() . "\n");

        exit($result->getState());
    }
}
