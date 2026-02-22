-- Nournia Shop — Coupon System Migration
-- Run this AFTER schema.sql

USE nournia_shop;

-- Coupons table
CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    discount_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    min_order_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    max_uses INT NOT NULL DEFAULT 1,
    used_count INT NOT NULL DEFAULT 0,
    expires_at DATE NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Coupon usage tracking (one-time per user)
CREATE TABLE IF NOT EXISTS coupon_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT NOT NULL,
    user_id INT NOT NULL,
    sale_id INT,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE SET NULL,
    UNIQUE KEY unique_coupon_user (coupon_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add coupon_id and discount columns to sales table
ALTER TABLE sales ADD COLUMN coupon_id INT DEFAULT NULL AFTER user_id;
ALTER TABLE sales ADD COLUMN discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER total_amount;

-- Seed 10 coupon codes (discounts 500–5000 THB)
INSERT INTO coupons (code, discount_amount, min_order_amount, max_uses, expires_at) VALUES
('NOURNIA500',   500.00,  1000.00, 50, '2026-12-31'),
('GAME1000',    1000.00,  2000.00, 30, '2026-12-31'),
('GEAR1500',    1500.00,  3000.00, 20, '2026-12-31'),
('PLAY2000',    2000.00,  4000.00, 15, '2026-12-31'),
('PRO2500',     2500.00,  5000.00, 10, '2026-12-31'),
('MEGA3000',    3000.00,  6000.00, 10, '2026-09-30'),
('SUPER3500',   3500.00,  7000.00,  8, '2026-09-30'),
('ULTRA4000',   4000.00,  8000.00,  5, '2026-06-30'),
('LEGEND4500',  4500.00,  9000.00,  5, '2026-06-30'),
('VIP5000',     5000.00, 10000.00,  3, '2026-06-30');
