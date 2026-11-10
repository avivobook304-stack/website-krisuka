-- ========================================================
-- KRISUKA E-COMMERCE DATABASE DUMP (MySQL / MariaDB)
-- Brand: KRISUKA (Keripik Sukun, Keladi & Pisang Fakfak, Papua Barat)
-- Slogan: "Renyah, Lezat, Cita Rasa Lokal."
-- Nomor WhatsApp Toko Resmi: 082274761719 (6282274761719)
-- Akun Admin Default:
--   Username : admin
--   Password : admin123
-- ========================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- 1. Buat Database krisuka_db
-- --------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `krisuka_db` 
  DEFAULT CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE `krisuka_db`;

-- --------------------------------------------------------
-- 2. Struktur Tabel `admins`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL UNIQUE,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'admin',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Data `admins` (Password: admin123)
INSERT INTO `admins` (`id`, `name`, `username`, `email`, `password_hash`, `role`, `created_at`) VALUES
(1, 'Administrator KRISUKA', 'admin', 'admin@krisuka.id', '$2y$10$ldztGIJw5x3HoWLHZNYqyumsX1fNSaCtp5202uJR9haSf7uUWYPzq', 'superadmin', '2026-11-10 12:00:00');

-- --------------------------------------------------------
-- 3. Struktur Tabel `categories`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL UNIQUE,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Data `categories`
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `created_at`) VALUES
(1, 'Keripik Sukun', 'keripik-sukun', 'Cemilan gurih renyah berbahan buah sukun pilihan khas Fakfak Papua Barat.', '2026-11-10 12:00:00'),
(2, 'Keripik Keladi', 'keripik-keladi', 'Keripik umbi keladi/talas khas Fakfak dengan tekstur super renyah.', '2026-11-10 12:00:00'),
(3, 'Keripik Pisang', 'keripik-pisang', 'Keripik pisang lokal Fakfak aneka rasa istimewa gurih, pedas, dan manis.', '2026-11-10 12:00:00');

-- --------------------------------------------------------
-- 4. Struktur Tabel `products`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(150) NOT NULL UNIQUE,
  `badge` varchar(50) DEFAULT 'Terfavorit',
  `price` decimal(12,2) NOT NULL DEFAULT 10000.00,
  `stock` int(11) NOT NULL DEFAULT 50,
  `netto` varchar(50) DEFAULT '100 gram',
  `description` text DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_prod_cat` (`category_id`),
  CONSTRAINT `fk_prod_cat` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Data `products` (7 Varian Resmi KRISUKA)
INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `badge`, `price`, `stock`, `netto`, `description`, `image`, `is_active`, `is_featured`, `created_at`, `updated_at`) VALUES
(1, 1, 'Keripik Sukun Fakfak', 'keripik-sukun-fakfak', 'Khas Fakfak Terfavorit', 10000.00, 60, '100 gram', 'Oleh-oleh ikonik dari Fakfak, Papua Barat. Dibuat dari buah sukun segar berkualitas tinggi yang diiris presisi dan digoreng dengan minyak nabati higienis. Memiliki rasa gurih lembut mentega alami yang renyah dan bikin nagih di lidah.', 'https://images.unsplash.com/photo-1599490659213-e2b9527bd087?auto=format&fit=crop&w=700&q=80', 1, 1, '2026-11-10 12:00:00', '2026-11-10 12:00:00'),
(2, 2, 'Keripik Keladi Fakfak', 'keripik-keladi-fakfak', 'Gurih Renyah Alami', 10000.00, 55, '100 gram', 'Keripik keladi (talas lokal) khas tanah Fakfak Papua. Irisan tipis yang digoreng garing menghasilkan sensasi kerenyahan tiada duanya, berpadu taburan garam gurih alami tanpa bahan pengawet.', 'https://images.unsplash.com/photo-1566478989037-eec170784d0b?auto=format&fit=crop&w=700&q=80', 1, 1, '2026-11-10 12:00:00', '2026-11-10 12:00:00'),
(3, 3, 'Keripik Pisang Balado', 'keripik-pisang-balado', 'Pedas Manis Gurih', 10000.00, 70, '100 gram', 'Sensasi bumbu balado khas yang pedas, manis, dan gurih meresap sempurna di setiap kepingan keripik pisang renyah hasil panen petani lokal Fakfak.', 'https://images.unsplash.com/photo-1599490659213-e2b9527bd087?auto=format&fit=crop&w=700&q=80', 1, 1, '2026-11-10 12:00:00', '2026-11-10 12:00:00'),
(4, 3, 'Keripik Pisang Jagung Bakar', 'keripik-pisang-jagung-bakar', 'Aroma Bakar Gurih', 10000.00, 65, '100 gram', 'Paduan rasa jagung bakar manis dan gurih dengan aroma bakar khas yang harum menggugah selera, favorit camilan santai keluarga.', 'https://images.unsplash.com/photo-1566478989037-eec170784d0b?auto=format&fit=crop&w=700&q=80', 1, 0, '2026-11-10 12:00:00', '2026-11-10 12:00:00'),
(5, 3, 'Keripik Pisang Original', 'keripik-pisang-original', 'Renyah Asli & Alami', 10000.00, 80, '100 gram', 'Rasa klasik keripik pisang gurih dengan taburan garam ringan, menonjolkan keaslian rasa manis dan gurih alami pisang Fakfak.', 'https://images.unsplash.com/photo-1621447504864-d8686e12698c?auto=format&fit=crop&w=700&q=80', 1, 1, '2026-11-10 12:00:00', '2026-11-10 12:00:00'),
(6, 3, 'Keripik Pisang Coklat', 'keripik-pisang-coklat', 'Manis Legit Coklat Tebal', 10000.00, 55, '100 gram', 'Balutan coklat manis legit yang lumer menyelimuti kepingan pisang renyah, perpaduan manis coklat dan gurih yang pas di mulut.', 'https://images.unsplash.com/photo-1541781774459-bb2af2f05b55?auto=format&fit=crop&w=700&q=80', 1, 1, '2026-11-10 12:00:00', '2026-11-10 12:00:00'),
(7, 3, 'Keripik Pisang Milo', 'keripik-pisang-milo', 'Coklat Malt Creamy', 10000.00, 50, '100 gram', 'Varian favorit anak muda dengan taburan serbuk coklat malt Milo asli yang melimpah, wangi susu coklat creamy yang bikin ketagihan.', 'https://images.unsplash.com/photo-1587132137056-bfbf0166836e?auto=format&fit=crop&w=700&q=80', 1, 1, '2026-11-10 12:00:00', '2026-11-10 12:00:00');

-- --------------------------------------------------------
-- 5. Struktur Tabel `store_settings`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `store_settings`;
CREATE TABLE `store_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Data `store_settings`
INSERT INTO `store_settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('store_name', 'KRISUKA', '2026-11-10 12:00:00'),
('store_abbreviation', 'Keripik Sukun dan Keladi', '2026-11-10 12:00:00'),
('store_tagline', 'Renyah, Lezat, Cita Rasa Lokal.', '2026-11-10 12:00:00'),
('store_origin', 'Fakfak, Papua Barat', '2026-11-10 12:00:00'),
('whatsapp_number', '6282274761719', '2026-11-10 12:00:00'),
('instagram_handle', '@krisuka.fakfak', '2026-11-10 12:00:00'),
('instagram_url', 'https://www.instagram.com/krisuka.fakfak?utm_source=ig_web_button_share_sheet&cplk=ZDNlZDc0MzIxNw==', '2026-11-10 12:00:00'),
('store_address', 'Kabupaten Fakfak, Papua Barat, Indonesia', '2026-11-10 12:00:00'),
('operational_hours', 'Senin - Sabtu: 08.00 - 20.00 WIT', '2026-11-10 12:00:00'),
('shipping_info', 'Pengiriman dari Fakfak, Papua Barat ke seluruh Nusantara. Packing kardus tebal & bubble wrap aman.', '2026-11-10 12:00:00'),
('default_price', '10000', '2026-11-10 12:00:00'),
('bank_info', 'BCA: 123-456-7890 a.n KRISUKA | BRI: 0987-01-023456-50-1 a.n KRISUKA', '2026-11-10 12:00:00');

-- --------------------------------------------------------
-- 6. Struktur Tabel `vouchers` (Kupon Diskon)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `vouchers`;
CREATE TABLE `vouchers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL UNIQUE,
  `discount_type` varchar(20) NOT NULL DEFAULT 'fixed',
  `discount_value` decimal(12,2) NOT NULL DEFAULT 5000.00,
  `min_spend` decimal(12,2) NOT NULL DEFAULT 20000.00,
  `max_discount` decimal(12,2) NOT NULL DEFAULT 10000.00,
  `usage_limit` int(11) NOT NULL DEFAULT 100,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `valid_until` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Data `vouchers`
INSERT INTO `vouchers` (`id`, `code`, `discount_type`, `discount_value`, `min_spend`, `max_discount`, `usage_limit`, `used_count`, `valid_until`, `is_active`, `created_at`) VALUES
(1, 'KRISUKAFK', 'fixed', 5000.00, 20000.00, 5000.00, 100, 0, '2026-12-31', 1, '2026-11-10 12:00:00'),
(2, 'HEMAT10', 'percent', 10.00, 30000.00, 15000.00, 100, 0, '2026-12-31', 1, '2026-11-10 12:00:00');

-- --------------------------------------------------------
-- 7. Struktur Tabel `orders`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_code` varchar(50) NOT NULL UNIQUE,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(30) NOT NULL,
  `customer_address` text NOT NULL,
  `customer_notes` text DEFAULT NULL,
  `shipping_zone` varchar(100) DEFAULT NULL,
  `shipping_cost` decimal(12,2) DEFAULT 0.00,
  `voucher_code` varchar(50) DEFAULT NULL,
  `discount_amount` decimal(12,2) DEFAULT 0.00,
  `total_qty` int(11) NOT NULL DEFAULT 0,
  `total_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(50) DEFAULT 'bank_transfer', -- bank_transfer, qris, cod, ewallet
  `order_status` varchar(30) DEFAULT 'menunggu_konfirmasi',
  `whatsapp_url` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 8. Struktur Tabel `order_items`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `qty` int(11) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_items_order` (`order_id`),
  CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 9. Struktur Tabel `stock_logs`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `stock_logs`;
CREATE TABLE `stock_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `type` varchar(20) NOT NULL,
  `quantity` int(11) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_logs_prod` (`product_id`),
  CONSTRAINT `fk_logs_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Data Awal `stock_logs`
INSERT INTO `stock_logs` (`id`, `product_id`, `type`, `quantity`, `note`, `created_at`) VALUES
(1, 1, 'in', 60, 'Stok Awal Produksi Sukun', '2026-11-10 12:00:00'),
(2, 2, 'in', 55, 'Stok Awal Produksi Keladi', '2026-11-10 12:00:00'),
(3, 3, 'in', 70, 'Stok Awal Pisang Balado', '2026-11-10 12:00:00'),
(4, 4, 'in', 65, 'Stok Awal Pisang Jagung Bakar', '2026-11-10 12:00:00'),
(5, 5, 'in', 80, 'Stok Awal Pisang Original', '2026-11-10 12:00:00'),
(6, 6, 'in', 55, 'Stok Awal Pisang Coklat', '2026-11-10 12:00:00'),
(7, 7, 'in', 50, 'Stok Awal Pisang Milo', '2026-11-10 12:00:00');

-- --------------------------------------------------------
-- 10. Struktur Tabel `product_reviews`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `product_reviews`;
CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_city` varchar(100) DEFAULT 'Fakfak',
  `rating` int(11) NOT NULL DEFAULT 5,
  `review_text` text NOT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_reviews_prod` (`product_id`),
  CONSTRAINT `fk_reviews_prod` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

