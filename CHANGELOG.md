# Changelog

All notable changes to this module are documented here, in
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) style.

## [Unreleased]

## [0.2.0]

### Added

- Object Storage: bucket inventory per location, object count and size, a list and detail
  view, a dashboard count and an `objectstorage` check command. Hetzner publishes no API for
  Object Storage, so this reads the S3 protocol with the project's own S3 credentials, which
  are configured alongside the API token.
- Capacity bar with percentage on the storage box detail view.
- `hcloud_object_storage_bucket` table, with upgrade scripts for both engines.

### Changed

- Detail views format their values instead of printing the stored number: byte counts, sizes
  in gigabytes, durations, prices and percentages each render in their own unit, boolean
  columns render as Yes and No, and foreign ids resolve to the name they point at and link to
  its detail view where one exists.
- Formats are resolved per table rather than per column name, so `size` reads as gigabytes on
  a volume and as bytes on a storage box type. List views share the same rules.
- Cost lines use the gross price the API states rather than deriving it from the VAT rate.

### Fixed

- The cost dashboard reported 0 volumes while charging for their storage, because the volume
  count was never read from the database.

## [0.1.0]

### Added

- Initial module: read-only Hetzner Cloud inventory for Icinga Web 2.
- Multi project sync via `icingacli hcloud sync run`, one configured token per project.
- Coverage of locations, server types, load balancer types, storage box types, images, ISOs
  and pricing, plus servers, volumes, networks, load balancers, firewalls, floating and
  primary IPs, certificates, placement groups, SSH keys, DNS zones and record sets, storage
  boxes with subaccounts and snapshots, and the action log.
- Metric collection for servers and load balancers into a pruned rolling window, rendered as
  inline SVG charts with unit aware axes, grouped series and hover values.
- Cost dashboard with exact decimal arithmetic, including traffic overage.
- List views for every resource, a project dashboard and a sync history view.
- Director import sources for servers, load balancers, volumes, primary IPs and storage boxes.
- Check commands for sync freshness, servers, load balancer target health, certificate
  expiry, firewall drift, DNS zone delegation, storage box quota and failed actions.
- Health hook reporting sync age and failures per project.
- MySQL and PostgreSQL schema baselines kept in step by a parity test.
