# Configuration

Configuration lives in `/etc/icingaweb2/modules/hcloud/config.ini`.

```ini
[db]
resource = hcloud

[sync]
retention_metrics = 48
retention_actions = 30
per_page = 50

[project:prod]
name = "Production"
token = "your-read-token"
enabled = 1
s3_access_key = "your-s3-access-key"
s3_secret_key = "your-s3-secret-key"
s3_locations = "fsn1, nbg1, hel1"

[project:staging]
name = "Staging"
token = "another-read-token"
enabled = 1
```

## Projects

Every Hetzner project has its own API token, so every project gets its own
`[project:<key>]` section. The key is what you pass to `--project` on the command line and
what identifies the project in the database; `name` is what the interface shows.

Create the token in the Hetzner Console under *Project -> Security -> API Tokens* and give
it **Read** permission. Hetzner offers exactly two levels, Read and Read & Write; there are
no finer grained scopes, no per IP restriction and no expiry. This module only ever reads,
so Read is the correct choice.

List what is configured:

```
icingacli hcloud sync projects
```

## Sync settings

| Setting | Default | Meaning |
|---|---|---|
| `retention_metrics` | 48 | hours of metric samples to keep |
| `retention_actions` | 30 | days of action history to keep |
| `per_page` | 50 | page size for API requests, capped at 50 by Hetzner |
| `metric_step` | 300 | seconds between metric samples |
| `object_storage_usage` | 1 | walk the object listing to size each bucket |
| `object_storage_max_pages` | 20 | listing pages per bucket, 1000 objects each |

`metric_step` is sent to Hetzner explicitly. Without it the API derives the resolution from
the width of the requested window, so a frequently running sync would store samples seconds
apart while a first run stores them a quarter of an hour apart. That makes the stored series
inconsistent and lets the table grow without bound: at two second resolution a 48 hour
retention is roughly five million rows per project rather than the forty thousand a five
minute step produces.

## Object Storage

Object Storage is the one product here that Hetzner does not expose through its own API.
There is no endpoint for buckets, for usage or for traffic, and none for issuing credentials,
so the module talks to it over the S3 protocol with the S3 keys from *Project -> Security ->
S3 credentials*. Leave `s3_access_key` empty and the module skips Object Storage entirely.

Two consequences follow from that, and neither is a limitation of this module:

- **There is no quota to report.** Hetzner bills Object Storage by what is stored rather than
  against an allowance, and publishes no per bucket limit. The `objectstorage` check therefore
  takes absolute sizes rather than a percentage.
- **A bucket's size is what its object listing adds up to.** S3 offers no endpoint that states
  it, so the sync pages through the objects and sums them. `object_storage_max_pages` bounds
  that walk at 20000 objects per bucket by default; a bucket that hits the bound is stored with
  `Size Complete` set to No, and the check reports UNKNOWN rather than a figure it cannot stand
  behind. Set `object_storage_usage = 0` to list buckets without sizing them at all.

Buckets are bound to a location and each location answers only for its own, so `s3_locations`
lists the ones to ask. The default covers all three Hetzner offers today.

## Permissions

| Permission | Grants |
|---|---|
| `hcloud/read` | access to every view in the module |
| `hcloud/config` | access to the configuration tabs |

## Keeping tokens out of the open

Tokens are written through the configuration form and are never echoed back into it. The API
client strips the `Authorization` header from any exception it rethrows, so a token cannot
reach a log through an error path. `config.ini` should be readable only by the web server
user, which is how Icinga Web 2 creates it.
