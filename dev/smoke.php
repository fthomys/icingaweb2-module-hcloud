<?php

declare(strict_types=1);

use Icinga\Application\Cli;
use Icinga\Application\Icinga;
use Icinga\Module\Hcloud\Common\Database;
use Icinga\Module\Hcloud\Web\ResourceController;
use ipl\Orm\Model;
use ipl\Orm\Relations;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "smoke.php is a command line tool\n");
    exit(1);
}

$basedir = getenv('ICINGAWEB_BASEDIR') ?: '/usr/share/icingaweb2';
require_once rtrim($basedir, '/') . '/../php/Icinga/Application/Cli.php';

Cli::start($basedir);

$modules = Icinga::app()->getModuleManager();
$modules->loadEnabledModules();

if (! $modules->hasLoaded('hcloud')) {
    fwrite(STDERR, "hcloud is not enabled, nothing to smoke test\n");
    exit(1);
}

$moduleDir = dirname(__DIR__);
$failures = [];
$checked = 0;

/**
 * Build a list view query exactly the way ResourceController does, then execute it.
 *
 * This is what catches unresolvable relations and bad column names, neither of which any
 * static check sees: ipl\Orm only resolves them when the query is actually built.
 */
foreach ((array) glob($moduleDir . '/application/controllers/*Controller.php') as $file) {
    $source = (string) file_get_contents((string) $file);

    if (! preg_match('/extends\s+(ResourceController|DetailController)\b/', $source, $kind)) {
        continue;
    }

    if (! preg_match('/return\s+(\w+)::class;/', $source, $match)) {
        continue;
    }

    $name = basename((string) $file, '.php');
    $class = 'Icinga\\Module\\Hcloud\\Model\\' . $match[1];

    if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
        $failures[] = sprintf('%s: model %s does not exist', $name, $class);
        printf("  FAIL  %-28s model %s does not exist\n", $name, $class);
        continue;
    }

    $checked++;

    try {
        $query = $class::on(Database::get());

        $relations = new Relations();
        (new $class())->createRelations($relations);
        if ($relations->has('project')) {
            $query->with('project');
        }

        $query->limit(1);

        foreach ($query as $row) {
            unset($row);
            break;
        }

        printf("  ok    %-28s %s\n", $name, $class::on(Database::get())->getModel()->getTableName());
    } catch (Throwable $e) {
        $failures[] = sprintf('%s: %s', $name, $e->getMessage());
        printf("  FAIL  %-28s %s\n", $name, $e->getMessage());
    }
}

printf("\n%d views checked, %d failed\n", $checked, count($failures));

exit($failures === [] ? 0 : 1);
