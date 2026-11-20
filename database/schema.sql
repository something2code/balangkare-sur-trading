CREATE DATABASE IF NOT EXISTS balangkare_market CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE balangkare_market;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  phone VARCHAR(30) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('farmer','buyer') NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  farmer_id INT UNSIGNED NOT NULL,
  name VARCHAR(140) NOT NULL,
  category ENUM('Rice','Grains','Seeds','Tools','Fertilizer','Vegetables','Other') NOT NULL DEFAULT 'Other',
  description TEXT NULL,
  image_path VARCHAR(255) NULL,
  price DECIMAL(10,2) NOT NULL,
  unit VARCHAR(30) NOT NULL DEFAULT 'kg',
  quantity DECIMAL(10,2) NOT NULL,
  location VARCHAR(120) NOT NULL DEFAULT 'Balangkare, Nueva Ecija',
  status ENUM('active','paused','sold_out') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_products_farmer FOREIGN KEY (farmer_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_products_status_category (status, category),
  INDEX idx_products_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  buyer_id INT UNSIGNED NOT NULL,
  farmer_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  quantity DECIMAL(10,2) NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  fulfillment ENUM('pickup','delivery') NOT NULL DEFAULT 'pickup',
  payment_method ENUM('cash','bank_transfer','ewallet','to_agree') NOT NULL DEFAULT 'to_agree',
  status ENUM('new','accepted','ready','completed','rejected','cancelled') NOT NULL DEFAULT 'new',
  buyer_note VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_buyer FOREIGN KEY (buyer_id) REFERENCES users(id),
  CONSTRAINT fk_orders_farmer FOREIGN KEY (farmer_id) REFERENCES users(id),
  CONSTRAINT fk_orders_product FOREIGN KEY (product_id) REFERENCES products(id),
  INDEX idx_orders_buyer (buyer_id, created_at),
  INDEX idx_orders_farmer (farmer_id, created_at)
) ENGINE=InnoDB;

-- Optional starter products are added after creating a farmer account, from the Farmer Dashboard.
