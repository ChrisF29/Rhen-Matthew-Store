CREATE DATABASE IF NOT EXISTS rhen_matthew_store
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE rhen_matthew_store;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(80) NOT NULL,
    size VARCHAR(60) NOT NULL,
    price DECIMAL(12, 2) NOT NULL DEFAULT 0,
    pieces_per_case INT UNSIGNED NOT NULL DEFAULT 24,
    stock_quantity INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_products_category (category),
    INDEX idx_products_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inventory (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    quantity INT NOT NULL,
    type ENUM('in', 'out', 'adjustment') NOT NULL,
    notes VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_inventory_product
        FOREIGN KEY (product_id)
        REFERENCES products (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    INDEX idx_inventory_product_created (product_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sales (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(140) NOT NULL,
    total_amount DECIMAL(12, 2) NOT NULL DEFAULT 0,
    payment_type ENUM('cash', 'utang') NOT NULL DEFAULT 'cash',
    status ENUM('pending', 'paid') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sales_created (created_at),
    INDEX idx_sales_payment (payment_type, status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sales_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    ordered_qty INT UNSIGNED NOT NULL DEFAULT 1,
    order_unit ENUM('piece', 'case', 'half_case', 'quarter_case') NOT NULL DEFAULT 'piece',
    base_units INT UNSIGNED NOT NULL DEFAULT 0,
    price DECIMAL(12, 2) NOT NULL,
    subtotal DECIMAL(12, 2) NOT NULL,
    CONSTRAINT fk_sales_items_sale
        FOREIGN KEY (sale_id)
        REFERENCES sales (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_sales_items_product
        FOREIGN KEY (product_id)
        REFERENCES products (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    INDEX idx_sales_items_sale (sale_id),
    INDEX idx_sales_items_product (product_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS drivers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(140) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    license_no VARCHAR(80) NOT NULL,
    vehicle_assigned VARCHAR(140) NULL,
    status ENUM('active', 'on_leave', 'inactive') NOT NULL DEFAULT 'active',
    hired_date DATE NOT NULL,
    notes VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_drivers_license (license_no),
    INDEX idx_drivers_status_hired (status, hired_date),
    INDEX idx_drivers_name (full_name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS deliveries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference_no VARCHAR(80) NOT NULL,
    customer_name VARCHAR(140) NOT NULL,
    address VARCHAR(255) NOT NULL,
    scheduled_date DATE NOT NULL,
    status ENUM('pending', 'in_transit', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    delivered_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_deliveries_reference (reference_no),
    INDEX idx_deliveries_status_date (status, scheduled_date)
) ENGINE=InnoDB;

INSERT INTO users (name, email, password, role, created_at)
VALUES (
    'System Administrator',
    'admin@store.local',
    '$2y$10$QR5UN.gaxXp199zzgKDimeu1PTiNOfDkt2C9AYUKHx.VV5YWE8HaS',
    'admin',
    NOW()
)
ON DUPLICATE KEY UPDATE email = email;
