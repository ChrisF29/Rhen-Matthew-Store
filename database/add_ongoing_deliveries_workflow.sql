USE rhen_matthew_store;

CREATE TABLE IF NOT EXISTS ongoing_deliveries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference_no VARCHAR(80) NOT NULL,
    customer_name VARCHAR(140) NOT NULL,
    payment_type ENUM('cash', 'utang') NOT NULL DEFAULT 'cash',
    scheduled_date DATE NOT NULL,
    status ENUM('pending_dispatch', 'in_transit', 'completed', 'cancelled') NOT NULL DEFAULT 'pending_dispatch',
    notes VARCHAR(255) NULL,
    sale_id INT UNSIGNED NULL,
    dispatched_at DATETIME NULL,
    completed_at DATETIME NULL,
    cancelled_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_ongoing_deliveries_reference (reference_no),
    INDEX idx_ongoing_deliveries_status_date (status, scheduled_date),
    CONSTRAINT fk_ongoing_deliveries_sale
        FOREIGN KEY (sale_id)
        REFERENCES sales (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ongoing_delivery_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ongoing_delivery_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    ordered_qty INT UNSIGNED NOT NULL,
    order_unit ENUM('piece', 'case', 'half_case', 'quarter_case') NOT NULL DEFAULT 'piece',
    loaded_units INT UNSIGNED NOT NULL,
    delivered_qty INT UNSIGNED NOT NULL DEFAULT 0,
    delivered_units INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ongoing_delivery_items_order
        FOREIGN KEY (ongoing_delivery_id)
        REFERENCES ongoing_deliveries (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_ongoing_delivery_items_product
        FOREIGN KEY (product_id)
        REFERENCES products (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    INDEX idx_ongoing_delivery_items_order (ongoing_delivery_id),
    INDEX idx_ongoing_delivery_items_product (product_id)
) ENGINE=InnoDB;
