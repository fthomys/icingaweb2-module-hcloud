# About

This module makes the contents of your Hetzner Cloud projects visible inside Icinga Web 2.

## How it is put together

```
icingacli hcloud sync run   ->   hcloud_* tables   ->   web views
                                                       Director import sources
                                                       icingacli hcloud check ...
```

A CLI sync is the only thing that talks to the Hetzner API. Everything else reads from the
local database. That is deliberate:

- a view that calls the API is slow, and breaks whenever Hetzner is unreachable,
- it cannot be filtered, sorted or paginated by the database,
- and it multiplies API requests by the number of people looking at the page.

## Read-only

The module never sends a POST, PUT, PATCH or DELETE to Hetzner. A token with **Read**
permission is sufficient, and nothing in the web interface can change your account. This is
a property of the code, not a setting.

## Scope

Covered: the Hetzner Cloud API at `api.hetzner.cloud/v1` and the Storage Box API at
`api.hetzner.com/v1`.

Not covered: the Robot API for dedicated servers, which is a separate product with separate
credentials and a different data model, and Object Storage, which has no Hetzner specific
REST API.

## A note on datacenters

Hetzner removed `datacenter` from servers and primary IPs on 2026-07-01, and the
`GET /datacenters` endpoint itself is deprecated with removal announced after 2026-10-01.
This module therefore models **locations** only. If you find older examples that show a
`datacenter` field, they predate that change.
