-- ==============================================================================
-- CV Asianindo E-Commerce Database Schema
-- Website: asianindomachine.com
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

-- ------------------------------------------------------------------------------
-- 1. Table: customers
-- Menyimpan data akun dan profil pelanggan
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `phone` VARCHAR(20) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `address` TEXT NULL,
    `city` VARCHAR(100) NULL,
    `province` VARCHAR(100) NULL,
    `postal_code` VARCHAR(10) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_customers_email` (`email`),
    INDEX `idx_customers_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. Table: cart_items
-- Menyimpan produk dalam keranjang belanja pelanggan yang sedang aktif
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `cart_items`;
CREATE TABLE `cart_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT NOT NULL,
    `product_id` VARCHAR(50) NOT NULL,
    `product_name` VARCHAR(255) NOT NULL,
    `product_image` VARCHAR(500) NULL,
    `product_price` BIGINT NOT NULL,
    `quantity` INT DEFAULT 1,
    `weight_grams` INT DEFAULT 0,
    `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_customer_product` (`customer_id`, `product_id`),
    INDEX `idx_cart_customer` (`customer_id`),
    CONSTRAINT `fk_cart_customer` FOREIGN KEY (`customer_id`) 
        REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 3. Table: orders
-- Menyimpan header data pesanan mesin / produk
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_id` INT NOT NULL,
    `order_number` VARCHAR(20) NOT NULL UNIQUE,
    `subtotal` BIGINT NOT NULL,
    `shipping_cost` BIGINT DEFAULT 0,
    `total` BIGINT NOT NULL,
    `status` ENUM(
        'pending_payment',
        'payment_uploaded',
        'payment_verified',
        'processing',
        'shipped',
        'delivered',
        'completed',
        'cancelled'
    ) DEFAULT 'pending_payment',
    `shipping_name` VARCHAR(100) NULL,
    `shipping_phone` VARCHAR(20) NULL,
    `shipping_address` TEXT NULL,
    `shipping_city` VARCHAR(100) NULL,
    `shipping_province` VARCHAR(100) NULL,
    `shipping_postal_code` VARCHAR(10) NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_orders_customer_id` (`customer_id`),
    INDEX `idx_orders_order_number` (`order_number`),
    INDEX `idx_orders_status` (`status`),
    INDEX `idx_orders_created_at` (`created_at`),
    CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) 
        REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 4. Table: order_items
-- Menyimpan detail item/produk pada setiap pesanan
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` VARCHAR(50) NOT NULL,
    `product_name` VARCHAR(255) NOT NULL,
    `product_image` VARCHAR(500) NULL,
    `quantity` INT NOT NULL,
    `price` BIGINT NOT NULL,
    `weight_grams` INT DEFAULT 0,
    INDEX `idx_order_items_order_id` (`order_id`),
    INDEX `idx_order_items_product_id` (`product_id`),
    CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) 
        REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 5. Table: payments
-- Menyimpan data konfirmasi pembayaran dan bukti transfer bank
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `bank_name` VARCHAR(50) NOT NULL,
    `account_number` VARCHAR(50) NOT NULL,
    `account_name` VARCHAR(100) NOT NULL,
    `amount` BIGINT NOT NULL,
    `proof_image` VARCHAR(500) NULL,
    `status` ENUM('pending', 'uploaded', 'verified', 'rejected') DEFAULT 'pending',
    `admin_notes` TEXT NULL,
    `verified_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_payments_order_id` (`order_id`),
    INDEX `idx_payments_status` (`status`),
    CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) 
        REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 6. Table: shipments
-- Menyimpan informasi pengiriman, ekspedisi kargo, dan nomor resi
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `shipments`;
CREATE TABLE `shipments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `expedition` VARCHAR(100) NULL,
    `tracking_number` VARCHAR(100) NULL,
    `status` ENUM('preparing', 'shipped', 'in_transit', 'delivered') DEFAULT 'preparing',
    `estimated_arrival` VARCHAR(100) NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_shipments_order_id` (`order_id`),
    INDEX `idx_shipments_tracking_number` (`tracking_number`),
    INDEX `idx_shipments_status` (`status`),
    CONSTRAINT `fk_shipments_order` FOREIGN KEY (`order_id`) 
        REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 7. Table: shipping_rates
-- Menyimpan tarif ongkos kirim kargo berdasarkan zona wilayah Indonesia
-- ------------------------------------------------------------------------------
DROP TABLE IF EXISTS `shipping_rates`;
CREATE TABLE `shipping_rates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `zone_name` VARCHAR(100) NOT NULL,
    `price_per_kg` BIGINT NOT NULL,
    `min_cost` BIGINT DEFAULT 0,
    `estimated_days` VARCHAR(50) NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    INDEX `idx_shipping_rates_zone` (`zone_name`),
    INDEX `idx_shipping_rates_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- SEED DATA: Default Shipping Rates (Zona Ekspedisi Kargo Indonesia)
-- ------------------------------------------------------------------------------
INSERT INTO `shipping_rates` (`zone_name`, `price_per_kg`, `min_cost`, `estimated_days`, `is_active`) VALUES
('Jawa', 15000, 50000, '2-4 Hari', 1),
('Sumatera', 25000, 75000, '3-6 Hari', 1),
('Bali & Nusa Tenggara', 22000, 70000, '3-5 Hari', 1),
('Kalimantan', 35000, 100000, '4-7 Hari', 1),
('Sulawesi', 40000, 120000, '4-8 Hari', 1),
('Papua', 65000, 200000, '7-14 Hari', 1);

SET FOREIGN_KEY_CHECKS = 1;
