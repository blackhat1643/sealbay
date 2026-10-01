-- SealBay Australia — MySQL / MariaDB schema
-- Generated from schema_statements() in includes/db.php. Money is stored in cents (AUD, GST inclusive).
--
-- Normally you do NOT need to import this file: open /install/ in the browser and the
-- installer creates these tables, loads the sample catalogue and creates the admin user.
-- Use this file only if you prefer to create the tables by hand in phpMyAdmin.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    last_login_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    username VARCHAR(60) NOT NULL,
    attempted_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(80) NOT NULL PRIMARY KEY,
    setting_value TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(120) NOT NULL UNIQUE,
    name VARCHAR(160) NOT NULL,
    headline VARCHAR(200) NULL,
    summary TEXT NULL,
    description TEXT NULL,
    illustration VARCHAR(40) NULL,
    image VARCHAR(190) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    slug VARCHAR(160) NOT NULL UNIQUE,
    sku VARCHAR(60) NOT NULL UNIQUE,
    name VARCHAR(200) NOT NULL,
    seal_type VARCHAR(20) NOT NULL DEFAULT 'rotary',
    style VARCHAR(120) NULL,
    material VARCHAR(60) NULL,
    inner_diameter DECIMAL(7,2) NULL,
    outer_diameter DECIMAL(7,2) NULL,
    width DECIMAL(7,2) NULL,
    temp_range VARCHAR(80) NULL,
    price_cents INT NOT NULL DEFAULT 0,
    stock_qty INT NOT NULL DEFAULT 0,
    allow_backorder TINYINT(1) NOT NULL DEFAULT 1,
    summary TEXT NULL,
    fitment TEXT NULL,
    kit_contents TEXT NULL,
    illustration VARCHAR(40) NULL,
    image VARCHAR(190) NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    updated_at DATETIME NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) NOT NULL UNIQUE,
    access_token VARCHAR(64) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'awaiting_payment',
    payment_method VARCHAR(20) NOT NULL,
    payment_ref VARCHAR(190) NULL,
    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,
    company VARCHAR(160) NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    address_line1 VARCHAR(160) NOT NULL,
    address_line2 VARCHAR(160) NULL,
    suburb VARCHAR(100) NOT NULL,
    state VARCHAR(3) NOT NULL,
    postcode VARCHAR(4) NOT NULL,
    shipping_method VARCHAR(20) NOT NULL,
    shipping_cents INT NOT NULL DEFAULT 0,
    subtotal_cents INT NOT NULL DEFAULT 0,
    total_cents INT NOT NULL DEFAULT 0,
    gst_cents INT NOT NULL DEFAULT 0,
    customer_notes TEXT NULL,
    admin_notes TEXT NULL,
    tracking_number VARCHAR(80) NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL,
    paid_at DATETIME NULL,
    shipped_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NULL,
    sku VARCHAR(60) NOT NULL,
    name VARCHAR(200) NOT NULL,
    unit_price_cents INT NOT NULL,
    quantity INT NOT NULL,
    line_total_cents INT NOT NULL,
    backorder_qty INT NOT NULL DEFAULT 0,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS articles (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(160) NOT NULL UNIQUE,
    title VARCHAR(200) NOT NULL,
    topic VARCHAR(80) NULL,
    excerpt TEXT NULL,
    body MEDIUMTEXT NULL,
    faqs TEXT NULL,
    reading_minutes INT NOT NULL DEFAULT 5,
    meta_title VARCHAR(200) NULL,
    meta_description VARCHAR(320) NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    published_at DATETIME NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS enquiries (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    reference VARCHAR(20) NOT NULL,
    type VARCHAR(20) NOT NULL DEFAULT 'quote',
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(40) NULL,
    postcode VARCHAR(10) NULL,
    seal_type VARCHAR(120) NULL,
    inner_diameter VARCHAR(60) NULL,
    outer_diameter VARCHAR(60) NULL,
    width VARCHAR(60) NULL,
    quantity VARCHAR(60) NULL,
    machine VARCHAR(250) NULL,
    message TEXT NULL,
    attachment_path VARCHAR(190) NULL,
    attachment_name VARCHAR(190) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'new',
    admin_notes TEXT NULL,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) NOT NULL PRIMARY KEY,
    data MEDIUMTEXT NOT NULL,
    expires_at INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS uploads (
    name VARCHAR(80) NOT NULL PRIMARY KEY,
    kind VARCHAR(20) NOT NULL,
    mime VARCHAR(80) NOT NULL,
    width INT NOT NULL DEFAULT 0,
    height INT NOT NULL DEFAULT 0,
    data MEDIUMBLOB NOT NULL,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_hits (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    bucket VARCHAR(64) NOT NULL,
    hit_at INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_sessions_expiry ON sessions (expires_at);

CREATE INDEX idx_rate_hits ON rate_hits (bucket, hit_at);

CREATE INDEX idx_enquiries_status ON enquiries (status, created_at);

CREATE INDEX idx_login_attempts ON login_attempts (ip_address, attempted_at);

CREATE INDEX idx_products_category ON products (category_id, sort_order);

CREATE INDEX idx_products_size ON products (inner_diameter, outer_diameter, width);

CREATE INDEX idx_orders_status ON orders (status, created_at);

CREATE INDEX idx_order_items_order ON order_items (order_id);

