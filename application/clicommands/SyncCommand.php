<?php

declare(strict_types=1);

namespace Icinga\Module\Hcloud\Clicommands;

use Icinga\Cli\Command;
use Icinga\Module\Hcloud\Api\Project;
use Icinga\Module\Hcloud\Api\ProjectRegistry;
use Icinga\Module\Hcloud\Common\Database;
use Icinga\Module\Hcloud\Db\Store;
use Icinga\Module\Hcloud\Sync\SyncOptions;
use Icinga\Module\Hcloud\Sync\Syncer;
use Throwable;

class SyncCommand extends Command
{
    /**
     * Pull every configured Hetzner Cloud project into the local database
     *
     * USAGE
     *
     *   icingacli hcloud sync run [options]
     *
     * OPTIONS
     *
     *   --project=<key>     Only sync the project with this config key
     *   --resource=<name>   Only sync this resource, for example servers or volumes
     *   --no-metrics        Skip the metrics pass
     *   --dry-run           Fetch everything but write nothing
     */
    public function runAction(): void
    {
        $projectKey = $this->params->get('project');
        $resource = $this->params->get('resource');
        $dryRun = (bool) $this->params->get('dry-run', false);
        $withMetrics = ! $this->params->get('no-metrics', false);

        $projects = ProjectRegistry::enabled();

        if (is_string($projectKey) && $projectKey !== '') {
            $projects = array_values(array_filter(
                $projects,
                static fn (Project $p): bool => $p->key === $projectKey
            ));

            if ($projects === []) {
                $this->abort(sprintf('No enabled project with key "%s" is configured.', $projectKey));
            }
        }

        if ($projects === []) {
            $this->abort('No Hetzner Cloud project is configured. Add one under Configuration.');
        }

        $options = SyncOptions::fromConfig()->with(
            dryRun: $dryRun,
            onlyResource: is_string($resource) && $resource !== '' ? $resource : null,
            withMetrics: $withMetrics
        );

        $store = new Store(Database::get());
        $failed = false;

        foreach ($projects as $project) {
            printf("%s (%s)\n", $project->name, $project->key);

            try {
                $syncer = new Syncer(
                    $store,
                    $project,
                    ProjectRegistry::client($project),
                    ProjectRegistry::storageClient($project),
                    $options
                );

                $result = $syncer->run();

                printf(
                    "  %d resources, %d rows written, %d removed, %d API requests\n",
                    $result->resources,
                    $result->written,
                    $result->deleted,
                    $result->apiRequests
                );

                if ($result->hasErrors()) {
                    $failed = true;
                    foreach ($result->errors as $resourceName => $message) {
                        printf("  failed: %s: %s\n", $resourceName, $message);
                    }
                }
            } catch (Throwable $e) {
                $failed = true;
                printf("  failed: %s\n", $e->getMessage());
            }
        }

        if ($dryRun) {
            print("Dry run: nothing was written.\n");
        }

        if ($failed) {
            exit(1);
        }
    }

    /**
     * List the configured Hetzner Cloud projects
     *
     * USAGE
     *
     *   icingacli hcloud sync projects
     */
    public function projectsAction(): void
    {
        $projects = ProjectRegistry::all();

        if ($projects === []) {
            print("No project configured.\n");

            return;
        }

        foreach ($projects as $project) {
            printf(
                "%-20s %-30s %s%s\n",
                $project->key,
                $project->name,
                $project->enabled ? 'enabled' : 'disabled',
                $project->hasToken() ? '' : ' (no token)'
            );
        }
    }

    private function abort(string $message): never
    {
        fwrite(STDERR, $message . "\n");

        exit(1);
    }
}
