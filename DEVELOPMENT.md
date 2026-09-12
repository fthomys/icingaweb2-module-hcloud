# Development

This file records only where this module *deviates* from the shared Icinga Web 2 module
conventions. Everything not mentioned here follows the common rules.

## Deviations

### No comments in code

Code carries no explanatory comments. Reasoning lives in `CLAUDE.md`, in this file and in
`doc/`. Docblocks survive only where PHPStan level 8 needs an array value type, and in the
CLI commands where the docblock *is* the `--help` output Icinga renders.

### No em dash or en dash

Plain hyphens only, everywhere in the tree.

### A rolling metric window in the database

The shared rules say a module should not run its own time series store. This one keeps a
short, pruned window of metric samples in `hcloud_metric_sample` anyway, so that the web
layer can draw charts while still reading only from the database. Retention defaults to 48
hours and is configurable. Long term history still belongs in Icinga perfdata, which the
check commands provide.

### A module owned migrator for the CLI

`icingacli hcloud migrate` uses `Db\Migrator` rather than `DbMigrationHook::run()`. The base
hook's `getMigrations()` reaches into `Icinga\Web\Session` through `loadLastState()`, which
is a web session and is not available in a CLI process. The web path (Configuration ->
Migrations) still goes through the hook as normal; both agree because the upgrade scripts
record their own version as their final statement.

### PHPStan

Level 8, no baseline. There is exactly one `ignoreErrors` entry, in `phpstan.neon`, for
`ipl\Sql\Connection::exec()`: the class proxies unknown methods to PDO via `__call` and
declares no `@method` tags, so PHPStan cannot see it. Icinga Web's own `DbMigrationStep`
calls it the same way. The defect is the missing annotation upstream.

## Why the manual sync is not backgrounded

An earlier version spawned `icingacli hcloud sync run` with `setsid` and returned at once.
That gave the user nothing to look at: the page came back immediately while the work was
still running, so the button appeared to do nothing. Running inside the request means
Icinga's normal form handling shows its progress indicator for the real duration and the
result can be reported straight away. Measured on a real project: 1.7 seconds against a
30 second `max_execution_time`.

## The metric charts

Three pieces, deliberately split so no unit rule exists twice:

- `Metric\SeriesInfo` decodes a Hetzner series name into a label, a unit and a chart group.
  The names carry both the unit and the disk or interface index and are documented nowhere,
  so this is the one place that knows them.
- `Metric\Unit` formats a value. Throughput scales binary (KiB, MiB), rates scale decimal
  (k, M), percent is the only unit with a fixed 0 to 100 axis.
- `Web\Widget\MetricChart` draws inline SVG: grid, axis ticks, time axis, one line per
  series, legend with now/avg/max. Series of one group share a chart, so read sits next to
  write, but IOPS and throughput never share an axis.

### Hover values

`public/js/module.js` registers `Icinga.availableModules.hcloud` and binds mousemove on
`.hcloud-metric-plot`. Under strict CSP there can be no inline script, so this has to be a
served file; Icinga picks it up through `Module::hasJs()` and bundles it.

**The browser never formats a value.** The widget emits the already formatted strings in
`data-hcloud-series`, together with the plot geometry in `data-hcloud-plot`, so the unit
rules live in `Metric\Unit` alone and cannot drift between PHP and JavaScript. The chart is
evenly sampled, so the pointer maps to an index by ratio rather than by searching.

## Tooling

Nothing in this module may come from a `vendor/` directory. `phpunit`, `phpcs` and `phpstan`
come from the distro or CI.

Both dev bootstraps locate Icinga Web through the environment variables Icinga Web itself
honours, so an installation in an unusual place needs no edits:

```
export ICINGAWEB_LIBDIR=/usr/share/icinga-php
export ICINGAWEB_BASEDIR=/usr/share/icingaweb2
export ICINGAWEB_MODULEDIR=/usr/share/icingaweb2/modules   # only needed for the Director hooks
```

`ICINGAWEB_MODULEDIR` matters because `ProvidedHook/Director/*` extends Director's
`ImportSourceHook`. Without Director on the path PHPStan cannot resolve that base class.

### The global translation helpers

`t()`, `mt()`, `tp()` and `mtp()` are plain global functions rather than autoloadable
classes. This module brings them in by requiring `Icinga/Application/functions.php` from
`dev/bootstrap-common.php`, instead of pinning an absolute path in `phpstan.neon` through
`scanFiles`. The bootstrap already knows where Icinga Web is, so the same config works on
any installation layout.

### PHPStan result cache

If PHPStan reports that methods on `ipl\Html` classes are undefined after you have edited
files, that is its incremental result cache going stale against the library bundles, not a
real error. `phpstan clear-result-cache` and re-run; a cold run is authoritative.

## The QA gate

```
phpunit
phpcs --standard=phpcs.xml
phpstan analyse
```

### dev/smoke.php needs a real installation

`phpunit` never touches a database, so it cannot see an unresolvable ipl\Orm relation or a
column that does not exist: those only fail when a query is actually built and run. That gap
is what `dev/smoke.php` closes. It boots Icinga Web through `Cli::start()`, loads the enabled
modules so the module autoloader exists, then builds and executes the list query of every
controller the same way `ResourceController` does.

```
sudo -u www-data php /usr/share/icingaweb2/modules/hcloud/dev/smoke.php
```

Run it on a host that has the module installed and a database configured. It exits non zero
on the first broken view.

Tests run standalone: no database, no live API, no mock server. The fixtures under
`dev/mock/` are plain JSON files.

## Fixtures

`dev/mock/*.json` is generated from Hetzner's published OpenAPI specs, not hand written:

```
php dev/generate-fixtures.php cloud.spec.json hetzner.spec.json
```

with the specs downloaded from `https://docs.hetzner.cloud/cloud.spec.json` and
`https://docs.hetzner.cloud/hetzner.spec.json`. The generator walks each response schema and
uses the per property `example` values, so the fixtures track the published contract instead
of somebody's memory of it. Regenerate them when the API changes.

Raw captures from a real account must never be committed; `dev/mock/raw/` is ignored for
exactly that reason.

## Tests worth knowing about

- `SchemaParityTest` compares the MySQL and PostgreSQL baselines table by table, column by
  column, key by key and index by index. It is the only thing that catches a column added to
  one baseline and forgotten in the other.
- `MapperSchemaTest` asserts that every column a mapper writes exists in the schema, and that
  every declared child table is actually produced. A typo there would otherwise only surface
  as an SQL error during a real sync.
- `ModelSchemaTest` asserts the reverse for the ORM models: exactly the schema's columns, the
  real primary key, and a label for every column.
