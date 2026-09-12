# Hetzner Cloud for Icinga Web 2

Browse every resource in your Hetzner Cloud projects from inside Icinga Web 2.

A CLI sync pulls the full Hetzner API into a local database; the web views, the Director
import sources and the check commands all read from that database. The module is
**read-only**: it never issues a mutating request, so a Hetzner *Read* token is enough.

## Features

- **Full resource coverage** - servers, volumes, networks, load balancers, firewalls,
  floating and primary IPs, certificates, placement groups, SSH keys, images, ISOs,
  DNS zones and record sets, and storage boxes with their subaccounts and snapshots.
- **Object Storage** - buckets, their object count and size, read over S3 because Hetzner
  offers no API for it.
- **Multi-project** - each Hetzner project has its own token and is synced independently.
- **Metrics** - CPU, disk and network for servers, connections and bandwidth for load
  balancers, kept in a pruned rolling window and drawn as inline SVG charts.
- **Cost dashboard** - per project, category and location, computed from the synced
  `/pricing` data with exact decimal arithmetic, including traffic overage.
- **Director import sources** - import servers, load balancers, volumes, primary IPs and
  storage boxes as Icinga objects, with Hetzner labels exposed as custom variables.
- **Check commands** - `icingacli hcloud check ...` returns plugin state plus perfdata for
  servers, load balancer target health, certificate expiry, firewall drift, DNS delegation,
  storage box quota, Object Storage size, failed actions and sync freshness.

## Requirements

- Icinga Web 2 >= 2.12
- PHP >= 8.2
- MariaDB >= 10.3 or PostgreSQL >= 12
- A Hetzner Cloud API token with **Read** permission, per project
- Optional: S3 credentials per project, for Object Storage

The module ships no `vendor/` directory. Everything comes from what an Icinga Web 2
installation already provides.

## Quick start

```
icingacli module enable hcloud
mysql icingaweb2 < schema/mysql.sql
icingacli hcloud sync projects
icingacli hcloud sync run
```

Then add a cron entry, for example every five minutes:

```
*/5 * * * * icingaweb2 icingacli hcloud sync run
```

## Documentation

| Chapter | Contents |
|---|---|
| [01-About](doc/01-About.md) | what the module does and how it is put together |
| [02-Installation](doc/02-Installation.md) | install, database, schema, upgrades |
| [03-Configuration](doc/03-Configuration.md) | projects, tokens, sync settings, permissions |
| [04-Sync](doc/04-Sync.md) | the sync process, scheduling, what is fetched |
| [05-Monitoring](doc/05-Monitoring.md) | check commands and the Health hook |
| [06-Director](doc/06-Director.md) | the import sources |
| [07-Troubleshooting](doc/07-Troubleshooting.md) | rate limits, stale data, common errors |

See [ROADMAP.md](ROADMAP.md) for what is done and what is next.

## License

GPL-3.0-or-later. See [LICENSE](LICENSE).
