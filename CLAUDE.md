# hcloud

Hetzner Cloud inventory and monitoring for Icinga Web 2. This file records what is true of
*this* module: its goal, the decisions behind its shape, and what is still open. Rules that
would read the same in a sibling module live in the shared module skill; deviations live in
`DEVELOPMENT.md`.

## Goal

Give an Icinga operator the complete contents of their Hetzner Cloud projects without
leaving Icinga Web, and turn the parts that can break into check results.

## Decisions

**Read-only, by construction.** The module has no code path that issues a mutating HTTP
request. This is why a Hetzner *Read* token is sufficient and why no view can damage the
account. Adding a write feature later would mean revisiting the token documentation, the
permission model and this claim in `doc/01-About.md`.

**Sync writes, web reads.** Only `icingacli hcloud sync run` talks to Hetzner. Controllers,
hooks and check commands read the database. This keeps views fast, filterable and available
while Hetzner is not.

**Project scoped keys.** Hetzner IDs are unique within a project, not across an account, so
every table is keyed on `(project_id, id)`. `hcloud_project` is the module's own surrogate
for a configured `[project:*]` section.

**Full replace per resource, not upsert.** `Db\Store::replace()` deletes a project's rows for
a table and reinserts them inside one transaction. Hetzner returns every collection in full,
so this is correct, it is what makes deletions propagate, and it avoids engine specific
upsert SQL entirely. Account sized data makes the churn irrelevant.

**Mappers are pure functions.** `Sync\Mapper\*` turns an API array into a row array and
touches nothing else. That is what makes the whole translation layer testable against JSON
fixtures with no database and no network.

**Exact decimal money.** `Pricing\Money` stores amounts as integers scaled by 10^10, because
Hetzner returns prices as decimal *strings* with ten decimal places and this host has no
bcmath. Never convert a price to float.

**Unrecognised values stay visible.** Every enum has a `tryFromValue()` that returns null for
an unknown value, and the widgets then render the raw string. A `default` arm that quietly
normalised would hide exactly the case worth seeing: `primary_ip.assignee_type` started
returning `unassigned` on 2026-08-01 while the published schema still said `server`.

## API facts that are easy to get wrong

Each of these was checked against `cloud.spec.json`, not recalled:

- `GET /actions` requires an `id` parameter; the unfiltered listing was removed 2025-01-30.
  The activity feed polls the eleven per type `/{resource}/actions` endpoints instead.
- `action.status` is `running|success|error`. There is no `pending`.
- `server.datacenter` and `primary_ip.datacenter` were removed 2026-07-01; `location` is the
  FK. `GET /datacenters` is deprecated with removal after 2026-10-01, so no datacenter table
  is built.
- The flat `deprecated` field on images, server types and load balancer types is removed
  2026-11-02. Map the `deprecation{announced, unavailable_after}` object.
- `server.public_net.ipv4.dns_ptr` is a string but `ipv6.dns_ptr` is a list. Both are
  normalised into `hcloud_server_public_ip_dns_ptr`.
- A load balancer `label_selector` target nests its resolved server targets one level down in
  `targets[].targets[]`. Flattening has to recurse or label driven setups look empty.
- Network subnets, network routes and firewall rules have no IDs. They key on
  `(network_id, ip_range)`, `(network_id, destination)` and rule ordinal respectively.
- Zone record sets are the only resource with a **string** primary key (`<name>/<type>`) and
  the only one with `protection.change` instead of `protection.delete`.
- Storage boxes live on a second host, `api.hetzner.com/v1`, with otherwise identical
  conventions.
- `meta.pagination.total_entries` is nullable. Loop on `next_page`, never count pages.
- Prices are decimal strings, and multi value query parameters are repeated, not comma joined.

## Settled against a live account

**One token covers both hosts.** Neither spec states whether a project token authenticates
against `api.hetzner.cloud` and `api.hetzner.com` alike. Verified on 2026-09-12 against a
real project: `sync run --dry-run --resource=storage_boxes` succeeded with the same token
that serves the Cloud API. `Api\ProjectRegistry` keeps the indirection anyway, so a second
token key stays a one line change if Hetzner ever splits them.

**Metric series names, confirmed 2026-09-12.** The spec models `time_series` as an open map
and enumerates nothing. A real sync returns exactly nine server series: `cpu`,
`disk.0.bandwidth.read`, `disk.0.bandwidth.write`, `disk.0.iops.read`, `disk.0.iops.write`,
`network.0.bandwidth.in`, `network.0.bandwidth.out`, `network.0.pps.in`,
`network.0.pps.out`. Load balancer series remain unconfirmed, because the specification
enumerates none of them and confirming needs an account that has one. `series_name` stays a
free string regardless.

**Costs verified end to end, 2026-09-12.** `Sync\PricingMapper` writes the singleton
`/pricing` response plus storage box type prices, which ride along on the type objects from
the second base URI. Checked against a live project: every server resolved to a price, the
per category totals matched a hand calculation, and gross came out as net times the reported
VAT rate. `CostReport` carries `hasPricingData`, so the view states that pricing is missing
rather than presenting a table of 0.00.

**All nine check commands exercised against real data, 2026-09-12.** Two defects only showed
up there: `volume` was in the approved plan but never written, and its detached count used
`['server_id IS NULL' => null]`, which makes ipl\Sql bind a value for a condition that has no
placeholder.

## Open

Tracked in [ROADMAP.md](ROADMAP.md).
