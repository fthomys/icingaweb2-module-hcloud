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
