-- Run once on an existing Balangkare Sur Trading database to enable profile address and photos.
USE balangkare_market;

ALTER TABLE users
  ADD COLUMN address VARCHAR(255) NULL AFTER phone,
  ADD COLUMN profile_image_path VARCHAR(255) NULL AFTER address;
