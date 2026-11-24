USE balangkare_market;

CREATE TABLE IF NOT EXISTS request_rate_limits (
  bucket_hash CHAR(64) NOT NULL PRIMARY KEY,
  window_started_at DATETIME NOT NULL,
  request_count INT UNSIGNED NOT NULL DEFAULT 0,
  INDEX idx_request_rate_limits_window (window_started_at)
) ENGINE=InnoDB;
