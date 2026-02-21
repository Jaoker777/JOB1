-- Nournia Shop — Gaming Gear Store
-- Database Schema + Seed Data

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE DATABASE IF NOT EXISTS nournia_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nournia_shop;

-- Users table (Authentication)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    user_id INT,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
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

-- Seed default users (password = 'password' for both, hashed with bcrypt)
INSERT INTO users (username, email, password_hash, role) VALUES
('admin', 'admin@nournia.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('user', 'user@nournia.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

-- Seed 9 categories
INSERT INTO categories (name, icon) VALUES
('เมาส์', '🖱'),
('คีย์บอร์ด', '⌨'),
('หูฟัง', '🎧'),
('จอ Monitor', '🖥'),
('CPU', '🧠'),
('การ์ดจอ', '🎮'),
('เมนบอร์ด', '🔌'),
('RAM', '💾'),
('อุปกรณ์เกมมิ่งอื่น ๆ', '🎮');

-- Seed sample products (at least 1 per category)
INSERT INTO products (category_id, name, description, price, stock_quantity, image_url) VALUES
-- 1: เมาส์
(1, 'Logitech G Pro X Superlight', 'เมาส์เกมมิ่งไร้สาย น้ำหนักเบาเพียง 63g. เซ็นเซอร์ 25K DPI Hero 25K.', 3990.00, 25, 'assets/images/products/mouse.png'),
(1, 'Razer DeathAdder V3', 'เมาส์เกมมิ่งสายโปร เซ็นเซอร์ Focus Pro 30K DPI. น้ำหนัก 59g.', 2790.00, 18, 'assets/images/products/mouse.png'),
-- 2: คีย์บอร์ด
(2, 'Corsair K100 RGB Mechanical', 'คีย์บอร์ดเกมมิ่ง Cherry MX Speed สวิตช์ OPX ไฟ RGB Per-Key.', 7490.00, 10, 'assets/images/products/keyboard.png'),
(2, 'SteelSeries Apex Pro TKL', 'คีย์บอร์ดเชิงกล สวิตช์ OmniPoint 2.0 ปรับ Actuation Point ได้.', 6990.00, 7, 'assets/images/products/keyboard.png'),
-- 3: หูฟัง
(3, 'SteelSeries Arctis Nova Pro', 'หูฟังเกมมิ่งไร้สาย ระบบ ANC Hi-Res Audio 360° Spatial.', 11900.00, 5, 'assets/images/products/headset.png'),
(3, 'HyperX Cloud III Wireless', 'หูฟังเกมมิ่งไร้สาย DTS Headphone:X แบตนาน 120 ชม.', 4990.00, 14, 'assets/images/products/headset.png'),
-- 4: จอ Monitor
(4, 'LG 27GP850-B 27" QHD', 'จอเกมมิ่ง 165Hz Nano IPS 1ms GTG. HDR400.', 12900.00, 3, 'assets/images/products/monitor.png'),
(4, 'Samsung Odyssey G7 32"', 'จอโค้ง 1000R QHD 240Hz QLED HDR600.', 16900.00, 4, 'assets/images/products/monitor.png'),
-- 5: CPU
(5, 'AMD Ryzen 7 7800X3D', 'ซีพียูเกมมิ่ง 8 คอร์/16 เธรด 3D V-Cache 96MB.', 13500.00, 12, 'assets/images/products/cpu.png'),
(5, 'Intel Core i7-14700K', 'ซีพียู 20 คอร์ (8P+12E) Turbo Boost สูงสุด 5.6GHz.', 14900.00, 9, 'assets/images/products/cpu.png'),
-- 6: การ์ดจอ
(6, 'NVIDIA RTX 4070 Ti', 'การ์ดจอเกมมิ่ง 12GB GDDR6X DLSS 3.0 Ray Tracing.', 22900.00, 8, 'assets/images/products/gpu.png'),
(6, 'AMD Radeon RX 7800 XT', 'การ์ดจอ 16GB GDDR6 FSR 3 สำหรับเกมมิ่ง 1440p.', 17900.00, 6, 'assets/images/products/gpu.png'),
-- 7: เมนบอร์ด
(7, 'GIGABYTE Z790 AORUS Master', 'เมนบอร์ด LGA 1700 DDR5 PCIe 5.0 WiFi 7 20+1+2 Phase VRM.', 18500.00, 5, 'assets/images/products/motherboard.png'),
(7, 'ASUS ROG Strix B650E-F', 'เมนบอร์ด AM5 DDR5 PCIe 5.0 WiFi 6E 16+2 Phase VRM.', 9900.00, 8, 'assets/images/products/motherboard.png'),
-- 8: RAM
(8, 'Corsair Vengeance DDR5 32GB', 'แรม DDR5-5600 Dual Channel CL36 สำหรับเกมมิ่ง.', 4290.00, 20, 'assets/images/products/ram.png'),
(8, 'G.SKILL Trident Z5 RGB 32GB', 'แรม DDR5-6000 CL30 RGB Dual Channel Intel XMP 3.0.', 5990.00, 11, 'assets/images/products/ram.png'),
-- 9: อุปกรณ์เกมมิ่งอื่น ๆ
(9, 'Secretlab TITAN Evo Gaming Chair', 'เก้าอี้เกมมิ่งระดับโปร Pebble+ หนัง Neo Hybrid รองรับถึง 130kg.', 15900.00, 4, 'assets/images/products/gaming_chair.png'),
(9, 'Samsung 990 Pro 2TB NVMe', 'SSD PCIe 4.0 NVMe อ่าน 7450 MB/s เขียน 6900 MB/s.', 6490.00, 15, 'assets/images/products/ssd.png');
