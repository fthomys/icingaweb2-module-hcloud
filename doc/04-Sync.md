# Sync

```
icingacli hcloud sync run [--project=<key>] [--resource=<name>] [--no-metrics] [--dry-run]
```

The command is one shot: it syncs once and exits. Scheduling is cron's job, or the
syncdaemon module's. A module that schedules itself cannot be driven by either.

```
# /etc/cron.d/hcloud
MAILTO=""
*/5 * * * * www-data /usr/bin/icingacli hcloud sync run >/dev/null 2>&1
```

Run it as the same user the web server runs as, so the sync and the web interface read the
same configuration. The charts depend on this: a metric series is only as continuous as the
sync that fills it, and a gap in the data is a gap in the chart.

## Syncing from the web interface

The *Sync* view has a *Sync now* button, guarded by the `hcloud/sync` permission. It runs the
sync **inside the request**, so Icinga shows its own progress indicator until the run is
finished and reports the outcome as a notification.

That only works because a sync is short: a project with a handful of servers takes under two
seconds, and metrics cost one API request per server and per load balancer. A project large
enough to approach the web server's `max_execution_time` belongs on cron, which is what the
CLI command is for; the *Skip metrics* checkbox removes the part that grows with the number
of servers.

## What happens during a run

Each resource is handled in two phases. The **fetch** phase talks to the API and touches no
database; the **write** phase runs in a transaction and makes no API calls. If a resource
fails, the transaction rolls back, the failure is recorded against that resource, and the
run continues with the next one.

Order: catalog data first (locations, server types, load balancer types, storage box types,
images, ISOs), then the resources that reference it, then the per parent sub resources
(zone record sets, storage box subaccounts and snapshots), then actions, then metrics.

## How removals are handled

Hetzner returns each collection in full, so a run replaces the whole collection for a
project rather than merging into it. This is what lets a destroyed server actually disappear
from the interface. Merging would leave ghosts that no later sync ever cleans up.

## Actions

Hetzner removed the unfiltered `GET /actions` listing on 2025-01-30; the endpoint now
requires an `id` parameter and returns 410 without one. The activity feed is therefore built
by polling the per resource type endpoints (`/servers/actions`, `/volumes/actions` and so
on) sorted newest first, stopping once entries fall outside `retention_actions`.

Action status is `running`, `success` or `error`. There is no `pending` status, despite what
older documentation says.

## Metrics

For each server and load balancer the sync requests the metric types the endpoint supports,
starting from the newest sample already stored and falling back to a bounded initial window.
Samples older than `retention_metrics` hours are pruned at the end of each run.

The API models the metric series as an open map and does not enumerate the series names, so
the module stores whatever names come back rather than assuming a fixed set. There are no
metrics endpoints for volumes, networks, firewalls or storage boxes.

Keeping a rolling window in the database is a deliberate exception to the rule that a module
should not run its own time series store. It buys graphs that still work when Hetzner is
unreachable, and it keeps the web layer reading only from the database. Long term history
belongs in Icinga's own perfdata, which the check commands feed.

## Rate limits

Hetzner allows 3600 requests per hour per project, refilling gradually, and reports the
budget in the `RateLimit-Limit`, `RateLimit-Remaining` and `RateLimit-Reset` headers. There
is no `Retry-After`. The client tracks the remaining budget, waits until the reset timestamp
when it runs low or receives a 429, and retries. A full sweep of one project is on the order
of tens of requests, so this is a safety net rather than a normal occurrence.
