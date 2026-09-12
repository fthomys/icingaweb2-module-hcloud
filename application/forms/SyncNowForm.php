<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Forms;

use Icinga\Application\Logger;
use Icinga\Module\Hcloud\Api\Project;
use Icinga\Module\Hcloud\Api\ProjectRegistry;
use Icinga\Module\Hcloud\Common\Database;
use Icinga\Module\Hcloud\Db\Store;
use Icinga\Module\Hcloud\Sync\SyncOptions;
use Icinga\Module\Hcloud\Sync\Syncer;
use ipl\Web\Compat\CompatForm;
use Throwable;

/**
 * The sync runs inside the request, so Icinga shows its own progress indicator until it is
 * finished and the result can be reported straight away. A full sync of a normal project is
 * a couple of seconds; very large ones belong on cron, which is what the CLI command is for.
 */
class SyncNowForm extends CompatForm
{
    private const TIME_LIMIT = 300;

    private ?string $summary = null;

    private ?string $failure = null;

    public function __construct()
    {
        $this->translationDomain = 'hcloud';
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function getFailure(): ?string
    {
        return $this->failure;
    }

    protected function assemble(): void
    {
        $projects = ProjectRegistry::enabled();

        if ($projects === []) {
            $this->addElement('submit', 'submit', [
                'label' => $this->translate('Sync Now'),
                'disabled' => true,
                'description' => $this->translate('No enabled project with a token is configured.'),
            ]);

            return;
        }

        $options = ['' => $this->translate('All projects')];
        foreach ($projects as $project) {
            $options[$project->key] = $project->name;
        }

        $this->addElement('select', 'project', [
            'label' => $this->translate('Project'),
            'options' => $options,
        ]);

        $this->addElement('checkbox', 'no_metrics', [
            'label' => $this->translate('Skip metrics'),
            'description' => $this->translate('Metrics take one API request per server and load balancer.'),
        ]);

        $this->addElement('submit', 'submit', [
            'label' => $this->translate('Sync Now'),
        ]);
    }

    public function onSuccess(): void
    {
        $key = (string) $this->getValue('project');
        $projects = ProjectRegistry::enabled();

        if ($key !== '') {
            $projects = array_values(array_filter(
                $projects,
                static fn (Project $project): bool => $project->key === $key
            ));

            if ($projects === []) {
                $this->failure = $this->translate('Unknown project.');

                return;
            }
        }

        set_time_limit(self::TIME_LIMIT);

        $options = SyncOptions::fromConfig()->with(withMetrics: ! $this->getValue('no_metrics'));
        $store = new Store(Database::get());

        $written = 0;
        $deleted = 0;
        $failures = [];

        foreach ($projects as $project) {
            try {
                $result = (new Syncer(
                    $store,
                    $project,
                    ProjectRegistry::client($project),
                    ProjectRegistry::storageClient($project),
                    $options
                ))->run();

                $written += $result->written;
                $deleted += $result->deleted;

                foreach ($result->errors as $resource => $message) {
                    $failures[] = sprintf('%s: %s: %s', $project->name, $resource, $message);
                }
            } catch (Throwable $e) {
                Logger::error('hcloud: manual sync of %s failed: %s', $project->key, $e->getMessage());
                $failures[] = sprintf('%s: %s', $project->name, $e->getMessage());
            }
        }

        $this->summary = sprintf(
            $this->translate('Synced %d projects, %d rows written, %d removed.'),
            count($projects),
            $written,
            $deleted
        );

        if ($failures !== []) {
            $this->failure = implode("\n", $failures);
        }
    }
}
