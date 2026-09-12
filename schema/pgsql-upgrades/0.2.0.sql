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

CREATE INDEX idx_hcloud_object_storage_bucket_name ON hcloud_object_storage_bucket (project_id, name);
CREATE INDEX idx_hcloud_object_storage_bucket_location ON hcloud_object_storage_bucket (project_id, location);

INSERT INTO hcloud_schema (version, timestamp, success)
  VALUES ('0.2.0', EXTRACT(EPOCH FROM NOW()) * 1000, 'y');
