
CREATE DATABASE IF NOT EXISTS ewaste_portal;
USE ewaste_portal;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(120) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(15),
    role ENUM('customer', 'admin', 'collector')
        DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- E-Waste Categories Table
CREATE TABLE IF NOT EXISTS ewaste_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) UNIQUE NOT NULL
) ENGINE=InnoDB;

-- Insert Default Categories
INSERT IGNORE INTO ewaste_categories (category_name)
VALUES
('Mobile Phone'),
('Laptop'),
('Charger'),
('Battery'),
('Other');

-- Pickup Requests Table
CREATE TABLE IF NOT EXISTS pickup_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    category_id INT NOT NULL,
    item_description TEXT,
    quantity INT NOT NULL DEFAULT 1,
    item_condition VARCHAR(50),
    pickup_address TEXT NOT NULL,
    preferred_date DATE,
    status ENUM(
        'Pending',
        'Approved',
        'Rejected',
        'Scheduled',
        'Collected',
        'Recycled'
    ) DEFAULT 'Pending',
    assigned_collector_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (customer_id)
        REFERENCES users(id),

    FOREIGN KEY (category_id)
        REFERENCES ewaste_categories(id),

    FOREIGN KEY (assigned_collector_id)
        REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB;
