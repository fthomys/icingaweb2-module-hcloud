# Director import sources

The module provides five import sources for Icinga Director:

| Import source | Table |
|---|---|
| Hetzner Cloud Servers | `hcloud_server` |
| Hetzner Cloud Load Balancers | `hcloud_load_balancer` |
| Hetzner Cloud Volumes | `hcloud_volume` |
| Hetzner Cloud Primary IPs | `hcloud_primary_ip` |
| Hetzner Cloud Storage Boxes | `hcloud_storage_box` |

They read the synced database, so run `icingacli hcloud sync run` at least once before
creating an import source.

## Setting one up

1. *Director -> Automation -> Import Sources -> Add*.
2. Pick the source type, for example *Hetzner Cloud Servers*.
3. Key column: `name`.
4. Trigger a run and check the preview.

## Available columns

Every source exposes its resource columns plus two extras:

- `project` - the configured project name, so a multi project import can be filtered or
  turned into a host group,
- `labels_flat` - the Hetzner labels flattened to `key=value,key=value`, which is convenient
  for a property modifier that splits into a list.

## A worked example

To import servers as hosts:

- `name` maps to the host object name,
- `labels_flat` through a *split* property modifier becomes `host.groups`,
- `project` becomes a custom variable, or a host group of its own,
- `status` becomes a custom variable you can assign service sets from.

Because the import reads the database rather than the API, a Director import run costs
nothing against your Hetzner rate limit and works while Hetzner is unreachable.
