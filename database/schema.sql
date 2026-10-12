 -- =========================================================================
-- INVENTORY MANAGEMENT SYSTEM - RELATIONAL DATABASE SCHEMA
-- Mini Project 3 (Pertemuan 4)
-- =========================================================================

CREATE DATABASE IF NOT EXISTS `inventory_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `inventory_db`;

-- 1. Tabel Users (Untuk Autentikasi dan Role Authorization)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabel Categories (Relasi One-to-Many dengan Products)
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabel Products (Master Data Produk)
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `sku` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `price` DECIMAL(12,2) NOT NULL,
  `stock` INT NOT NULL DEFAULT 0,
  `minimum_stock` INT NOT NULL DEFAULT 5,
  `image` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabel Suppliers (Master Data Supplier)
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(30) NULL,
  `address` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabel Junction `product_suppliers` (Relasi Many-to-Many Products ↔ Suppliers)
CREATE TABLE IF NOT EXISTS `product_suppliers` (
  `product_id` INT NOT NULL,
  `supplier_id` INT NOT NULL,
  `supplier_price` DECIMAL(12,2) NULL,
  PRIMARY KEY (`product_id`, `supplier_id`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabel Stock Movements (Histori Barang Masuk & Keluar)
CREATE TABLE IF NOT EXISTS `stock_movements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `type` ENUM('IN', 'OUT') NOT NULL,
  `quantity` INT NOT NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- DUMMY DATA AWAL (SEEDER) UNTUK PENGUJIAN
-- =========================================================================

-- Password default untuk semua user di bawah adalah: password123
-- (Hash dihasilkan menggunakan password_hash('password123', PASSWORD_DEFAULT))
INSERT INTO `users` (`id`, `username`, `password_hash`, `role`) VALUES
(1, 'admin_pusat', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
(2, 'staff_gudang', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff')
ON DUPLICATE KEY UPDATE `username`=`username`;

INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Elektronik', 'Perangkat elektronik dan gadget'),
(2, 'Alat Tulis Kantor', 'Perlengkapan tulis dan kantor')
ON DUPLICATE KEY UPDATE `name`=`name`;

INSERT INTO `suppliers` (`id`, `name`, `phone`, `address`) VALUES
(1, 'PT Sumber Jaya Mandiri', '081234567890', 'Jl. Merdeka No. 10, Jakarta'),
(2, 'CV Maju Bersama', '089876543210', 'Jl. Sudirman No. 45, Bandung')
ON DUPLICATE KEY UPDATE `name`=`name`;