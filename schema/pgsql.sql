CREATE TABLE hcloud_schema (
  id SERIAL NOT NULL,
  version VARCHAR(64) NOT NULL,
  timestamp BIGINT NOT NULL,
  success VARCHAR(1) DEFAULT NULL,
  reason TEXT DEFAULT NULL,
  PRIMARY KEY (id),
  CONSTRAINT idx_hcloud_schema_version UNIQUE (version)
);

CREATE TABLE hcloud_project (
  id SERIAL NOT NULL,
  config_key VARCHAR(128) NOT NULL,
  name VARCHAR(255) NOT NULL,
  enabled SMALLINT NOT NULL DEFAULT 1,
  created TIMESTAMP NOT NULL,
  PRIMARY KEY (id),
  CONSTRAINT idx_hcloud_project_config_key UNIQUE (config_key)
);

CREATE TABLE hcloud_sync_run (
  id BIGSERIAL NOT NULL,
  project_id INT NOT NULL,
  started TIMESTAMP NOT NULL,
  ended TIMESTAMP DEFAULT NULL,
  status VARCHAR(32) NOT NULL,
  resources_synced INT NOT NULL DEFAULT 0,
  rows_written INT NOT NULL DEFAULT 0,
  rows_deleted INT NOT NULL DEFAULT 0,
  api_requests INT NOT NULL DEFAULT 0,
  error TEXT DEFAULT NULL,
  PRIMARY KEY (id),
  CONSTRAINT fk_hcloud_sync_run_project FOREIGN KEY (project_id) REFERENCES hcloud_project (id) ON DELETE CASCADE
);

CREATE TABLE hcloud_sync_run_resource (
  id BIGSERIAL NOT NULL,
  sync_run_id BIGINT NOT NULL,
  resource VARCHAR(64) NOT NULL,
  fetched INT NOT NULL DEFAULT 0,
  written INT NOT NULL DEFAULT 0,
  deleted INT NOT NULL DEFAULT 0,
  duration_ms INT NOT NULL DEFAULT 0,
  error TEXT DEFAULT NULL,
  PRIMARY KEY (id),
  CONSTRAINT fk_hcloud_sync_run_resource_run FOREIGN KEY (sync_run_id) REFERENCES hcloud_sync_run (id) ON DELETE CASCADE
);

CREATE TABLE hcloud_location (
  project_id INT NOT NULL,
  id BIGINT NOT NULL,
  name VARCHAR(128) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  country VARCHAR(8) DEFAULT NULL,
  city VARCHAR(128) DEFAULT NULL,
  latitude DECIMAL(10, 6) DEFAULT NULL,
  longitude DECIMAL(10, 6) DEFAULT NULL,
  network_zone VARCHAR(64) DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  CONSTRAINT fk_hcloud_location_project FOREIGN KEY (project_id) REFERENCES hcloud_project (id) ON DELETE CASCADE
);

CREATE TABLE hcloud_server_type (
  project_id INT NOT NULL,
  id BIGINT NOT NULL,
  name VARCHAR(128) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  cores INT DEFAULT NULL,
  memory DECIMAL(12, 2) DEFAULT NULL,
  disk DECIMAL(12, 2) DEFAULT NULL,
  storage_type VARCHAR(32) DEFAULT NULL,
  cpu_type VARCHAR(32) DEFAULT NULL,
  category VARCHAR(64) DEFAULT NULL,
  architecture VARCHAR(16) DEFAULT NULL,
  deprecation_announced TIMESTAMP DEFAULT NULL,
  deprecation_unavailable_after TIMESTAMP DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  CONSTRAINT fk_hcloud_server_type_project FOREIGN KEY (project_id) REFERENCES hcloud_project (id) ON DELETE CASCADE
);

CREATE TABLE hcloud_server_type_location (
  project_id INT NOT NULL,
  server_type_id BIGINT NOT NULL,
  location_id BIGINT NOT NULL,
  location_name VARCHAR(128) NOT NULL,
  recommended SMALLINT NOT NULL DEFAULT 0,
  available SMALLINT NOT NULL DEFAULT 0,
  deprecation_announced TIMESTAMP DEFAULT NULL,
  deprecation_unavailable_after TIMESTAMP DEFAULT NULL,
  PRIMARY KEY (project_id, server_type_id, location_id),
  CONSTRAINT fk_hcloud_server_type_location_type FOREIGN KEY (project_id, server_type_id) REFERENCES hcloud_server_type (project_id, id) ON DELETE CASCADE
);

CREATE TABLE hcloud_load_balancer_type (
  project_id INT NOT NULL,
  id BIGINT NOT NULL,
  name VARCHAR(128) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  max_connections INT DEFAULT NULL,
  max_services INT DEFAULT NULL,
  max_targets INT DEFAULT NULL,
  max_assigned_certificates INT DEFAULT NULL,
  deprecation_announced TIMESTAMP DEFAULT NULL,
  deprecation_unavailable_after TIMESTAMP DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  CONSTRAINT fk_hcloud_load_balancer_type_project FOREIGN KEY (project_id) REFERENCES hcloud_project (id) ON DELETE CASCADE
);

CREATE TABLE hcloud_storage_box_type (
  project_id INT NOT NULL,
  id BIGINT NOT NULL,
  name VARCHAR(128) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  size BIGINT DEFAULT NULL,
  snapshot_limit INT DEFAULT NULL,
  automatic_snapshot_limit INT DEFAULT NULL,
  subaccounts_limit INT DEFAULT NULL,
  deprecation_announced TIMESTAMP DEFAULT NULL,
  deprecation_unavailable_after TIMESTAMP DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  CONSTRAINT fk_hcloud_storage_box_type_project FOREIGN KEY (project_id) REFERENCES hcloud_project (id) ON DELETE CASCADE
);

CREATE TABLE hcloud_image (
  project_id INT NOT NULL,
  id BIGINT NOT NULL,
  name VARCHAR(255) DEFAULT NULL,
  description VARCHAR(255) DEFAULT NULL,
  type VARCHAR(32) DEFAULT NULL,
  status VARCHAR(32) DEFAULT NULL,
  created TIMESTAMP DEFAULT NULL,
  image_size DECIMAL(12, 3) DEFAULT NULL,
  disk_size DECIMAL(12, 3) DEFAULT NULL,
  architecture VARCHAR(16) DEFAULT NULL,
  os_flavor VARCHAR(32) DEFAULT NULL,
  os_version VARCHAR(64) DEFAULT NULL,
  rapid_deploy SMALLINT NOT NULL DEFAULT 0,
  bound_to BIGINT DEFAULT NULL,
  created_from_id BIGINT DEFAULT NULL,
  created_from_name VARCHAR(255) DEFAULT NULL,
  deleted TIMESTAMP DEFAULT NULL,
  protection_delete SMALLINT NOT NULL DEFAULT 0,
  deprecation_announced TIMESTAMP DEFAULT NULL,
  deprecation_unavailable_after TIMESTAMP DEFAULT NULL,
  labels JSONB DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  CONSTRAINT fk_hcloud_image_project FOREIGN KEY (project_id) REFERENCES hcloud_project (id) ON DELETE CASCADE
);

CREATE TABLE hcloud_iso (
  project_id INT NOT NULL,
  id BIGINT NOT NULL,
  name VARCHAR(255) DEFAULT NULL,
  description VARCHAR(255) DEFAULT NULL,
  type VARCHAR(32) DEFAULT NULL,
  architecture VARCHAR(16) DEFAULT NULL,
  deprecation_announced TIMESTAMP DEFAULT NULL,
  deprecation_unavailable_after TIMESTAMP DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  CONSTRAINT fk_hcloud_iso_project FOREIGN KEY (project_id) REFERENCES hcloud_project (id) ON DELETE CASCADE
);

CREATE TABLE hcloud_server (
  project_id INT NOT NULL,
  id BIGINT NOT NULL,
  name VARCHAR(255) NOT NULL,
  status VARCHAR(32) NOT NULL,
  created TIMESTAMP DEFAULT NULL,
  locked SMALLINT NOT NULL DEFAULT 0,
  rescue_enabled SMALLINT NOT NULL DEFAULT 0,
  backup_window VARCHAR(32) DEFAULT NULL,
  outgoing_traffic BIGINT DEFAULT NULL,
  ingoing_traffic BIGINT DEFAULT NULL,
  included_traffic BIGINT DEFAULT NULL,
  primary_disk_size DECIMAL(12, 3) DEFAULT NULL,
  server_type_id BIGINT DEFAULT NULL,
  location_id BIGINT DEFAULT NULL,
  image_id BIGINT DEFAULT NULL,
  iso_id BIGINT DEFAULT NULL,
  placement_group_id BIGINT DEFAULT NULL,
  protection_delete SMALLINT NOT NULL DEFAULT 0,
  protection_rebuild SMALLINT NOT NULL DEFAULT 0,
  labels JSONB DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  CONSTRAINT fk_hcloud_server_project FOREIGN KEY (project_id) REFERENCES hcloud_project (id) ON DELETE CASCADE
);

CREATE TABLE hcloud_server_public_ip (
  project_id INT NOT NULL,
  server_id BIGINT NOT NULL,
  family VARCHAR(8) NOT NULL,
  ip_id BIGINT DEFAULT NULL,
  ip VARCHAR(64) DEFAULT NULL,
  blocked SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (project_id, server_id, family),
  CONSTRAINT fk_hcloud_server_public_ip_server FOREIGN KEY (project_id, server_id) REFERENCES hcloud_server (project_id, id) ON DELETE CASCADE
);

CREATE TABLE hcloud_server_public_ip_dns_ptr (
  project_id INT NOT NULL,
  server_id BIGINT NOT NULL,
  family VARCHAR(8) NOT NULL,
  ip VARCHAR(64) NOT NULL,
  dns_ptr VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (project_id, server_id, family, ip),
  CONSTRAINT fk_hcloud_server_public_ip_dns_ptr_ip FOREIGN KEY (project_id, server_id, family) REFERENCES hcloud_server_public_ip (project_id, server_id, family) ON DELETE CASCADE
);

CREATE TABLE hcloud_server_firewall (
  project_id INT NOT NULL,
  server_id BIGINT NOT NULL,
  firewall_id BIGINT NOT NULL,
  status VARCHAR(32) DEFAULT NULL,
  PRIMARY KEY (project_id, server_id, firewall_id),
  CONSTRAINT fk_hcloud_server_firewall_server FOREIGN KEY (project_id, server_id) REFERENCES hcloud_server (project_id, id) ON DELETE CASCADE
);

CREATE TABLE hcloud_server_private_net (
  project_id INT NOT NULL,
  server_id BIGINT NOT NULL,
  network_id BIGINT NOT NULL,
  ip VARCHAR(64) DEFAULT NULL,
  mac_address VARCHAR(32) DEFAULT NULL,
  PRIMARY KEY (project_id, server_id, network_id),
  CONSTRAINT fk_hcloud_server_private_net_server FOREIGN KEY (project_id, server_id) REFERENCES hcloud_server (project_id, id) ON DELETE CASCADE
);

CREATE TABLE hcloud_server_private_net_alias_ip (
  project_id INT NOT NULL,
  server_id BIGINT NOT NULL,
  network_id BIGINT NOT NULL,
  alias_ip VARCHAR(64) NOT NULL,
  PRIMARY KEY (project_id, server_id, network_id, alias_ip),
  CONSTRAINT fk_hcloud_server_alias_ip_private_net FOREIGN KEY (project_id, server_id, network_id) REFERENCES hcloud_server_private_net (project_id, server_id, network_id) ON DELETE CASCADE
);

CREATE TABLE hcloud_volume (
  project_id INT NOT NULL,
  id BIGINT NOT NULL,
  name VARCHAR(255) NOT NULL,
  created TIMESTAMP DEFAULT NULL,
  status VARCHAR(32) NOT NULL,
  server_id BIGINT DEFAULT NULL,
  linux_device VARCHAR(255) DEFAULT NULL,
  size DECIMAL(12, 3) DEFAULT NULL,
  format VARCHAR(32) DEFAULT NULL,
  location_id BIGINT DEFAULT NULL,
  protection_delete SMALLINT NOT NULL DEFAULT 0,
  labels JSONB DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  CONSTRAINT fk_hcloud_volume_project FOREIGN KEY (project_id) REFERENCES hcloud_project (id) ON DELETE CASCADE
);

CREATE TABLE hcloud_load_balancer (
  project_id INT NOT NULL,
  id BIGINT NOT NULL,
  name VARCHAR(255) NOT NULL,
  created TIMESTAMP DEFAULT NULL,
  algorithm_type VARCHAR(32) DEFAULT NULL,
  outgoing_traffic BIGINT DEFAULT NULL,
  ingoing_traffic BIGINT DEFAULT NULL,
  included_traffic BIGINT DEFAULT NULL,
  location_id BIGINT DEFAULT NULL,
  load_balancer_type_id BIGINT DEFAULT NULL,
  public_enabled SMALLINT NOT NULL DEFAULT 0,
  public_ipv4 VARCHAR(64) DEFAULT NULL,
  public_ipv4_dns_ptr VARCHAR(255) DEFAULT NULL,
  public_ipv6 VARCHAR(64) DEFAULT NULL,
  public_ipv6_dns_ptr VARCHAR(255) DEFAULT NULL,
  protection_delete SMALLINT NOT NULL DEFAULT 0,
  labels JSONB DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  CONSTRAINT fk_hcloud_load_balancer_project FOREIGN KEY (project_id) REFERENCES hcloud_project (id) ON DELETE CASCADE
);

CREATE TABLE hcloud_load_balancer_service (
  project_id INT NOT NULL,
  load_balancer_id BIGINT NOT NULL,
  listen_port INT NOT NULL,
  protocol VARCHAR(16) NOT NULL,
  destination_port INT DEFAULT NULL,
  proxyprotocol SMALLINT NOT NULL DEFAULT 0,
  health_check_protocol VARCHAR(16) DEFAULT NULL,
  health_check_port INT DEFAULT NULL,
  health_check_interval INT DEFAULT NULL,
  health_check_timeout INT DEFAULT NULL,
  health_check_retries INT DEFAULT NULL,
  health_check_http_domain VARCHAR(255) DEFAULT NULL,
  health_check_http_path VARCHAR(255) DEFAULT NULL,
  health_check_http_response TEXT DEFAULT NULL,
  health_check_http_status_codes JSONB DEFAULT NULL,
  health_check_http_tls SMALLINT NOT NULL DEFAULT 0,
  http_cookie_name VARCHAR(255) DEFAULT NULL,
  http_cookie_lifetime INT DEFAULT NULL,
  http_timeout_idle INT DEFAULT NULL,
  http_sticky_sessions SMALLINT NOT NULL DEFAULT 0,
  http_redirect_http SMALLINT NOT NULL DEFAULT 0,
  http_certificates JSONB DEFAULT NULL,
  PRIMARY KEY (project_id, load_balancer_id, listen_port),
  CONSTRAINT fk_hcloud_lb_service_lb FOREIGN KEY (project_id, load_balancer_id) REFERENCES hcloud_load_balancer (project_id, id) ON DELETE CASCADE
);

CREATE TABLE hcloud_load_balancer_target (
  project_id INT NOT NULL,
  load_balancer_id BIGINT NOT NULL,
  target_index INT NOT NULL,
  parent_index INT DEFAULT NULL,
  type VARCHAR(32) NOT NULL,
  server_id BIGINT DEFAULT NULL,
  server_ip VARCHAR(64) DEFAULT NULL,
  ip_address VARCHAR(64) DEFAULT NULL,
  label_selector VARCHAR(255) DEFAULT NULL,
  use_private_ip SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (project_id, load_balancer_id, target_index),
  CONSTRAINT fk_hcloud_lb_target_lb FOREIGN KEY (project_id, load_balancer_id) REFERENCES hcloud_load_balancer (project_id, id) ON DELETE CASCADE
);

CREATE TABLE hcloud_load_balancer_target_health (
  project_id INT NOT NULL,
  load_balancer_id BIGINT NOT NULL,
  target_index INT NOT NULL,
  listen_port INT NOT NULL,
  status VARCHAR(32) NOT NULL,
  detail VARCHAR(64) DEFAULT NULL,
  http_status_code INT DEFAULT NULL,
  PRIMARY KEY (project_id, load_balancer_id, target_index, listen_port),
  CONSTRAINT fk_hcloud_lb_target_health_target FOREIGN KEY (project_id, load_balancer_id, target_index) REFERENCES hcloud_load_balancer_target (project_id, load_balancer_id, target_index) ON DELETE CASCADE
);

CREATE TABLE hcloud_load_balancer_private_net (
  project_id INT NOT NULL,
  load_balancer_id BIGINT NOT NULL,
  network_id BIGINT NOT NULL,
  ip VARCHAR(64) DEFAULT NULL,
  PRIMARY KEY (project_id, load_balancer_id, network_id),
  CONSTRAINT fk_hcloud_lb_private_net_lb FOREIGN KEY (project_id, load_balancer_id) REFERENCES hcloud_load_balancer (project_id, id) ON DELETE CASCADE
);

CREATE TABLE hcloud_network (
  project_id INT NOT NULL,
  id BIGINT NOT NULL,
  name VARCHAR(255) NOT NULL,
  ip_range VARCHAR(64) DEFAULT NULL,
  created TIMESTAMP DEFAULT NULL,
  expose_routes_to_vswitch SMALLINT NOT NULL DEFAULT 0,
  protection_delete SMALLINT NOT NULL DEFAULT 0,
  labels JSONB DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  CONSTRAINT fk_hcloud_network_project FOREIGN KEY (project_id) REFERENCES hcloud_project (id) ON DELETE CASCADE
);

CREATE TABLE hcloud_network_subnet (
  project_id INT NOT NULL,
  network_id BIGINT NOT NULL,
  ip_range VARCHAR(64) NOT NULL,
  type VARCHAR(32) DEFAULT NULL,
  network_zone VARCHAR(64) DEFAULT NULL,
  gateway VARCHAR(64) DEFAULT NULL,
  vswitch_id BIGINT DEFAULT NULL,
  PRIMARY KEY (project_id, network_id, ip_range),
  CONSTRAINT fk_hcloud_network_subnet_network FOREIGN KEY (project_id, network_id) REFERENCES hcloud_network (project_id, id) ON DELETE CASCADE
);

CREATE TABLE hcloud_network_route (
  project_id INT NOT NULL,
  network_id BIGINT NOT NULL,
  destination VARCHAR(64) NOT NULL,
  gateway VARCHAR(64) DEFAULT NULL,
  PRIMARY KEY (project_id, network_id, destination),
  CONSTRAINT fk_hcloud_network_route_network FOREIGN KEY (project_id, network_id) REFERENCES hcloud_network (project_id, id) ON DELETE CASCADE
);

CREATE TABLE hcloud_firewall (
  project_id INT NOT NULL,
  id BIGINT NOT NULL,
  name VARCHAR(255) NOT NULL,
  created TIMESTAMP DEFAULT NULL,
  labels JSONB DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  CONSTRAINT fk_hcloud_firewall_project FOREIGN KEY (project_id) REFERENCES hcloud_project (id) ON DELETE CASCADE
);

CREATE TABLE hcloud_firewall_rule (
  project_id INT NOT NULL,
  firewall_id BIGINT NOT NULL,
  rule_index INT NOT NULL,
  direction VARCHAR(8) NOT NULL,
  protocol VARCHAR(16) NOT NULL,
  port VARCHAR(32) DEFAULT NULL,
  description VARCHAR(255) DEFAULT NULL,
  source_ips JSONB DEFAULT NULL,
  destination_ips JSONB DEFAULT NULL,
  PRIMARY KEY (project_id, firewall_id, rule_index),
  CONSTRAINT fk_hcloud_firewall_rule_firewall FOREIGN KEY (project_id, firewall_id) REFERENCES hcloud_firewall (project_id, id) ON DELETE CASCADE
);

CREATE TABLE hcloud_firewall_applied_to (
  project_id INT NOT NULL,
  firewall_id BIGINT NOT NULL,
  applied_index INT NOT NULL,
  type VARCHAR(32) NOT NULL,
  server_id BIGINT DEFAULT NULL,
  label_selector VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (project_id, firewall_id, applied_index),
  CONSTRAINT fk_hcloud_firewall_applied_to_firewall FOREIGN KEY (project_id, firewall_id) REFERENCES hcloud_firewall (project_id, id) ON DELETE CASCADE
);

CREATE TABLE hcloud_firewall_applied_resource (
  project_id INT NOT NULL,
  firewall_id BIGINT NOT NULL,
  applied_index INT NOT NULL,
  resource_type VARCHAR(32) NOT NULL,
  server_id BIGINT NOT NULL,
  PRIMARY KEY (project_id, firewall_id, applied_index, server_id),
  CONSTRAINT fk_hcloud_firewall_applied_resource_applied FOREIGN KEY (project_id, firewall_id, applied_index) REFERENCES hcloud_firewall_applied_to (project_id, firewall_id, applied_index) ON DELETE CASCADE
);

CREATE TABLE hcloud_floating_ip (
  project_id INT NOT NULL,
  id BIGINT NOT NULL,
  name VARCHAR(255) DEFAULT NULL,
  description VARCHAR(255) DEFAULT NULL,
  created TIMESTAMP DEFAULT NULL,
  ip VARCHAR(64) NOT NULL,
  type VARCHAR(16) NOT NULL,
  server_id BIGINT DEFAULT NULL,
  blocked SMALLINT NOT NULL DEFAULT 0,
  home_location_id BIGINT DEFAULT NULL,
  protection_delete SMALLINT NOT NULL DEFAULT 0,
  labels JSONB DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  CONSTRAINT fk_hcloud_floating_ip_project FOREIGN KEY (project_id) REFERENCES hcloud_project (id) ON DELETE CASCADE
);

CREATE TABLE hcloud_floating_ip_dns_ptr (
  project_id INT NOT NULL,
  floating_ip_id BIGINT NOT NULL,
  ip VARCHAR(64) NOT NULL,
  dns_ptr VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (project_id, floating_ip_id, ip),
  CONSTRAINT fk_hcloud_floating_ip_dns_ptr_ip FOREIGN KEY (project_id, floating_ip_id) REFERENCES hcloud_floating_ip (project_id, id) ON DELETE CASCADE
);

CREATE TABLE hcloud_primary_ip (
  project_id INT NOT NULL,
  id BIGINT NOT NULL,
  name VARCHAR(255) DEFAULT NULL,
  created TIMESTAMP DEFAULT NULL,
  ip VARCHAR(64) NOT NULL,
  type VARCHAR(16) NOT NULL,
  blocked SMALLINT NOT NULL DEFAULT 0,
  auto_delete SMALLINT NOT NULL DEFAULT 0,
  assignee_type VARCHAR(32) DEFAULT NULL,
  assignee_id BIGINT DEFAULT NULL,
  location_id BIGINT DEFAULT NULL,
  protection_delete SMALLINT NOT NULL DEFAULT 0,
  labels JSONB DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  CONSTRAINT fk_hcloud_primary_ip_project FOREIGN KEY (project_id) REFERENCES hcloud_project (id) ON DELETE CASCADE
);

CREATE TABLE hcloud_primary_ip_dns_ptr (
  project_id INT NOT NULL,
  primary_ip_id BIGINT NOT NULL,
  ip VARCHAR(64) NOT NULL,
  dns_ptr VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (project_id, primary_ip_id, ip),
  CONSTRAINT fk_hcloud_primary_ip_dns_ptr_ip FOREIGN KEY (project_id, primary_ip_id) REFERENCES hcloud_primary_ip (project_id, id) ON DELETE CASCADE
);

CREATE TABLE hcloud_certificate (
  project_id INT NOT NULL,
  id BIGINT NOT NULL,
  name VARCHAR(255) NOT NULL,
  created TIMESTAMP DEFAULT NULL,
  type VARCHAR(32) DEFAULT NULL,
  not_valid_before TIMESTAMP DEFAULT NULL,
  not_valid_after TIMESTAMP DEFAULT NULL,
  fingerprint VARCHAR(255) DEFAULT NULL,
  status_issuance VARCHAR(32) DEFAULT NULL,
  status_renewal VARCHAR(32) DEFAULT NULL,
  status_error_code VARCHAR(128) DEFAULT NULL,
  status_error_message TEXT DEFAULT NULL,
  labels JSONB DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  CONSTRAINT fk_hcloud_certificate_project FOREIGN KEY (project_id) REFERENCES hcloud_project (id) ON DELETE CASCADE
);

CREATE TABLE hcloud_certificate_domain (
  project_id INT NOT NULL,
  certificate_id BIGINT NOT NULL,
  domain_name VARCHAR(255) NOT NULL,
  PRIMARY KEY (project_id, certificate_id, domain_name),
  CONSTRAINT fk_hcloud_certificate_domain_certificate FOREIGN KEY (project_id, certificate_id) REFERENCES hcloud_certificate (project_id, id) ON DELETE CASCADE
);

CREATE TABLE hcloud_certificate_used_by (
  project_id INT NOT NULL,
  certificate_id BIGINT NOT NULL,
  used_by_type VARCHAR(64) NOT NULL,
  used_by_id BIGINT NOT NULL,
  PRIMARY KEY (project_id, certificate_id, used_by_type, used_by_id),
  CONSTRAINT fk_hcloud_certificate_used_by_certificate FOREIGN KEY (project_id, certificate_id) REFERENCES hcloud_certificate (project_id, id) ON DELETE CASCADE
);

CREATE TABLE hcloud_placement_group (
  project_id INT NOT NULL,
  id BIGINT NOT NULL,
  name VARCHAR(255) NOT NULL,
  created TIMESTAMP DEFAULT NULL,
  type VARCHAR(32) DEFAULT NULL,
  labels JSONB DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  CONSTRAINT fk_hcloud_placement_group_project FOREIGN KEY (project_id) REFERENCES hcloud_project (id) ON DELETE CASCADE
);

CREATE TABLE hcloud_ssh_key (
  project_id INT NOT NULL,
  id BIGINT NOT NULL,
  name VARCHAR(255) NOT NULL,
  fingerprint VARCHAR(255) DEFAULT NULL,
  public_key TEXT DEFAULT NULL,
  created TIMESTAMP DEFAULT NULL,
  labels JSONB DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  CONSTRAINT fk_hcloud_ssh_key_project FOREIGN KEY (project_id) REFERENCES hcloud_project (id) ON DELETE CASCADE
);

CREATE TABLE hcloud_zone (
  project_id INT NOT NULL,
  id BIGINT NOT NULL,
  name VARCHAR(255) NOT NULL,
  created TIMESTAMP DEFAULT NULL,
  mode VARCHAR(32) DEFAULT NULL,
  ttl INT DEFAULT NULL,
  status VARCHAR(32) DEFAULT NULL,
  record_count INT DEFAULT NULL,
  registrar VARCHAR(32) DEFAULT NULL,
  delegation_last_check TIMESTAMP DEFAULT NULL,
  delegation_status VARCHAR(32) DEFAULT NULL,
  protection_delete SMALLINT NOT NULL DEFAULT 0,
  labels JSONB DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  CONSTRAINT fk_hcloud_zone_project FOREIGN KEY (project_id) REFERENCES hcloud_project (id) ON DELETE CASCADE
);

CREATE TABLE hcloud_zone_nameserver (
  project_id INT NOT NULL,
  zone_id BIGINT NOT NULL,
  kind VARCHAR(16) NOT NULL,
  address VARCHAR(255) NOT NULL,
  PRIMARY KEY (project_id, zone_id, kind, address),
  CONSTRAINT fk_hcloud_zone_nameserver_zone FOREIGN KEY (project_id, zone_id) REFERENCES hcloud_zone (project_id, id) ON DELETE CASCADE
);

CREATE TABLE hcloud_zone_primary_nameserver (
  project_id INT NOT NULL,
  zone_id BIGINT NOT NULL,
  address VARCHAR(255) NOT NULL,
  port INT DEFAULT NULL,
  tsig_algorithm VARCHAR(32) DEFAULT NULL,
  PRIMARY KEY (project_id, zone_id, address),
  CONSTRAINT fk_hcloud_zone_primary_nameserver_zone FOREIGN KEY (project_id, zone_id) REFERENCES hcloud_zone (project_id, id) ON DELETE CASCADE
);

CREATE TABLE hcloud_zone_rrset (
  project_id INT NOT NULL,
  zone_id BIGINT NOT NULL,
  id VARCHAR(320) NOT NULL,
  name VARCHAR(255) NOT NULL,
  type VARCHAR(16) NOT NULL,
  ttl INT DEFAULT NULL,
  protection_change SMALLINT NOT NULL DEFAULT 0,
  labels JSONB DEFAULT NULL,
  PRIMARY KEY (project_id, zone_id, id),
  CONSTRAINT fk_hcloud_zone_rrset_zone FOREIGN KEY (project_id, zone_id) REFERENCES hcloud_zone (project_id, id) ON DELETE CASCADE
);

CREATE TABLE hcloud_zone_rrset_record (
  project_id INT NOT NULL,
  zone_id BIGINT NOT NULL,
  rrset_id VARCHAR(320) NOT NULL,
  record_index INT NOT NULL,
  value TEXT NOT NULL,
  comment VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (project_id, zone_id, rrset_id, record_index),
  CONSTRAINT fk_hcloud_zone_rrset_record_rrset FOREIGN KEY (project_id, zone_id, rrset_id) REFERENCES hcloud_zone_rrset (project_id, zone_id, id) ON DELETE CASCADE
);

CREATE TABLE hcloud_storage_box (
  project_id INT NOT NULL,
  id BIGINT NOT NULL,
  name VARCHAR(255) NOT NULL,
  created TIMESTAMP DEFAULT NULL,
  status VARCHAR(32) DEFAULT NULL,
  username VARCHAR(128) DEFAULT NULL,
  server VARCHAR(255) DEFAULT NULL,
  system VARCHAR(128) DEFAULT NULL,
  location_id BIGINT DEFAULT NULL,
  storage_box_type_id BIGINT DEFAULT NULL,
  stats_size BIGINT DEFAULT NULL,
  stats_size_data BIGINT DEFAULT NULL,
  stats_size_snapshots BIGINT DEFAULT NULL,
  access_reachable_externally SMALLINT NOT NULL DEFAULT 0,
  access_samba_enabled SMALLINT NOT NULL DEFAULT 0,
  access_ssh_enabled SMALLINT NOT NULL DEFAULT 0,
  access_webdav_enabled SMALLINT NOT NULL DEFAULT 0,
  access_zfs_enabled SMALLINT NOT NULL DEFAULT 0,
  snapshot_plan_max_snapshots INT DEFAULT NULL,
  snapshot_plan_minute INT DEFAULT NULL,
  snapshot_plan_hour INT DEFAULT NULL,
  snapshot_plan_day_of_week INT DEFAULT NULL,
  snapshot_plan_day_of_month INT DEFAULT NULL,
  protection_delete SMALLINT NOT NULL DEFAULT 0,
  labels JSONB DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  CONSTRAINT fk_hcloud_storage_box_project FOREIGN KEY (project_id) REFERENCES hcloud_project (id) ON DELETE CASCADE
);

CREATE TABLE hcloud_storage_box_subaccount (
  project_id INT NOT NULL,
  storage_box_id BIGINT NOT NULL,
  id BIGINT NOT NULL,
  name VARCHAR(255) DEFAULT NULL,
  username VARCHAR(128) DEFAULT NULL,
  home_directory VARCHAR(512) DEFAULT NULL,
  description VARCHAR(255) DEFAULT NULL,
  server VARCHAR(255) DEFAULT NULL,
  created TIMESTAMP DEFAULT NULL,
  access_reachable_externally SMALLINT NOT NULL DEFAULT 0,
  access_samba_enabled SMALLINT NOT NULL DEFAULT 0,
  access_ssh_enabled SMALLINT NOT NULL DEFAULT 0,
  access_webdav_enabled SMALLINT NOT NULL DEFAULT 0,
  access_readonly SMALLINT NOT NULL DEFAULT 0,
  labels JSONB DEFAULT NULL,
  PRIMARY KEY (project_id, storage_box_id, id),
  CONSTRAINT fk_hcloud_storage_box_subaccount_box FOREIGN KEY (project_id, storage_box_id) REFERENCES hcloud_storage_box (project_id, id) ON DELETE CASCADE
);

CREATE TABLE hcloud_storage_box_snapshot (
  project_id INT NOT NULL,
  storage_box_id BIGINT NOT NULL,
  id BIGINT NOT NULL,
  name VARCHAR(255) DEFAULT NULL,
  description VARCHAR(255) DEFAULT NULL,
  is_automatic SMALLINT NOT NULL DEFAULT 0,
  created TIMESTAMP DEFAULT NULL,
  stats_size BIGINT DEFAULT NULL,
  stats_size_filesystem BIGINT DEFAULT NULL,
  labels JSONB DEFAULT NULL,
  PRIMARY KEY (project_id, storage_box_id, id),
  CONSTRAINT fk_hcloud_storage_box_snapshot_box FOREIGN KEY (project_id, storage_box_id) REFERENCES hcloud_storage_box (project_id, id) ON DELETE CASCADE
);

CREATE TABLE hcloud_action (
  project_id INT NOT NULL,
  id BIGINT NOT NULL,
  command VARCHAR(128) NOT NULL,
  status VARCHAR(32) NOT NULL,
  progress INT DEFAULT NULL,
  started TIMESTAMP DEFAULT NULL,
  finished TIMESTAMP DEFAULT NULL,
  error_code VARCHAR(128) DEFAULT NULL,
  error_message TEXT DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  CONSTRAINT fk_hcloud_action_project FOREIGN KEY (project_id) REFERENCES hcloud_project (id) ON DELETE CASCADE
);

CREATE TABLE hcloud_action_resource (
  project_id INT NOT NULL,
  action_id BIGINT NOT NULL,
  resource_type VARCHAR(64) NOT NULL,
  resource_id BIGINT NOT NULL,
  PRIMARY KEY (project_id, action_id, resource_type, resource_id),
  CONSTRAINT fk_hcloud_action_resource_action FOREIGN KEY (project_id, action_id) REFERENCES hcloud_action (project_id, id) ON DELETE CASCADE
);

CREATE TABLE hcloud_metric_sample (
  project_id INT NOT NULL,
  resource_type VARCHAR(32) NOT NULL,
  resource_id BIGINT NOT NULL,
  series_name VARCHAR(128) NOT NULL,
  ts TIMESTAMP NOT NULL,
  value DECIMAL(24, 6) DEFAULT NULL,
  PRIMARY KEY (project_id, resource_type, resource_id, series_name, ts),
  CONSTRAINT fk_hcloud_metric_sample_project FOREIGN KEY (project_id) REFERENCES hcloud_project (id) ON DELETE CASCADE
);

CREATE TABLE hcloud_pricing (
  project_id INT NOT NULL,
  currency VARCHAR(16) DEFAULT NULL,
  vat_rate DECIMAL(10, 4) DEFAULT NULL,
  image_price_per_gb_month_net DECIMAL(20, 10) DEFAULT NULL,
  image_price_per_gb_month_gross DECIMAL(20, 10) DEFAULT NULL,
  volume_price_per_gb_month_net DECIMAL(20, 10) DEFAULT NULL,
  volume_price_per_gb_month_gross DECIMAL(20, 10) DEFAULT NULL,
  server_backup_percentage DECIMAL(10, 4) DEFAULT NULL,
  updated TIMESTAMP NOT NULL,
  PRIMARY KEY (project_id),
  CONSTRAINT fk_hcloud_pricing_project FOREIGN KEY (project_id) REFERENCES hcloud_project (id) ON DELETE CASCADE
);

CREATE TABLE hcloud_pricing_server_type (
  project_id INT NOT NULL,
  server_type_id BIGINT NOT NULL,
  location_name VARCHAR(128) NOT NULL,
  name VARCHAR(128) DEFAULT NULL,
  price_hourly_net DECIMAL(20, 10) DEFAULT NULL,
  price_hourly_gross DECIMAL(20, 10) DEFAULT NULL,
  price_monthly_net DECIMAL(20, 10) DEFAULT NULL,
  price_monthly_gross DECIMAL(20, 10) DEFAULT NULL,
  included_traffic BIGINT DEFAULT NULL,
  price_per_tb_traffic_net DECIMAL(20, 10) DEFAULT NULL,
  price_per_tb_traffic_gross DECIMAL(20, 10) DEFAULT NULL,
  PRIMARY KEY (project_id, server_type_id, location_name),
  CONSTRAINT fk_hcloud_pricing_server_type_project FOREIGN KEY (project_id) REFERENCES hcloud_project (id) ON DELETE CASCADE
);

CREATE TABLE hcloud_pricing_load_balancer_type (
  project_id INT NOT NULL,
  load_balancer_type_id BIGINT NOT NULL,
  location_name VARCHAR(128) NOT NULL,
  name VARCHAR(128) DEFAULT NULL,
  price_hourly_net DECIMAL(20, 10) DEFAULT NULL,
  price_hourly_gross DECIMAL(20, 10) DEFAULT NULL,
  price_monthly_net DECIMAL(20, 10) DEFAULT NULL,
  price_monthly_gross DECIMAL(20, 10) DEFAULT NULL,
  included_traffic BIGINT DEFAULT NULL,
  price_per_tb_traffic_net DECIMAL(20, 10) DEFAULT NULL,
  price_per_tb_traffic_gross DECIMAL(20, 10) DEFAULT NULL,
  PRIMARY KEY (project_id, load_balancer_type_id, location_name),
  CONSTRAINT fk_hcloud_pricing_lb_type_project FOREIGN KEY (project_id) REFERENCES hcloud_project (id) ON DELETE CASCADE
);

CREATE TABLE hcloud_pricing_primary_ip (
  project_id INT NOT NULL,
  type VARCHAR(16) NOT NULL,
  location_name VARCHAR(128) NOT NULL,
  price_hourly_net DECIMAL(20, 10) DEFAULT NULL,
  price_hourly_gross DECIMAL(20, 10) DEFAULT NULL,
  price_monthly_net DECIMAL(20, 10) DEFAULT NULL,
  price_monthly_gross DECIMAL(20, 10) DEFAULT NULL,
  PRIMARY KEY (project_id, type, location_name),
  CONSTRAINT fk_hcloud_pricing_primary_ip_project FOREIGN KEY (project_id) REFERENCES hcloud_project (id) ON DELETE CASCADE
);

CREATE TABLE hcloud_pricing_floating_ip (
  project_id INT NOT NULL,
  type VARCHAR(16) NOT NULL,
  location_name VARCHAR(128) NOT NULL,
  price_monthly_net DECIMAL(20, 10) DEFAULT NULL,
  price_monthly_gross DECIMAL(20, 10) DEFAULT NULL,
  PRIMARY KEY (project_id, type, location_name),
  CONSTRAINT fk_hcloud_pricing_floating_ip_project FOREIGN KEY (project_id) REFERENCES hcloud_project (id) ON DELETE CASCADE
);

CREATE TABLE hcloud_pricing_storage_box_type (
  project_id INT NOT NULL,
  storage_box_type_id BIGINT NOT NULL,
  location_name VARCHAR(128) NOT NULL,
  price_hourly_net DECIMAL(20, 10) DEFAULT NULL,
  price_hourly_gross DECIMAL(20, 10) DEFAULT NULL,
  price_monthly_net DECIMAL(20, 10) DEFAULT NULL,
  price_monthly_gross DECIMAL(20, 10) DEFAULT NULL,
  setup_fee_net DECIMAL(20, 10) DEFAULT NULL,
  setup_fee_gross DECIMAL(20, 10) DEFAULT NULL,
  PRIMARY KEY (project_id, storage_box_type_id, location_name),
  CONSTRAINT fk_hcloud_pricing_storage_box_type_project FOREIGN KEY (project_id) REFERENCES hcloud_project (id) ON DELETE CASCADE
);

CREATE TABLE hcloud_object_storage_bucket (
  project_id INT NOT NULL,
  id VARCHAR(320) NOT NULL,
  location VARCHAR(64) NOT NULL,
  name VARCHAR(255) NOT NULL,
  created TIMESTAMP DEFAULT NULL,
  object_count BIGINT DEFAULT NULL,
  size BIGINT DEFAULT NULL,
  usage_complete SMALLINT NOT NULL DEFAULT 0,
  usage_scanned TIMESTAMP DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  CONSTRAINT fk_hcloud_object_storage_bucket_project FOREIGN KEY (project_id) REFERENCES hcloud_project (id) ON DELETE CASCADE
);

CREATE INDEX idx_hcloud_sync_run_project ON hcloud_sync_run (project_id, started);
CREATE INDEX idx_hcloud_sync_run_resource_run ON hcloud_sync_run_resource (sync_run_id);
CREATE INDEX idx_hcloud_location_name ON hcloud_location (project_id, name);
CREATE INDEX idx_hcloud_server_type_name ON hcloud_server_type (project_id, name);
CREATE INDEX idx_hcloud_load_balancer_type_name ON hcloud_load_balancer_type (project_id, name);
CREATE INDEX idx_hcloud_storage_box_type_name ON hcloud_storage_box_type (project_id, name);
CREATE INDEX idx_hcloud_image_type ON hcloud_image (project_id, type);
CREATE INDEX idx_hcloud_image_status ON hcloud_image (project_id, status);
CREATE INDEX idx_hcloud_iso_name ON hcloud_iso (project_id, name);
CREATE INDEX idx_hcloud_server_name ON hcloud_server (project_id, name);
CREATE INDEX idx_hcloud_server_status ON hcloud_server (project_id, status);
CREATE INDEX idx_hcloud_server_location ON hcloud_server (project_id, location_id);
CREATE INDEX idx_hcloud_server_firewall_status ON hcloud_server_firewall (project_id, status);
CREATE INDEX idx_hcloud_volume_name ON hcloud_volume (project_id, name);
CREATE INDEX idx_hcloud_volume_status ON hcloud_volume (project_id, status);
CREATE INDEX idx_hcloud_volume_server ON hcloud_volume (project_id, server_id);
CREATE INDEX idx_hcloud_load_balancer_name ON hcloud_load_balancer (project_id, name);
CREATE INDEX idx_hcloud_lb_target_server ON hcloud_load_balancer_target (project_id, server_id);
CREATE INDEX idx_hcloud_lb_target_health_status ON hcloud_load_balancer_target_health (project_id, status);
CREATE INDEX idx_hcloud_network_name ON hcloud_network (project_id, name);
CREATE INDEX idx_hcloud_firewall_name ON hcloud_firewall (project_id, name);
CREATE INDEX idx_hcloud_floating_ip_ip ON hcloud_floating_ip (project_id, ip);
CREATE INDEX idx_hcloud_floating_ip_server ON hcloud_floating_ip (project_id, server_id);
CREATE INDEX idx_hcloud_primary_ip_ip ON hcloud_primary_ip (project_id, ip);
CREATE INDEX idx_hcloud_primary_ip_assignee ON hcloud_primary_ip (project_id, assignee_id);
CREATE INDEX idx_hcloud_certificate_name ON hcloud_certificate (project_id, name);
CREATE INDEX idx_hcloud_certificate_expiry ON hcloud_certificate (project_id, not_valid_after);
CREATE INDEX idx_hcloud_placement_group_name ON hcloud_placement_group (project_id, name);
CREATE INDEX idx_hcloud_ssh_key_name ON hcloud_ssh_key (project_id, name);
CREATE INDEX idx_hcloud_ssh_key_fingerprint ON hcloud_ssh_key (project_id, fingerprint);
CREATE INDEX idx_hcloud_zone_name ON hcloud_zone (project_id, name);
CREATE INDEX idx_hcloud_zone_status ON hcloud_zone (project_id, status);
CREATE INDEX idx_hcloud_zone_rrset_type ON hcloud_zone_rrset (project_id, type);
CREATE INDEX idx_hcloud_storage_box_name ON hcloud_storage_box (project_id, name);
CREATE INDEX idx_hcloud_storage_box_status ON hcloud_storage_box (project_id, status);
CREATE INDEX idx_hcloud_storage_box_snapshot_created ON hcloud_storage_box_snapshot (project_id, created);
CREATE INDEX idx_hcloud_action_started ON hcloud_action (project_id, started);
CREATE INDEX idx_hcloud_action_status ON hcloud_action (project_id, status);
CREATE INDEX idx_hcloud_action_resource_lookup ON hcloud_action_resource (project_id, resource_type, resource_id);
CREATE INDEX idx_hcloud_metric_sample_ts ON hcloud_metric_sample (project_id, ts);
CREATE INDEX idx_hcloud_object_storage_bucket_name ON hcloud_object_storage_bucket (project_id, name);
CREATE INDEX idx_hcloud_object_storage_bucket_location ON hcloud_object_storage_bucket (project_id, location);

INSERT INTO hcloud_schema (version, timestamp, success)
  VALUES ('0.2.0', EXTRACT(EPOCH FROM NOW()) * 1000, 'y');
