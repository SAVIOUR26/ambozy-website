-- ============================================================
-- AMBOZY GRAPHICS SOLUTIONS LTD — Database Schema
-- Run this in phpMyAdmin or via: mysql -u root -p ambozy_db < schema.sql
-- ============================================================

-- HOW TO IMPORT IN phpMyAdmin (cPanel):
--   1. Click your database name in the LEFT panel of phpMyAdmin
--   2. Click the Import tab at the top
--   3. Choose this file and click Go

-- ── Quote Inquiries ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `inquiries` (
  `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(120)    NOT NULL,
  `email`      VARCHAR(180)    NOT NULL,
  `phone`      VARCHAR(40)     DEFAULT NULL,
  `company`    VARCHAR(160)    DEFAULT NULL,
  `service`    VARCHAR(120)    DEFAULT NULL,
  `budget`     VARCHAR(80)     DEFAULT NULL,
  `message`    TEXT            NOT NULL,
  `status`     ENUM('new','read','replied','closed') NOT NULL DEFAULT 'new',
  `notes`      TEXT            DEFAULT NULL,
  `ip_address` VARCHAR(45)     DEFAULT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status`     (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Admin Users ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `username`     VARCHAR(60)   NOT NULL UNIQUE,
  `password_hash`VARCHAR(255)  NOT NULL,
  `full_name`    VARCHAR(120)  DEFAULT NULL,
  `email`        VARCHAR(180)  DEFAULT NULL,
  `last_login`   DATETIME      DEFAULT NULL,
  `created_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Services (CMS-managed) ───────────────────────────────────
CREATE TABLE IF NOT EXISTS `services` (
  `id`          INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `icon`        VARCHAR(10)    DEFAULT '🎨',
  `title`       VARCHAR(120)   NOT NULL,
  `description` TEXT           DEFAULT NULL,
  `items`       TEXT           DEFAULT NULL,
  `sort_order`  TINYINT        NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)     NOT NULL DEFAULT 1,
  `created_at`  DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Gallery ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `gallery` (
  `id`          INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `filename`    VARCHAR(255)   NOT NULL,
  `caption`     VARCHAR(255)   DEFAULT NULL,
  `category`    VARCHAR(80)    DEFAULT NULL,
  `sort_order`  SMALLINT       NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)     NOT NULL DEFAULT 1,
  `created_at`  DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Site Settings ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `settings` (
  `key`         VARCHAR(100)   NOT NULL,
  `value`       TEXT           DEFAULT NULL,
  `updated_at`  DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Seed default admin (password: admin123 — CHANGE IMMEDIATELY) ──
INSERT IGNORE INTO `admin_users` (`username`, `password_hash`, `full_name`, `email`)
VALUES (
  'admin',
  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- admin123
  'Ambozy Admin',
  'info@ambozygraphics.com'
);

-- ── Seed default services ────────────────────────────────────
INSERT IGNORE INTO `services` (`id`,`icon`,`title`,`items`,`sort_order`) VALUES
(1,  '👕','Branded Merchandise',  'T-shirts, Polo Shirts, Caps, Aprons, Fleece, Overalls, Bags', 1),
(2,  '🎁','Branded Giveaways',    'Keyrings, Mugs, Pens, USB Drives, Umbrellas, Wristbands', 2),
(3,  '📖','Books & Magazines',    'Magazines, Invite Cards, Certificates, Business Cards, Newsletters', 3),
(4,  '📋','Stationery',           'Letterheads, Envelopes, Corporate & Computer Stationery', 4),
(5,  '📣','Marketing Materials',  'Posters, Brochures, Banners, Calendars, Car Branding', 5),
(6,  '🪟','Signage & Signs',      'Neon, Illuminated, Light Boxes, Acrylic, Pull-ups, Backdrops', 6),
(7,  '🛒','Point of Sale',        'Wobblers, Shelf Strips, Danglers, POS Displays', 7),
(8,  '📦','Packaging Solutions',  'Product Labels, Shopping Bags, Kraft Paper, Boxes, Paper Cups', 8),
(9,  '🏆','Awards & Plaques',     'Crystal Awards, Wooden Plaques, Trophies, Desk Sign Holders', 9),
(10, '📡','Outdoor Advertising',  'Tents, Billboards, Pavement Signs, Pull-up Banners, Light Boxes', 10);

-- ── Seed default settings ────────────────────────────────────
INSERT IGNORE INTO `settings` (`key`,`value`) VALUES
('site_name',   'Ambozy Graphics Solutions Ltd'),
('site_phone',  '+256 782 187 799'),
('site_email',  'info@ambozygraphics.com'),
('site_address','Plot 1314 Church Road, Buye, Ntinda, Kampala'),
('whatsapp',    '256782187799'),
('facebook',    ''),
('instagram',   ''),
('twitter',     '');
