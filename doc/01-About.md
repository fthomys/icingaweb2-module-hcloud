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

Covered: the Hetzner Cloud API at `api.hetzner.cloud/v1`, the Storage Box API at
`api.hetzner.com/v1`, and Object Storage over the S3 protocol.

Object Storage is the odd one out. Hetzner publishes no API for it, not for buckets, not for
usage and not for issuing credentials, so the module reads it over S3 with the project's own
S3 keys. That also means there is no quota to report and a bucket's size is what its object
listing adds up to. See [03-Configuration](03-Configuration.md).

Not covered: the Robot API for dedicated servers, which is a separate product with separate
credentials and a different data model.

## A note on datacenters

Hetzner removed `datacenter` from servers and primary IPs on 2026-07-01, and the
`GET /datacenters` endpoint itself is deprecated with removal announced after 2026-10-01.
This module therefore models **locations** only. If you find older examples that show a
`datacenter` field, they predate that change.

## AI disclosure

This module was written with AI assistance (Claude). What that means in practice, and which
parts are verified against what, is described under *AI disclosure* in the README.

The short version: the Hetzner API surface was read from the OpenAPI specifications rather
than recalled, the facts that were settled against a live account are listed in `CLAUDE.md`,
and the parts that could not be verified against Hetzner say so in `ROADMAP.md`.
