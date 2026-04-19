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
