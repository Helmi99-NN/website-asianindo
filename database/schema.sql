-- Database Schema for CV Asianindo E-Commerce
CREATE TABLE IF NOT EXISTS `customers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `phone` VARCHAR(50) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `address` TEXT NULL,
    `city` VARCHAR(100) NULL,
    `province` VARCHAR(100) NULL,
    `postal_code` VARCHAR(20) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
