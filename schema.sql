-- Gaming Store Inventory System
-- Database Schema + Seed Data

CREATE DATABASE IF NOT EXISTS gaming_store;
USE gaming_store;

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    stock_quantity INT NOT NULL DEFAULT 0,
    image_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sales table
CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sale items table
CREATE TABLE IF NOT EXISTS sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed categories
INSERT INTO categories (name) VALUES
('Graphics Cards'),
('Processors'),
('RAM'),
('Storage'),
('Monitors'),
('Peripherals');

-- Seed sample products
INSERT INTO products (category_id, name, description, price, stock_quantity, image_url) VALUES
(1, 'NVIDIA RTX 4070 Ti', 'High-performance GPU for gaming and content creation. 12GB GDDR6X.', 22900.00, 8, NULL),
(2, 'AMD Ryzen 7 7800X3D', '8-core gaming processor with 3D V-Cache technology.', 13500.00, 12, NULL),
(3, 'Corsair Vengeance DDR5 32GB', 'DDR5-5600 dual-channel RAM kit. CL36 latency.', 4290.00, 20, NULL),
(4, 'Samsung 990 Pro 2TB NVMe', 'PCIe 4.0 NVMe SSD with 7450 MB/s read speed.', 6490.00, 15, NULL),
(5, 'LG 27GP850-B 27" QHD', '165Hz Nano IPS gaming monitor. 1ms GTG response.', 12900.00, 3, NULL),
(6, 'Logitech G Pro X Superlight', 'Ultra-lightweight wireless gaming mouse. 25K DPI sensor.', 3990.00, 25, NULL);
