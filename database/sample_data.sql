-- Sample data for Rhen Matthew Store IMS
-- Run this after importing database/schema.sql

USE rhen_matthew_store;

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

ALTER TABLE products
    ADD COLUMN IF NOT EXISTS pieces_per_case INT UNSIGNED NOT NULL DEFAULT 24 AFTER price;

ALTER TABLE sales_items
    ADD COLUMN IF NOT EXISTS ordered_qty INT UNSIGNED NOT NULL DEFAULT 1 AFTER quantity,
    ADD COLUMN IF NOT EXISTS order_unit ENUM('piece', 'case', 'half_case', 'quarter_case') NOT NULL DEFAULT 'piece' AFTER ordered_qty,
    ADD COLUMN IF NOT EXISTS base_units INT UNSIGNED NOT NULL DEFAULT 0 AFTER order_unit;

START TRANSACTION;

-- NOTE:
-- TRUNCATE cannot be used on parent tables referenced by foreign keys in MySQL,
-- even when FOREIGN_KEY_CHECKS is disabled. Use DELETE instead.
SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM sales_items;
DELETE FROM inventory;
DELETE FROM sales;
DELETE FROM deliveries;
DELETE FROM drivers;
DELETE FROM products;
SET FOREIGN_KEY_CHECKS = 1;

ALTER TABLE sales_items AUTO_INCREMENT = 1;
ALTER TABLE inventory AUTO_INCREMENT = 1;
ALTER TABLE sales AUTO_INCREMENT = 1;
ALTER TABLE deliveries AUTO_INCREMENT = 1;
ALTER TABLE drivers AUTO_INCREMENT = 1;
ALTER TABLE products AUTO_INCREMENT = 1;

-- Keep admin from schema.sql and add two sample staff users.
-- Password hash below corresponds to: admin12345
INSERT INTO users (name, email, password, role, created_at)
VALUES
    ('System Administrator', 'admin@store.local', '$2y$10$QR5UN.gaxXp199zzgKDimeu1PTiNOfDkt2C9AYUKHx.VV5YWE8HaS', 'admin', NOW()),
    ('Rhen Matthew', 'rhen@store.local', '$2y$10$QR5UN.gaxXp199zzgKDimeu1PTiNOfDkt2C9AYUKHx.VV5YWE8HaS', 'staff', NOW()),
    ('Store Staff 1', 'staff1@store.local', '$2y$10$QR5UN.gaxXp199zzgKDimeu1PTiNOfDkt2C9AYUKHx.VV5YWE8HaS', 'staff', NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    role = VALUES(role);

INSERT INTO drivers (id, full_name, phone, license_no, vehicle_assigned, status, hired_date, notes, created_at, updated_at)
VALUES
    (1, 'Ramon Villanueva', '09171234567', 'N04-12-123456', 'Truck #1', 'active', '2024-01-10', 'Primary route: San Jose and Lipa', NOW() - INTERVAL 200 DAY, NOW() - INTERVAL 2 DAY),
    (2, 'Joel Fernandez', '09181239876', 'N03-11-654321', 'Truck #2', 'active', '2024-03-22', 'Handles long-haul delivery schedules', NOW() - INTERVAL 150 DAY, NOW() - INTERVAL 1 DAY),
    (3, 'Dennis Ramos', '09192345678', 'N01-09-745812', 'Van #1', 'on_leave', '2024-07-18', 'Temporary medical leave', NOW() - INTERVAL 100 DAY, NOW() - INTERVAL 8 DAY),
    (4, 'Mark de Leon', '09201234567', 'N09-14-889900', 'Reserve Unit', 'inactive', '2023-11-05', 'Previously assigned to weekend dispatch', NOW() - INTERVAL 260 DAY, NOW() - INTERVAL 20 DAY);

INSERT INTO products (id, name, category, size, price, stock_quantity, created_at, updated_at)
VALUES
    (1, 'Coca-Cola Mismo', 'Softdrinks', '290ml', 18.00, 145, NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 1 HOUR),
    (2, 'Sprite Mismo', 'Softdrinks', '290ml', 18.00, 132, NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 1 HOUR),
    (3, 'Royal Tru-Orange Mismo', 'Softdrinks', '290ml', 18.00, 118, NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 1 HOUR),
    (4, 'Pepsi Regular', 'Softdrinks', '330ml', 17.00, 96, NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 1 HOUR),
    (5, 'Mountain Dew', 'Softdrinks', '330ml', 20.00, 88, NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 1 HOUR),
    (6, 'Coke 1.5L', 'Softdrinks', '1500ml', 72.00, 64, NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 1 HOUR),
    (7, 'Sprite 1.5L', 'Softdrinks', '1500ml', 72.00, 58, NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 1 HOUR),
    (8, 'Royal 1.5L', 'Softdrinks', '1500ml', 72.00, 52, NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 1 HOUR),
    (9, 'Wilkins Distilled Water', 'Water', '500ml', 15.00, 180, NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 1 HOUR),
    (10, 'Cobra Energy Drink', 'Energy Drink', '350ml', 25.00, 74, NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 1 HOUR);

INSERT INTO sales (id, customer_name, total_amount, payment_type, status, created_at)
VALUES
    (1, 'Maria''s Sari-Sari Store', 486.00, 'cash', 'paid', NOW() - INTERVAL 2 DAY),
    (2, 'Barangay Canteen', 664.00, 'utang', 'pending', NOW() - INTERVAL 1 DAY),
    (3, 'J&L Mini Mart', 562.00, 'cash', 'paid', NOW() - INTERVAL 10 HOUR),
    (4, 'Purok 5 Variety Store', 513.00, 'utang', 'pending', NOW() - INTERVAL 6 HOUR),
    (5, 'Lakeside Eatery', 605.00, 'cash', 'paid', NOW() - INTERVAL 3 HOUR),
    (6, 'ABC Tindahan', 388.00, 'cash', 'paid', NOW() - INTERVAL 1 HOUR);

INSERT INTO sales_items (sale_id, product_id, quantity, price, subtotal)
VALUES
    (1, 1, 12, 18.00, 216.00),
    (1, 2, 10, 18.00, 180.00),
    (1, 9, 6, 15.00, 90.00),

    (2, 6, 4, 72.00, 288.00),
    (2, 7, 3, 72.00, 216.00),
    (2, 5, 8, 20.00, 160.00),

    (3, 3, 12, 18.00, 216.00),
    (3, 4, 8, 17.00, 136.00),
    (3, 10, 6, 25.00, 150.00),
    (3, 9, 4, 15.00, 60.00),

    (4, 1, 8, 18.00, 144.00),
    (4, 2, 8, 18.00, 144.00),
    (4, 5, 6, 20.00, 120.00),
    (4, 10, 3, 25.00, 75.00),
    (4, 9, 2, 15.00, 30.00),

    (5, 6, 3, 72.00, 216.00),
    (5, 7, 2, 72.00, 144.00),
    (5, 8, 2, 72.00, 144.00),
    (5, 4, 3, 17.00, 51.00),
    (5, 10, 2, 25.00, 50.00),

    (6, 1, 5, 18.00, 90.00),
    (6, 2, 5, 18.00, 90.00),
    (6, 3, 5, 18.00, 90.00),
    (6, 4, 4, 17.00, 68.00),
    (6, 10, 2, 25.00, 50.00);

UPDATE sales_items
SET ordered_qty = quantity,
    order_unit = 'piece',
    base_units = quantity;

-- Inventory ledger entries: stock-in batch plus aggregated sales-out movement.
INSERT INTO inventory (product_id, quantity, type, notes, created_at)
VALUES
    (1, 170, 'in', 'Initial stock load', NOW() - INTERVAL 5 DAY),
    (2, 155, 'in', 'Initial stock load', NOW() - INTERVAL 5 DAY),
    (3, 135, 'in', 'Initial stock load', NOW() - INTERVAL 5 DAY),
    (4, 111, 'in', 'Initial stock load', NOW() - INTERVAL 5 DAY),
    (5, 102, 'in', 'Initial stock load', NOW() - INTERVAL 5 DAY),
    (6, 71, 'in', 'Initial stock load', NOW() - INTERVAL 5 DAY),
    (7, 63, 'in', 'Initial stock load', NOW() - INTERVAL 5 DAY),
    (8, 54, 'in', 'Initial stock load', NOW() - INTERVAL 5 DAY),
    (9, 192, 'in', 'Initial stock load', NOW() - INTERVAL 5 DAY),
    (10, 87, 'in', 'Initial stock load', NOW() - INTERVAL 5 DAY),

    (1, -25, 'out', 'Sales period movement', NOW() - INTERVAL 1 DAY),
    (2, -23, 'out', 'Sales period movement', NOW() - INTERVAL 1 DAY),
    (3, -17, 'out', 'Sales period movement', NOW() - INTERVAL 1 DAY),
    (4, -15, 'out', 'Sales period movement', NOW() - INTERVAL 1 DAY),
    (5, -14, 'out', 'Sales period movement', NOW() - INTERVAL 1 DAY),
    (6, -7, 'out', 'Sales period movement', NOW() - INTERVAL 1 DAY),
    (7, -5, 'out', 'Sales period movement', NOW() - INTERVAL 1 DAY),
    (8, -2, 'out', 'Sales period movement', NOW() - INTERVAL 1 DAY),
    (9, -12, 'out', 'Sales period movement', NOW() - INTERVAL 1 DAY),
    (10, -13, 'out', 'Sales period movement', NOW() - INTERVAL 1 DAY);

INSERT INTO deliveries (reference_no, customer_name, address, scheduled_date, status, delivered_at, created_at)
VALUES
    ('DLV-2026-001', 'Maria''s Sari-Sari Store', 'Purok 1, San Jose, Batangas', CURDATE() - INTERVAL 1 DAY, 'delivered', NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 2 DAY),
    ('DLV-2026-002', 'Barangay Canteen', 'Barangay Hall Compound, Batangas', CURDATE(), 'in_transit', NULL, NOW() - INTERVAL 1 DAY),
    ('DLV-2026-003', 'J&L Mini Mart', 'National Road, Lipa City', CURDATE() + INTERVAL 1 DAY, 'pending', NULL, NOW() - INTERVAL 8 HOUR),
    ('DLV-2026-004', 'Purok 5 Variety Store', 'Purok 5, Ibaan, Batangas', CURDATE() + INTERVAL 2 DAY, 'pending', NULL, NOW() - INTERVAL 6 HOUR),
    ('DLV-2026-005', 'Lakeside Eatery', 'Lakeside Street, Tanauan', CURDATE() - INTERVAL 3 DAY, 'delivered', NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 4 DAY),
    ('DLV-2026-006', 'ABC Tindahan', 'Market Area, Sto. Tomas', CURDATE() - INTERVAL 2 DAY, 'cancelled', NULL, NOW() - INTERVAL 2 DAY);

COMMIT;
