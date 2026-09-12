# Roadmap

Planned work on the module itself. Items are grouped by the release that should carry them.

## 0.2.0, current

The full read path is in place.

- Complete Cloud API coverage: servers, volumes, networks, load balancers, firewalls,
  floating and primary IPs, certificates, placement groups, SSH keys, images, ISOs, DNS zones
  with record sets, plus storage boxes with subaccounts and snapshots from the second API host
- Multi project sync, one token per project, read only throughout
- MySQL and PostgreSQL schema, kept identical by a parity test
- List and detail view for every resource, dashboard, cost breakdown, sync history
- Metric charts with time bucketing, min/max envelope, unit aware axes and hover values
- Ten check commands, five Director import sources, Health hook
- Object Storage over S3, since Hetzner publishes no API for it
- Value formatting and resolved foreign keys throughout the detail views
- Complete German translation

## 0.3.0, filling the gaps the UI still has

### Sample continuity
A chart is only as continuous as the sync behind it. The module already pins the resolution
with `sync.metric_step`, but nothing tells an operator that their sync interval and that step
disagree, which is what produces a chart full of holes. A warning on the sync view, and a
check that compares the two, would catch it before it looks like a rendering bug.

### Search bar suggestions
`Web\ResourceController::createSearchBarSuggestions()` returns an empty list, so the search
bar accepts a filter but proposes nothing. It needs to answer with column names and, for
columns worth it, the distinct values present in the database.

### Dashlets
The module provides no dashlets, so none of its views can be added to an Icinga dashboard.
Candidates: resource counts per project, servers not running, certificates near expiry,
sync freshness.

### Restrictions
No `provideRestriction`, so every user who holds `hcloud/read` sees every project. A
restriction on the project column would let one Icinga serve several tenants.

### Continuous integration
No workflow exists. CI should run the same gate the module is developed against: phpunit,
phpcs, phpstan, plus a schema apply against both MySQL and PostgreSQL to prove the baselines
really are loadable and not just textually consistent.

### Foreign keys in list views
Detail views resolve a stored id to the name it points at. List views still print the id,
because doing the same there means joining the target table into a paginated query rather
than one extra lookup per row.

### Object Storage cost
`/pricing` carries no Object Storage entry, so the cost view leaves buckets out rather than
hardcoding a figure that would silently go stale. A configured price per terabyte would let
the existing cost path include them.

## 0.4.0, depth

### Load balancer metric series
The nine server series are confirmed against live data. The load balancer series names are
still inferred from the request parameters, because the specification enumerates none of
them. They need confirming against an account that has a load balancer, and
`Metric\SeriesInfo` needs the names it then learns.

### Retention per resource kind
One retention applies to every series. Splitting it would let a busy server keep a short high
resolution window while catalog style data is kept longer.

### Cost history
Costs are computed from the current inventory each time the view is opened, so there is no
way to see that a bill grew. A small monthly snapshot table would answer that without
becoming a time series store.

### Traffic projection
`outgoing_traffic` against `included_traffic` is known per resource, but only as a current
figure. Projecting it to the end of the billing period is what makes an overage actionable
before it happens.

### More locales
English is the source language and needs no catalog. German is complete. Any further locale
is a matter of running the extraction and translating.

## Not planned

These are decisions, not omissions.

- **Write access to Hetzner.** No code path mutates, which is precisely why a Read token
  suffices. Adding one would change the permission model, the documentation and the safety
  argument all at once.
- **A webhook receiver.** Icinga usually runs where nothing outside can reach it, so a
  receiver means firewall, TLS and secret handling for little gain over polling.
- **A module owned time series backend.** The rolling metric window is a bounded exception
  that exists so charts survive an API outage. Long term history belongs in Icinga perfdata,
  which the check commands already feed.
- **The Robot API for dedicated servers.** Separate product, separate credentials, separate
  data model. It would be a second client, not an extension of this one.
- **Object Storage usage without the object walk.** Hetzner states plainly that no API exists
  for the facts its console shows, so a bucket's size is what its object listing adds up to.
  Guessing at an undocumented endpoint would be worse than the bounded walk that is there.
