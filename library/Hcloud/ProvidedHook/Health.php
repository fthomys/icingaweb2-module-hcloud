<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\ProvidedHook;

use Icinga\Application\Hook\HealthHook;
use Icinga\Module\Hcloud\Common\Database;
use Icinga\Module\Hcloud\Db\Store;
use ipl\Sql\Select;
use ipl\Web\Url;
use Throwable;

class Health extends HealthHook
{
    private const STALE_AFTER_MINUTES = 60;

    public function getName(): string
    {
        return mt(Database::MODULE_NAME, 'Hetzner Cloud');
    }

    public function getUrl(): Url
    {
        return Url::fromPath('hcloud/sync');
    }

    public function checkHealth(): void
    {
        if (Database::resourceName() === null) {
            $this->setState(self::STATE_UNKNOWN);
            $this->setMessage(mt(Database::MODULE_NAME, 'No database resource is configured.'));

            return;
        }

        try {
            $this->inspect();
        } catch (Throwable $e) {
            $this->setState(self::STATE_UNKNOWN);
            $this->setMessage(sprintf(
                mt(Database::MODULE_NAME, 'Cannot read the Hetzner Cloud database: %s'),
                $e->getMessage()
            ));
        }
    }

    private function inspect(): void
    {
        $db = Database::get();

        $projects = (int) $db->fetchScalar(
            (new Select())->from('hcloud_project')->columns(['COUNT(*)'])->where(['enabled = ?' => 1])
        );

        if ($projects === 0) {
            $this->setState(self::STATE_UNKNOWN);
            $this->setMessage(mt(Database::MODULE_NAME, 'No Hetzner Cloud project is configured.'));

            return;
        }

        $failedProjects = (int) $db->fetchScalar(
            (new Select())
                ->from('hcloud_sync_run')
                ->columns(['COUNT(DISTINCT project_id)'])
                ->where(['status = ?' => Store::STATUS_ERROR])
        );

        $lastSync = $db->fetchScalar(
            (new Select())
                ->from('hcloud_sync_run')
                ->columns(['MAX(ended)'])
                ->where(['status = ?' => Store::STATUS_SUCCESS])
        );

        $failedActions = (int) $db->fetchScalar(
            (new Select())->from('hcloud_action')->columns(['COUNT(*)'])->where(['status = ?' => 'error'])
        );

        $this->setMetrics([
            'projects' => $projects,
            'failed_actions' => $failedActions,
        ]);

        if (! is_string($lastSync) || $lastSync === '') {
            $this->setState(self::STATE_WARNING);
            $this->setMessage(mt(Database::MODULE_NAME, 'No successful sync has run yet.'));

            return;
        }

        $ageMinutes = (int) floor((time() - (int) strtotime($lastSync . ' UTC')) / 60);

        if ($ageMinutes > self::STALE_AFTER_MINUTES) {
            $this->setState(self::STATE_WARNING);
            $this->setMessage(sprintf(
                mt(Database::MODULE_NAME, 'The last successful sync finished %d minutes ago.'),
                $ageMinutes
            ));

            return;
        }

        if ($failedProjects > 0) {
            $this->setState(self::STATE_WARNING);
            $this->setMessage(sprintf(
                mt(Database::MODULE_NAME, '%d of %d projects failed to sync.'),
                $failedProjects,
                $projects
            ));

            return;
        }

        $this->setState(self::STATE_OK);
        $this->setMessage(sprintf(
            mt(Database::MODULE_NAME, '%d projects synced, last sync %d minutes ago.'),
            $projects,
            $ageMinutes
        ));
    }
}
