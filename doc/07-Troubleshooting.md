# Troubleshooting

## The interface shows nothing

Check that a sync has actually run:

```
icingacli hcloud sync projects
icingacli hcloud sync run --dry-run
```

`--dry-run` fetches everything and writes nothing, so it proves the token and connectivity
without touching the database. The *Sync* view and `icingacli hcloud check sync` show the
history of real runs.

## The data is stale

`icingacli hcloud check sync` is the check that catches this. A stalled cron is the usual
cause; the *Sync* view shows when the last successful run finished and what failed.

## 401 unauthorized

The token is wrong, or it belongs to a different project. Tokens are bound to one project.

## 401 with code `token_readonly`

Something attempted a write. This module never writes, so if you see this, the token is
being used by something else as well.

## 429 rate_limit_exceeded

The budget is 3600 requests per hour per project. The client waits for the reset and retries
on its own, so an occasional 429 in the logs is not a problem. If it happens constantly,
either the sync interval is too short for the number of projects, or something else is using
the same token.

## A status shows as a raw value instead of a friendly label

That is intentional. When Hetzner returns a value the module does not recognise, it renders
the raw value rather than hiding it behind a default. It means the API gained a value the
module has not been taught yet; please report it.

A real example: `primary_ip.assignee_type` began returning `unassigned` on 2026-08-01 even
though the published schema still lists only `server`.

## Migrations do not appear

The migration hook needs Icinga Web 2 >= 2.12. On older versions, apply
`schema/*-upgrades/*.sql` by hand, in version order.

## Translations are half English

gettext caches `.mo` files per worker process, so restart the web server after deploying
translation changes, and make sure the target locale is generated on the host.
