CREATE TABLE hcloud_schema (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  version VARCHAR(64) NOT NULL,
  timestamp BIGINT UNSIGNED NOT NULL,
  success ENUM('n', 'y') DEFAULT NULL,
  reason TEXT DEFAULT NULL,
  PRIMARY KEY (id),
  CONSTRAINT idx_hcloud_schema_version UNIQUE (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_project (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  config_key VARCHAR(128) NOT NULL,
  name VARCHAR(255) NOT NULL,
  enabled TINYINT UNSIGNED NOT NULL DEFAULT 1,
  created DATETIME NOT NULL,
  PRIMARY KEY (id),
  CONSTRAINT idx_hcloud_project_config_key UNIQUE (config_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_sync_run (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  project_id INT UNSIGNED NOT NULL,
  started DATETIME NOT NULL,
  ended DATETIME DEFAULT NULL,
  status VARCHAR(32) NOT NULL,
  resources_synced INT UNSIGNED NOT NULL DEFAULT 0,
  rows_written INT UNSIGNED NOT NULL DEFAULT 0,
  rows_deleted INT UNSIGNED NOT NULL DEFAULT 0,
  api_requests INT UNSIGNED NOT NULL DEFAULT 0,
  error TEXT DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_hcloud_sync_run_project (project_id, started),
  CONSTRAINT fk_hcloud_sync_run_project FOREIGN KEY (project_id)
    REFERENCES hcloud_project (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_sync_run_resource (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  sync_run_id BIGINT UNSIGNED NOT NULL,
  resource VARCHAR(64) NOT NULL,
  fetched INT UNSIGNED NOT NULL DEFAULT 0,
  written INT UNSIGNED NOT NULL DEFAULT 0,
  deleted INT UNSIGNED NOT NULL DEFAULT 0,
  duration_ms INT UNSIGNED NOT NULL DEFAULT 0,
  error TEXT DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_hcloud_sync_run_resource_run (sync_run_id),
  CONSTRAINT fk_hcloud_sync_run_resource_run FOREIGN KEY (sync_run_id)
    REFERENCES hcloud_sync_run (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_location (
  project_id INT UNSIGNED NOT NULL,
  id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(128) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  country VARCHAR(8) DEFAULT NULL,
  city VARCHAR(128) DEFAULT NULL,
  latitude DECIMAL(10, 6) DEFAULT NULL,
  longitude DECIMAL(10, 6) DEFAULT NULL,
  network_zone VARCHAR(64) DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  KEY idx_hcloud_location_name (project_id, name),
  CONSTRAINT fk_hcloud_location_project FOREIGN KEY (project_id)
    REFERENCES hcloud_project (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_server_type (
  project_id INT UNSIGNED NOT NULL,
  id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(128) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  cores INT UNSIGNED DEFAULT NULL,
  memory DECIMAL(12, 2) DEFAULT NULL,
  disk DECIMAL(12, 2) DEFAULT NULL,
  storage_type VARCHAR(32) DEFAULT NULL,
  cpu_type VARCHAR(32) DEFAULT NULL,
  category VARCHAR(64) DEFAULT NULL,
  architecture VARCHAR(16) DEFAULT NULL,
  deprecation_announced DATETIME DEFAULT NULL,
  deprecation_unavailable_after DATETIME DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  KEY idx_hcloud_server_type_name (project_id, name),
  CONSTRAINT fk_hcloud_server_type_project FOREIGN KEY (project_id)
    REFERENCES hcloud_project (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_server_type_location (
  project_id INT UNSIGNED NOT NULL,
  server_type_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NOT NULL,
  location_name VARCHAR(128) NOT NULL,
  recommended TINYINT UNSIGNED NOT NULL DEFAULT 0,
  available TINYINT UNSIGNED NOT NULL DEFAULT 0,
  deprecation_announced DATETIME DEFAULT NULL,
  deprecation_unavailable_after DATETIME DEFAULT NULL,
  PRIMARY KEY (project_id, server_type_id, location_id),
  CONSTRAINT fk_hcloud_server_type_location_type FOREIGN KEY (project_id, server_type_id)
    REFERENCES hcloud_server_type (project_id, id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_load_balancer_type (
  project_id INT UNSIGNED NOT NULL,
  id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(128) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  max_connections INT UNSIGNED DEFAULT NULL,
  max_services INT UNSIGNED DEFAULT NULL,
  max_targets INT UNSIGNED DEFAULT NULL,
  max_assigned_certificates INT UNSIGNED DEFAULT NULL,
  deprecation_announced DATETIME DEFAULT NULL,
  deprecation_unavailable_after DATETIME DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  KEY idx_hcloud_load_balancer_type_name (project_id, name),
  CONSTRAINT fk_hcloud_load_balancer_type_project FOREIGN KEY (project_id)
    REFERENCES hcloud_project (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_storage_box_type (
  project_id INT UNSIGNED NOT NULL,
  id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(128) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  size BIGINT UNSIGNED DEFAULT NULL,
  snapshot_limit INT UNSIGNED DEFAULT NULL,
  automatic_snapshot_limit INT UNSIGNED DEFAULT NULL,
  subaccounts_limit INT UNSIGNED DEFAULT NULL,
  deprecation_announced DATETIME DEFAULT NULL,
  deprecation_unavailable_after DATETIME DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  KEY idx_hcloud_storage_box_type_name (project_id, name),
  CONSTRAINT fk_hcloud_storage_box_type_project FOREIGN KEY (project_id)
    REFERENCES hcloud_project (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_image (
  project_id INT UNSIGNED NOT NULL,
  id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) DEFAULT NULL,
  description VARCHAR(255) DEFAULT NULL,
  type VARCHAR(32) DEFAULT NULL,
  status VARCHAR(32) DEFAULT NULL,
  created DATETIME DEFAULT NULL,
  image_size DECIMAL(12, 3) DEFAULT NULL,
  disk_size DECIMAL(12, 3) DEFAULT NULL,
  architecture VARCHAR(16) DEFAULT NULL,
  os_flavor VARCHAR(32) DEFAULT NULL,
  os_version VARCHAR(64) DEFAULT NULL,
  rapid_deploy TINYINT UNSIGNED NOT NULL DEFAULT 0,
  bound_to BIGINT UNSIGNED DEFAULT NULL,
  created_from_id BIGINT UNSIGNED DEFAULT NULL,
  created_from_name VARCHAR(255) DEFAULT NULL,
  deleted DATETIME DEFAULT NULL,
  protection_delete TINYINT UNSIGNED NOT NULL DEFAULT 0,
  deprecation_announced DATETIME DEFAULT NULL,
  deprecation_unavailable_after DATETIME DEFAULT NULL,
  labels TEXT DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  KEY idx_hcloud_image_type (project_id, type),
  KEY idx_hcloud_image_status (project_id, status),
  CONSTRAINT fk_hcloud_image_project FOREIGN KEY (project_id)
    REFERENCES hcloud_project (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_iso (
  project_id INT UNSIGNED NOT NULL,
  id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) DEFAULT NULL,
  description VARCHAR(255) DEFAULT NULL,
  type VARCHAR(32) DEFAULT NULL,
  architecture VARCHAR(16) DEFAULT NULL,
  deprecation_announced DATETIME DEFAULT NULL,
  deprecation_unavailable_after DATETIME DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  KEY idx_hcloud_iso_name (project_id, name),
  CONSTRAINT fk_hcloud_iso_project FOREIGN KEY (project_id)
    REFERENCES hcloud_project (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_server (
  project_id INT UNSIGNED NOT NULL,
  id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  status VARCHAR(32) NOT NULL,
  created DATETIME DEFAULT NULL,
  locked TINYINT UNSIGNED NOT NULL DEFAULT 0,
  rescue_enabled TINYINT UNSIGNED NOT NULL DEFAULT 0,
  backup_window VARCHAR(32) DEFAULT NULL,
  outgoing_traffic BIGINT UNSIGNED DEFAULT NULL,
  ingoing_traffic BIGINT UNSIGNED DEFAULT NULL,
  included_traffic BIGINT UNSIGNED DEFAULT NULL,
  primary_disk_size DECIMAL(12, 3) DEFAULT NULL,
  server_type_id BIGINT UNSIGNED DEFAULT NULL,
  location_id BIGINT UNSIGNED DEFAULT NULL,
  image_id BIGINT UNSIGNED DEFAULT NULL,
  iso_id BIGINT UNSIGNED DEFAULT NULL,
  placement_group_id BIGINT UNSIGNED DEFAULT NULL,
  protection_delete TINYINT UNSIGNED NOT NULL DEFAULT 0,
  protection_rebuild TINYINT UNSIGNED NOT NULL DEFAULT 0,
  labels TEXT DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  KEY idx_hcloud_server_name (project_id, name),
  KEY idx_hcloud_server_status (project_id, status),
  KEY idx_hcloud_server_location (project_id, location_id),
  CONSTRAINT fk_hcloud_server_project FOREIGN KEY (project_id)
    REFERENCES hcloud_project (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_server_public_ip (
  project_id INT UNSIGNED NOT NULL,
  server_id BIGINT UNSIGNED NOT NULL,
  family VARCHAR(8) NOT NULL,
  ip_id BIGINT UNSIGNED DEFAULT NULL,
  ip VARCHAR(64) DEFAULT NULL,
  blocked TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (project_id, server_id, family),
  CONSTRAINT fk_hcloud_server_public_ip_server FOREIGN KEY (project_id, server_id)
    REFERENCES hcloud_server (project_id, id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_server_public_ip_dns_ptr (
  project_id INT UNSIGNED NOT NULL,
  server_id BIGINT UNSIGNED NOT NULL,
  family VARCHAR(8) NOT NULL,
  ip VARCHAR(64) NOT NULL,
  dns_ptr VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (project_id, server_id, family, ip),
  CONSTRAINT fk_hcloud_server_public_ip_dns_ptr_ip FOREIGN KEY (project_id, server_id, family)
    REFERENCES hcloud_server_public_ip (project_id, server_id, family) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_server_firewall (
  project_id INT UNSIGNED NOT NULL,
  server_id BIGINT UNSIGNED NOT NULL,
  firewall_id BIGINT UNSIGNED NOT NULL,
  status VARCHAR(32) DEFAULT NULL,
  PRIMARY KEY (project_id, server_id, firewall_id),
  KEY idx_hcloud_server_firewall_status (project_id, status),
  CONSTRAINT fk_hcloud_server_firewall_server FOREIGN KEY (project_id, server_id)
    REFERENCES hcloud_server (project_id, id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_server_private_net (
  project_id INT UNSIGNED NOT NULL,
  server_id BIGINT UNSIGNED NOT NULL,
  network_id BIGINT UNSIGNED NOT NULL,
  ip VARCHAR(64) DEFAULT NULL,
  mac_address VARCHAR(32) DEFAULT NULL,
  PRIMARY KEY (project_id, server_id, network_id),
  CONSTRAINT fk_hcloud_server_private_net_server FOREIGN KEY (project_id, server_id)
    REFERENCES hcloud_server (project_id, id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_server_private_net_alias_ip (
  project_id INT UNSIGNED NOT NULL,
  server_id BIGINT UNSIGNED NOT NULL,
  network_id BIGINT UNSIGNED NOT NULL,
  alias_ip VARCHAR(64) NOT NULL,
  PRIMARY KEY (project_id, server_id, network_id, alias_ip),
  CONSTRAINT fk_hcloud_server_alias_ip_private_net FOREIGN KEY (project_id, server_id, network_id)
    REFERENCES hcloud_server_private_net (project_id, server_id, network_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_volume (
  project_id INT UNSIGNED NOT NULL,
  id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  created DATETIME DEFAULT NULL,
  status VARCHAR(32) NOT NULL,
  server_id BIGINT UNSIGNED DEFAULT NULL,
  linux_device VARCHAR(255) DEFAULT NULL,
  size DECIMAL(12, 3) DEFAULT NULL,
  format VARCHAR(32) DEFAULT NULL,
  location_id BIGINT UNSIGNED DEFAULT NULL,
  protection_delete TINYINT UNSIGNED NOT NULL DEFAULT 0,
  labels TEXT DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  KEY idx_hcloud_volume_name (project_id, name),
  KEY idx_hcloud_volume_status (project_id, status),
  KEY idx_hcloud_volume_server (project_id, server_id),
  CONSTRAINT fk_hcloud_volume_project FOREIGN KEY (project_id)
    REFERENCES hcloud_project (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_load_balancer (
  project_id INT UNSIGNED NOT NULL,
  id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  created DATETIME DEFAULT NULL,
  algorithm_type VARCHAR(32) DEFAULT NULL,
  outgoing_traffic BIGINT UNSIGNED DEFAULT NULL,
  ingoing_traffic BIGINT UNSIGNED DEFAULT NULL,
  included_traffic BIGINT UNSIGNED DEFAULT NULL,
  location_id BIGINT UNSIGNED DEFAULT NULL,
  load_balancer_type_id BIGINT UNSIGNED DEFAULT NULL,
  public_enabled TINYINT UNSIGNED NOT NULL DEFAULT 0,
  public_ipv4 VARCHAR(64) DEFAULT NULL,
  public_ipv4_dns_ptr VARCHAR(255) DEFAULT NULL,
  public_ipv6 VARCHAR(64) DEFAULT NULL,
  public_ipv6_dns_ptr VARCHAR(255) DEFAULT NULL,
  protection_delete TINYINT UNSIGNED NOT NULL DEFAULT 0,
  labels TEXT DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  KEY idx_hcloud_load_balancer_name (project_id, name),
  CONSTRAINT fk_hcloud_load_balancer_project FOREIGN KEY (project_id)
    REFERENCES hcloud_project (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_load_balancer_service (
  project_id INT UNSIGNED NOT NULL,
  load_balancer_id BIGINT UNSIGNED NOT NULL,
  listen_port INT UNSIGNED NOT NULL,
  protocol VARCHAR(16) NOT NULL,
  destination_port INT UNSIGNED DEFAULT NULL,
  proxyprotocol TINYINT UNSIGNED NOT NULL DEFAULT 0,
  health_check_protocol VARCHAR(16) DEFAULT NULL,
  health_check_port INT UNSIGNED DEFAULT NULL,
  health_check_interval INT UNSIGNED DEFAULT NULL,
  health_check_timeout INT UNSIGNED DEFAULT NULL,
  health_check_retries INT UNSIGNED DEFAULT NULL,
  health_check_http_domain VARCHAR(255) DEFAULT NULL,
  health_check_http_path VARCHAR(255) DEFAULT NULL,
  health_check_http_response TEXT DEFAULT NULL,
  health_check_http_status_codes TEXT DEFAULT NULL,
  health_check_http_tls TINYINT UNSIGNED NOT NULL DEFAULT 0,
  http_cookie_name VARCHAR(255) DEFAULT NULL,
  http_cookie_lifetime INT UNSIGNED DEFAULT NULL,
  http_timeout_idle INT UNSIGNED DEFAULT NULL,
  http_sticky_sessions TINYINT UNSIGNED NOT NULL DEFAULT 0,
  http_redirect_http TINYINT UNSIGNED NOT NULL DEFAULT 0,
  http_certificates TEXT DEFAULT NULL,
  PRIMARY KEY (project_id, load_balancer_id, listen_port),
  CONSTRAINT fk_hcloud_lb_service_lb FOREIGN KEY (project_id, load_balancer_id)
    REFERENCES hcloud_load_balancer (project_id, id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_load_balancer_target (
  project_id INT UNSIGNED NOT NULL,
  load_balancer_id BIGINT UNSIGNED NOT NULL,
  target_index INT UNSIGNED NOT NULL,
  parent_index INT UNSIGNED DEFAULT NULL,
  type VARCHAR(32) NOT NULL,
  server_id BIGINT UNSIGNED DEFAULT NULL,
  server_ip VARCHAR(64) DEFAULT NULL,
  ip_address VARCHAR(64) DEFAULT NULL,
  label_selector VARCHAR(255) DEFAULT NULL,
  use_private_ip TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (project_id, load_balancer_id, target_index),
  KEY idx_hcloud_lb_target_server (project_id, server_id),
  CONSTRAINT fk_hcloud_lb_target_lb FOREIGN KEY (project_id, load_balancer_id)
    REFERENCES hcloud_load_balancer (project_id, id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_load_balancer_target_health (
  project_id INT UNSIGNED NOT NULL,
  load_balancer_id BIGINT UNSIGNED NOT NULL,
  target_index INT UNSIGNED NOT NULL,
  listen_port INT UNSIGNED NOT NULL,
  status VARCHAR(32) NOT NULL,
  detail VARCHAR(64) DEFAULT NULL,
  http_status_code INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (project_id, load_balancer_id, target_index, listen_port),
  KEY idx_hcloud_lb_target_health_status (project_id, status),
  CONSTRAINT fk_hcloud_lb_target_health_target FOREIGN KEY (project_id, load_balancer_id, target_index)
    REFERENCES hcloud_load_balancer_target (project_id, load_balancer_id, target_index) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_load_balancer_private_net (
  project_id INT UNSIGNED NOT NULL,
  load_balancer_id BIGINT UNSIGNED NOT NULL,
  network_id BIGINT UNSIGNED NOT NULL,
  ip VARCHAR(64) DEFAULT NULL,
  PRIMARY KEY (project_id, load_balancer_id, network_id),
  CONSTRAINT fk_hcloud_lb_private_net_lb FOREIGN KEY (project_id, load_balancer_id)
    REFERENCES hcloud_load_balancer (project_id, id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_network (
  project_id INT UNSIGNED NOT NULL,
  id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  ip_range VARCHAR(64) DEFAULT NULL,
  created DATETIME DEFAULT NULL,
  expose_routes_to_vswitch TINYINT UNSIGNED NOT NULL DEFAULT 0,
  protection_delete TINYINT UNSIGNED NOT NULL DEFAULT 0,
  labels TEXT DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  KEY idx_hcloud_network_name (project_id, name),
  CONSTRAINT fk_hcloud_network_project FOREIGN KEY (project_id)
    REFERENCES hcloud_project (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_network_subnet (
  project_id INT UNSIGNED NOT NULL,
  network_id BIGINT UNSIGNED NOT NULL,
  ip_range VARCHAR(64) NOT NULL,
  type VARCHAR(32) DEFAULT NULL,
  network_zone VARCHAR(64) DEFAULT NULL,
  gateway VARCHAR(64) DEFAULT NULL,
  vswitch_id BIGINT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (project_id, network_id, ip_range),
  CONSTRAINT fk_hcloud_network_subnet_network FOREIGN KEY (project_id, network_id)
    REFERENCES hcloud_network (project_id, id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_network_route (
  project_id INT UNSIGNED NOT NULL,
  network_id BIGINT UNSIGNED NOT NULL,
  destination VARCHAR(64) NOT NULL,
  gateway VARCHAR(64) DEFAULT NULL,
  PRIMARY KEY (project_id, network_id, destination),
  CONSTRAINT fk_hcloud_network_route_network FOREIGN KEY (project_id, network_id)
    REFERENCES hcloud_network (project_id, id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_firewall (
  project_id INT UNSIGNED NOT NULL,
  id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  created DATETIME DEFAULT NULL,
  labels TEXT DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  KEY idx_hcloud_firewall_name (project_id, name),
  CONSTRAINT fk_hcloud_firewall_project FOREIGN KEY (project_id)
    REFERENCES hcloud_project (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_firewall_rule (
  project_id INT UNSIGNED NOT NULL,
  firewall_id BIGINT UNSIGNED NOT NULL,
  rule_index INT UNSIGNED NOT NULL,
  direction VARCHAR(8) NOT NULL,
  protocol VARCHAR(16) NOT NULL,
  port VARCHAR(32) DEFAULT NULL,
  description VARCHAR(255) DEFAULT NULL,
  source_ips TEXT DEFAULT NULL,
  destination_ips TEXT DEFAULT NULL,
  PRIMARY KEY (project_id, firewall_id, rule_index),
  CONSTRAINT fk_hcloud_firewall_rule_firewall FOREIGN KEY (project_id, firewall_id)
    REFERENCES hcloud_firewall (project_id, id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_firewall_applied_to (
  project_id INT UNSIGNED NOT NULL,
  firewall_id BIGINT UNSIGNED NOT NULL,
  applied_index INT UNSIGNED NOT NULL,
  type VARCHAR(32) NOT NULL,
  server_id BIGINT UNSIGNED DEFAULT NULL,
  label_selector VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (project_id, firewall_id, applied_index),
  CONSTRAINT fk_hcloud_firewall_applied_to_firewall FOREIGN KEY (project_id, firewall_id)
    REFERENCES hcloud_firewall (project_id, id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_firewall_applied_resource (
  project_id INT UNSIGNED NOT NULL,
  firewall_id BIGINT UNSIGNED NOT NULL,
  applied_index INT UNSIGNED NOT NULL,
  resource_type VARCHAR(32) NOT NULL,
  server_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (project_id, firewall_id, applied_index, server_id),
  CONSTRAINT fk_hcloud_firewall_applied_resource_applied FOREIGN KEY (project_id, firewall_id, applied_index)
    REFERENCES hcloud_firewall_applied_to (project_id, firewall_id, applied_index) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_floating_ip (
  project_id INT UNSIGNED NOT NULL,
  id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) DEFAULT NULL,
  description VARCHAR(255) DEFAULT NULL,
  created DATETIME DEFAULT NULL,
  ip VARCHAR(64) NOT NULL,
  type VARCHAR(16) NOT NULL,
  server_id BIGINT UNSIGNED DEFAULT NULL,
  blocked TINYINT UNSIGNED NOT NULL DEFAULT 0,
  home_location_id BIGINT UNSIGNED DEFAULT NULL,
  protection_delete TINYINT UNSIGNED NOT NULL DEFAULT 0,
  labels TEXT DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  KEY idx_hcloud_floating_ip_ip (project_id, ip),
  KEY idx_hcloud_floating_ip_server (project_id, server_id),
  CONSTRAINT fk_hcloud_floating_ip_project FOREIGN KEY (project_id)
    REFERENCES hcloud_project (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_floating_ip_dns_ptr (
  project_id INT UNSIGNED NOT NULL,
  floating_ip_id BIGINT UNSIGNED NOT NULL,
  ip VARCHAR(64) NOT NULL,
  dns_ptr VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (project_id, floating_ip_id, ip),
  CONSTRAINT fk_hcloud_floating_ip_dns_ptr_ip FOREIGN KEY (project_id, floating_ip_id)
    REFERENCES hcloud_floating_ip (project_id, id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_primary_ip (
  project_id INT UNSIGNED NOT NULL,
  id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) DEFAULT NULL,
  created DATETIME DEFAULT NULL,
  ip VARCHAR(64) NOT NULL,
  type VARCHAR(16) NOT NULL,
  blocked TINYINT UNSIGNED NOT NULL DEFAULT 0,
  auto_delete TINYINT UNSIGNED NOT NULL DEFAULT 0,
  assignee_type VARCHAR(32) DEFAULT NULL,
  assignee_id BIGINT UNSIGNED DEFAULT NULL,
  location_id BIGINT UNSIGNED DEFAULT NULL,
  protection_delete TINYINT UNSIGNED NOT NULL DEFAULT 0,
  labels TEXT DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  KEY idx_hcloud_primary_ip_ip (project_id, ip),
  KEY idx_hcloud_primary_ip_assignee (project_id, assignee_id),
  CONSTRAINT fk_hcloud_primary_ip_project FOREIGN KEY (project_id)
    REFERENCES hcloud_project (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_primary_ip_dns_ptr (
  project_id INT UNSIGNED NOT NULL,
  primary_ip_id BIGINT UNSIGNED NOT NULL,
  ip VARCHAR(64) NOT NULL,
  dns_ptr VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (project_id, primary_ip_id, ip),
  CONSTRAINT fk_hcloud_primary_ip_dns_ptr_ip FOREIGN KEY (project_id, primary_ip_id)
    REFERENCES hcloud_primary_ip (project_id, id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_certificate (
  project_id INT UNSIGNED NOT NULL,
  id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  created DATETIME DEFAULT NULL,
  type VARCHAR(32) DEFAULT NULL,
  not_valid_before DATETIME DEFAULT NULL,
  not_valid_after DATETIME DEFAULT NULL,
  fingerprint VARCHAR(255) DEFAULT NULL,
  status_issuance VARCHAR(32) DEFAULT NULL,
  status_renewal VARCHAR(32) DEFAULT NULL,
  status_error_code VARCHAR(128) DEFAULT NULL,
  status_error_message TEXT DEFAULT NULL,
  labels TEXT DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  KEY idx_hcloud_certificate_name (project_id, name),
  KEY idx_hcloud_certificate_expiry (project_id, not_valid_after),
  CONSTRAINT fk_hcloud_certificate_project FOREIGN KEY (project_id)
    REFERENCES hcloud_project (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_certificate_domain (
  project_id INT UNSIGNED NOT NULL,
  certificate_id BIGINT UNSIGNED NOT NULL,
  domain_name VARCHAR(255) NOT NULL,
  PRIMARY KEY (project_id, certificate_id, domain_name),
  CONSTRAINT fk_hcloud_certificate_domain_certificate FOREIGN KEY (project_id, certificate_id)
    REFERENCES hcloud_certificate (project_id, id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_certificate_used_by (
  project_id INT UNSIGNED NOT NULL,
  certificate_id BIGINT UNSIGNED NOT NULL,
  used_by_type VARCHAR(64) NOT NULL,
  used_by_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (project_id, certificate_id, used_by_type, used_by_id),
  CONSTRAINT fk_hcloud_certificate_used_by_certificate FOREIGN KEY (project_id, certificate_id)
    REFERENCES hcloud_certificate (project_id, id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_placement_group (
  project_id INT UNSIGNED NOT NULL,
  id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  created DATETIME DEFAULT NULL,
  type VARCHAR(32) DEFAULT NULL,
  labels TEXT DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  KEY idx_hcloud_placement_group_name (project_id, name),
  CONSTRAINT fk_hcloud_placement_group_project FOREIGN KEY (project_id)
    REFERENCES hcloud_project (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_ssh_key (
  project_id INT UNSIGNED NOT NULL,
  id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  fingerprint VARCHAR(255) DEFAULT NULL,
  public_key TEXT DEFAULT NULL,
  created DATETIME DEFAULT NULL,
  labels TEXT DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  KEY idx_hcloud_ssh_key_name (project_id, name),
  KEY idx_hcloud_ssh_key_fingerprint (project_id, fingerprint),
  CONSTRAINT fk_hcloud_ssh_key_project FOREIGN KEY (project_id)
    REFERENCES hcloud_project (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_zone (
  project_id INT UNSIGNED NOT NULL,
  id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  created DATETIME DEFAULT NULL,
  mode VARCHAR(32) DEFAULT NULL,
  ttl INT UNSIGNED DEFAULT NULL,
  status VARCHAR(32) DEFAULT NULL,
  record_count INT UNSIGNED DEFAULT NULL,
  registrar VARCHAR(32) DEFAULT NULL,
  delegation_last_check DATETIME DEFAULT NULL,
  delegation_status VARCHAR(32) DEFAULT NULL,
  protection_delete TINYINT UNSIGNED NOT NULL DEFAULT 0,
  labels TEXT DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  KEY idx_hcloud_zone_name (project_id, name),
  KEY idx_hcloud_zone_status (project_id, status),
  CONSTRAINT fk_hcloud_zone_project FOREIGN KEY (project_id)
    REFERENCES hcloud_project (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_zone_nameserver (
  project_id INT UNSIGNED NOT NULL,
  zone_id BIGINT UNSIGNED NOT NULL,
  kind VARCHAR(16) NOT NULL,
  address VARCHAR(255) NOT NULL,
  PRIMARY KEY (project_id, zone_id, kind, address),
  CONSTRAINT fk_hcloud_zone_nameserver_zone FOREIGN KEY (project_id, zone_id)
    REFERENCES hcloud_zone (project_id, id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_zone_primary_nameserver (
  project_id INT UNSIGNED NOT NULL,
  zone_id BIGINT UNSIGNED NOT NULL,
  address VARCHAR(255) NOT NULL,
  port INT UNSIGNED DEFAULT NULL,
  tsig_algorithm VARCHAR(32) DEFAULT NULL,
  PRIMARY KEY (project_id, zone_id, address),
  CONSTRAINT fk_hcloud_zone_primary_nameserver_zone FOREIGN KEY (project_id, zone_id)
    REFERENCES hcloud_zone (project_id, id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_zone_rrset (
  project_id INT UNSIGNED NOT NULL,
  zone_id BIGINT UNSIGNED NOT NULL,
  id VARCHAR(320) NOT NULL,
  name VARCHAR(255) NOT NULL,
  type VARCHAR(16) NOT NULL,
  ttl INT UNSIGNED DEFAULT NULL,
  protection_change TINYINT UNSIGNED NOT NULL DEFAULT 0,
  labels TEXT DEFAULT NULL,
  PRIMARY KEY (project_id, zone_id, id),
  KEY idx_hcloud_zone_rrset_type (project_id, type),
  CONSTRAINT fk_hcloud_zone_rrset_zone FOREIGN KEY (project_id, zone_id)
    REFERENCES hcloud_zone (project_id, id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_zone_rrset_record (
  project_id INT UNSIGNED NOT NULL,
  zone_id BIGINT UNSIGNED NOT NULL,
  rrset_id VARCHAR(320) NOT NULL,
  record_index INT UNSIGNED NOT NULL,
  value TEXT NOT NULL,
  comment VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (project_id, zone_id, rrset_id, record_index),
  CONSTRAINT fk_hcloud_zone_rrset_record_rrset FOREIGN KEY (project_id, zone_id, rrset_id)
    REFERENCES hcloud_zone_rrset (project_id, zone_id, id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_storage_box (
  project_id INT UNSIGNED NOT NULL,
  id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  created DATETIME DEFAULT NULL,
  status VARCHAR(32) DEFAULT NULL,
  username VARCHAR(128) DEFAULT NULL,
  server VARCHAR(255) DEFAULT NULL,
  system VARCHAR(128) DEFAULT NULL,
  location_id BIGINT UNSIGNED DEFAULT NULL,
  storage_box_type_id BIGINT UNSIGNED DEFAULT NULL,
  stats_size BIGINT UNSIGNED DEFAULT NULL,
  stats_size_data BIGINT UNSIGNED DEFAULT NULL,
  stats_size_snapshots BIGINT UNSIGNED DEFAULT NULL,
  access_reachable_externally TINYINT UNSIGNED NOT NULL DEFAULT 0,
  access_samba_enabled TINYINT UNSIGNED NOT NULL DEFAULT 0,
  access_ssh_enabled TINYINT UNSIGNED NOT NULL DEFAULT 0,
  access_webdav_enabled TINYINT UNSIGNED NOT NULL DEFAULT 0,
  access_zfs_enabled TINYINT UNSIGNED NOT NULL DEFAULT 0,
  snapshot_plan_max_snapshots INT UNSIGNED DEFAULT NULL,
  snapshot_plan_minute INT UNSIGNED DEFAULT NULL,
  snapshot_plan_hour INT UNSIGNED DEFAULT NULL,
  snapshot_plan_day_of_week INT UNSIGNED DEFAULT NULL,
  snapshot_plan_day_of_month INT UNSIGNED DEFAULT NULL,
  protection_delete TINYINT UNSIGNED NOT NULL DEFAULT 0,
  labels TEXT DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  KEY idx_hcloud_storage_box_name (project_id, name),
  KEY idx_hcloud_storage_box_status (project_id, status),
  CONSTRAINT fk_hcloud_storage_box_project FOREIGN KEY (project_id)
    REFERENCES hcloud_project (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_storage_box_subaccount (
  project_id INT UNSIGNED NOT NULL,
  storage_box_id BIGINT UNSIGNED NOT NULL,
  id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) DEFAULT NULL,
  username VARCHAR(128) DEFAULT NULL,
  home_directory VARCHAR(512) DEFAULT NULL,
  description VARCHAR(255) DEFAULT NULL,
  server VARCHAR(255) DEFAULT NULL,
  created DATETIME DEFAULT NULL,
  access_reachable_externally TINYINT UNSIGNED NOT NULL DEFAULT 0,
  access_samba_enabled TINYINT UNSIGNED NOT NULL DEFAULT 0,
  access_ssh_enabled TINYINT UNSIGNED NOT NULL DEFAULT 0,
  access_webdav_enabled TINYINT UNSIGNED NOT NULL DEFAULT 0,
  access_readonly TINYINT UNSIGNED NOT NULL DEFAULT 0,
  labels TEXT DEFAULT NULL,
  PRIMARY KEY (project_id, storage_box_id, id),
  CONSTRAINT fk_hcloud_storage_box_subaccount_box FOREIGN KEY (project_id, storage_box_id)
    REFERENCES hcloud_storage_box (project_id, id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_storage_box_snapshot (
  project_id INT UNSIGNED NOT NULL,
  storage_box_id BIGINT UNSIGNED NOT NULL,
  id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) DEFAULT NULL,
  description VARCHAR(255) DEFAULT NULL,
  is_automatic TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created DATETIME DEFAULT NULL,
  stats_size BIGINT UNSIGNED DEFAULT NULL,
  stats_size_filesystem BIGINT UNSIGNED DEFAULT NULL,
  labels TEXT DEFAULT NULL,
  PRIMARY KEY (project_id, storage_box_id, id),
  KEY idx_hcloud_storage_box_snapshot_created (project_id, created),
  CONSTRAINT fk_hcloud_storage_box_snapshot_box FOREIGN KEY (project_id, storage_box_id)
    REFERENCES hcloud_storage_box (project_id, id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_action (
  project_id INT UNSIGNED NOT NULL,
  id BIGINT UNSIGNED NOT NULL,
  command VARCHAR(128) NOT NULL,
  status VARCHAR(32) NOT NULL,
  progress INT UNSIGNED DEFAULT NULL,
  started DATETIME DEFAULT NULL,
  finished DATETIME DEFAULT NULL,
  error_code VARCHAR(128) DEFAULT NULL,
  error_message TEXT DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  KEY idx_hcloud_action_started (project_id, started),
  KEY idx_hcloud_action_status (project_id, status),
  CONSTRAINT fk_hcloud_action_project FOREIGN KEY (project_id)
    REFERENCES hcloud_project (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_action_resource (
  project_id INT UNSIGNED NOT NULL,
  action_id BIGINT UNSIGNED NOT NULL,
  resource_type VARCHAR(64) NOT NULL,
  resource_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (project_id, action_id, resource_type, resource_id),
  KEY idx_hcloud_action_resource_lookup (project_id, resource_type, resource_id),
  CONSTRAINT fk_hcloud_action_resource_action FOREIGN KEY (project_id, action_id)
    REFERENCES hcloud_action (project_id, id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_metric_sample (
  project_id INT UNSIGNED NOT NULL,
  resource_type VARCHAR(32) NOT NULL,
  resource_id BIGINT UNSIGNED NOT NULL,
  series_name VARCHAR(128) NOT NULL,
  ts DATETIME NOT NULL,
  value DECIMAL(24, 6) DEFAULT NULL,
  PRIMARY KEY (project_id, resource_type, resource_id, series_name, ts),
  KEY idx_hcloud_metric_sample_ts (project_id, ts),
  CONSTRAINT fk_hcloud_metric_sample_project FOREIGN KEY (project_id)
    REFERENCES hcloud_project (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_pricing (
  project_id INT UNSIGNED NOT NULL,
  currency VARCHAR(16) DEFAULT NULL,
  vat_rate DECIMAL(10, 4) DEFAULT NULL,
  image_price_per_gb_month_net DECIMAL(20, 10) DEFAULT NULL,
  image_price_per_gb_month_gross DECIMAL(20, 10) DEFAULT NULL,
  volume_price_per_gb_month_net DECIMAL(20, 10) DEFAULT NULL,
  volume_price_per_gb_month_gross DECIMAL(20, 10) DEFAULT NULL,
  server_backup_percentage DECIMAL(10, 4) DEFAULT NULL,
  updated DATETIME NOT NULL,
  PRIMARY KEY (project_id),
  CONSTRAINT fk_hcloud_pricing_project FOREIGN KEY (project_id)
    REFERENCES hcloud_project (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_pricing_server_type (
  project_id INT UNSIGNED NOT NULL,
  server_type_id BIGINT UNSIGNED NOT NULL,
  location_name VARCHAR(128) NOT NULL,
  name VARCHAR(128) DEFAULT NULL,
  price_hourly_net DECIMAL(20, 10) DEFAULT NULL,
  price_hourly_gross DECIMAL(20, 10) DEFAULT NULL,
  price_monthly_net DECIMAL(20, 10) DEFAULT NULL,
  price_monthly_gross DECIMAL(20, 10) DEFAULT NULL,
  included_traffic BIGINT UNSIGNED DEFAULT NULL,
  price_per_tb_traffic_net DECIMAL(20, 10) DEFAULT NULL,
  price_per_tb_traffic_gross DECIMAL(20, 10) DEFAULT NULL,
  PRIMARY KEY (project_id, server_type_id, location_name),
  CONSTRAINT fk_hcloud_pricing_server_type_project FOREIGN KEY (project_id)
    REFERENCES hcloud_project (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_pricing_load_balancer_type (
  project_id INT UNSIGNED NOT NULL,
  load_balancer_type_id BIGINT UNSIGNED NOT NULL,
  location_name VARCHAR(128) NOT NULL,
  name VARCHAR(128) DEFAULT NULL,
  price_hourly_net DECIMAL(20, 10) DEFAULT NULL,
  price_hourly_gross DECIMAL(20, 10) DEFAULT NULL,
  price_monthly_net DECIMAL(20, 10) DEFAULT NULL,
  price_monthly_gross DECIMAL(20, 10) DEFAULT NULL,
  included_traffic BIGINT UNSIGNED DEFAULT NULL,
  price_per_tb_traffic_net DECIMAL(20, 10) DEFAULT NULL,
  price_per_tb_traffic_gross DECIMAL(20, 10) DEFAULT NULL,
  PRIMARY KEY (project_id, load_balancer_type_id, location_name),
  CONSTRAINT fk_hcloud_pricing_lb_type_project FOREIGN KEY (project_id)
    REFERENCES hcloud_project (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_pricing_primary_ip (
  project_id INT UNSIGNED NOT NULL,
  type VARCHAR(16) NOT NULL,
  location_name VARCHAR(128) NOT NULL,
  price_hourly_net DECIMAL(20, 10) DEFAULT NULL,
  price_hourly_gross DECIMAL(20, 10) DEFAULT NULL,
  price_monthly_net DECIMAL(20, 10) DEFAULT NULL,
  price_monthly_gross DECIMAL(20, 10) DEFAULT NULL,
  PRIMARY KEY (project_id, type, location_name),
  CONSTRAINT fk_hcloud_pricing_primary_ip_project FOREIGN KEY (project_id)
    REFERENCES hcloud_project (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_pricing_floating_ip (
  project_id INT UNSIGNED NOT NULL,
  type VARCHAR(16) NOT NULL,
  location_name VARCHAR(128) NOT NULL,
  price_monthly_net DECIMAL(20, 10) DEFAULT NULL,
  price_monthly_gross DECIMAL(20, 10) DEFAULT NULL,
  PRIMARY KEY (project_id, type, location_name),
  CONSTRAINT fk_hcloud_pricing_floating_ip_project FOREIGN KEY (project_id)
    REFERENCES hcloud_project (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_pricing_storage_box_type (
  project_id INT UNSIGNED NOT NULL,
  storage_box_type_id BIGINT UNSIGNED NOT NULL,
  location_name VARCHAR(128) NOT NULL,
  price_hourly_net DECIMAL(20, 10) DEFAULT NULL,
  price_hourly_gross DECIMAL(20, 10) DEFAULT NULL,
  price_monthly_net DECIMAL(20, 10) DEFAULT NULL,
  price_monthly_gross DECIMAL(20, 10) DEFAULT NULL,
  setup_fee_net DECIMAL(20, 10) DEFAULT NULL,
  setup_fee_gross DECIMAL(20, 10) DEFAULT NULL,
  PRIMARY KEY (project_id, storage_box_type_id, location_name),
  CONSTRAINT fk_hcloud_pricing_storage_box_type_project FOREIGN KEY (project_id)
    REFERENCES hcloud_project (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hcloud_object_storage_bucket (
  project_id INT UNSIGNED NOT NULL,
  id VARCHAR(320) NOT NULL,
  location VARCHAR(64) NOT NULL,
  name VARCHAR(255) NOT NULL,
  created DATETIME DEFAULT NULL,
  object_count BIGINT UNSIGNED DEFAULT NULL,
  size BIGINT UNSIGNED DEFAULT NULL,
  usage_complete TINYINT UNSIGNED NOT NULL DEFAULT 0,
  usage_scanned DATETIME DEFAULT NULL,
  PRIMARY KEY (project_id, id),
  KEY idx_hcloud_object_storage_bucket_name (project_id, name),
  KEY idx_hcloud_object_storage_bucket_location (project_id, location),
  CONSTRAINT fk_hcloud_object_storage_bucket_project FOREIGN KEY (project_id)
    REFERENCES hcloud_project (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO hcloud_schema (version, timestamp, success)
  VALUES ('0.2.0', UNIX_TIMESTAMP() * 1000, 'y');
