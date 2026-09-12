# Monitoring

## Check commands

Every check reads the local database only. None of them contacts Hetzner, so a check never
hangs on a slow API and never consumes rate limit budget.

```
icingacli hcloud check sync [--warning=<minutes>] [--critical=<minutes>]
icingacli hcloud check server
icingacli hcloud check volume [--warn-detached]
icingacli hcloud check loadbalancer
icingacli hcloud check certificate [--warning=<days>] [--critical=<days>]
icingacli hcloud check firewall
icingacli hcloud check zone
icingacli hcloud check storagebox [--warning=<percent>] [--critical=<percent>]
icingacli hcloud check objectstorage [--warning=<gb>] [--critical=<gb>]
icingacli hcloud check actions
```

| Check | Warning | Critical |
|---|---|---|
| `sync` | last successful sync older than 30 minutes | older than 120 minutes, or never |
| `server` | any server not `running` | - |
| `volume` | any volume not `available`, or detached with `--warn-detached` | - |
| `loadbalancer` | any target in `unknown` | any target `unhealthy` |
| `certificate` | expires within 28 days | expires within 7 days, or managed renewal failed |
| `firewall` | any server firewall binding still `pending` | - |
| `zone` | delegation `invalid` or `lame` | zone status `error` |
| `storagebox` | usage over 80 percent | usage over 90 percent |
| `objectstorage` | total size over `--warning` GiB | over `--critical` GiB |
| `actions` | any failed action in the retention window | - |

`objectstorage` takes absolute sizes rather than percentages because Hetzner publishes no
quota for Object Storage. It reports UNKNOWN when a bucket's size is incomplete, which happens
when the object listing hit `sync.object_storage_max_pages` before reaching the end.

**Start with `sync`.** Every other check reports on data that is only as fresh as the last
sync, so a stalled cron would otherwise show up as everything being fine.

All checks emit perfdata, which is where long term history comes from. The module shows the
current snapshot; Icinga keeps the series.

## Health

The module implements Icinga Web's Health hook, so *Configuration -> Health* shows the last
sync age and status per project plus the number of failed actions. It is database only and
deliberately cheap, because health is collected synchronously alongside every other module's.
