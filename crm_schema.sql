-- ============================================================
-- AMBOZY GRAPHICS CRM — Extended Schema (Phase 1)
-- Run AFTER schema.sql:  mysql -u root -p ambozy_db < crm_schema.sql
-- ============================================================

USE `ambozy_db`;

-- ── Document number sequences ────────────────────────────────
CREATE TABLE IF NOT EXISTS `doc_sequences` (
  `prefix`      VARCHAR(20)   NOT NULL,
  `year`        YEAR          NOT NULL,
  `last_number` INT UNSIGNED  NOT NULL DEFAULT 0,
  PRIMARY KEY (`prefix`, `year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Clients (master customer records) ───────────────────────
CREATE TABLE IF NOT EXISTS `clients` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `code`        VARCHAR(20)   NOT NULL UNIQUE,          -- CLI-0001
  `name`        VARCHAR(120)  NOT NULL,
  `email`       VARCHAR(180)  DEFAULT NULL,
  `phone`       VARCHAR(40)   DEFAULT NULL,
  `company`     VARCHAR(160)  DEFAULT NULL,
  `address`     TEXT          DEFAULT NULL,
  `city`        VARCHAR(80)   DEFAULT NULL,
  `type`        ENUM('individual','business') NOT NULL DEFAULT 'individual',
  `status`      ENUM('active','inactive')     NOT NULL DEFAULT 'active',
  `source`      ENUM('inquiry','manual','referral','walk-in','online') NOT NULL DEFAULT 'manual',
  `notes`       TEXT          DEFAULT NULL,
  `created_by`  INT UNSIGNED  DEFAULT NULL,
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email`  (`email`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Leads / Sales Pipeline ───────────────────────────────────
CREATE TABLE IF NOT EXISTS `leads` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `ref`              VARCHAR(20)   NOT NULL UNIQUE,        -- LEAD-0001
  `client_id`        INT UNSIGNED  DEFAULT NULL,           -- set on convert
  `name`             VARCHAR(120)  NOT NULL,
  `email`            VARCHAR(180)  DEFAULT NULL,
  `phone`            VARCHAR(40)   DEFAULT NULL,
  `company`          VARCHAR(160)  DEFAULT NULL,
  `service_interest` VARCHAR(120)  DEFAULT NULL,
  `budget`           VARCHAR(80)   DEFAULT NULL,
  `message`          TEXT          DEFAULT NULL,
  `status`           ENUM('new','contacted','qualified','quoted','won','lost') NOT NULL DEFAULT 'new',
  `source`           ENUM('website','walk-in','referral','phone','email','social') NOT NULL DEFAULT 'website',
  `notes`            TEXT          DEFAULT NULL,
  `ip_address`       VARCHAR(45)   DEFAULT NULL,
  `converted_at`     DATETIME      DEFAULT NULL,
  `created_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status`  (`status`),
  KEY `idx_client`  (`client_id`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `fk_leads_client` FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Price Catalog ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `catalog_items` (
  `id`          INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `code`        VARCHAR(40)    DEFAULT NULL,
  `category`    VARCHAR(80)    DEFAULT NULL,
  `name`        VARCHAR(160)   NOT NULL,
  `description` TEXT           DEFAULT NULL,
  `unit`        VARCHAR(40)    NOT NULL DEFAULT 'piece',
  `unit_price`  DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  `is_active`   TINYINT(1)     NOT NULL DEFAULT 1,
  `created_at`  DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Quotations ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `quotations` (
  `id`               INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `quote_number`     VARCHAR(20)    NOT NULL UNIQUE,      -- QUO-2026-0001
  `client_id`        INT UNSIGNED   NOT NULL,
  `lead_id`          INT UNSIGNED   DEFAULT NULL,
  `title`            VARCHAR(200)   NOT NULL,
  `status`           ENUM('draft','sent','accepted','rejected','expired') NOT NULL DEFAULT 'draft',
  `valid_until`      DATE           DEFAULT NULL,
  `subtotal`         DECIMAL(14,2)  NOT NULL DEFAULT 0.00,
  `discount_percent` DECIMAL(5,2)   NOT NULL DEFAULT 0.00,
  `tax_percent`      DECIMAL(5,2)   NOT NULL DEFAULT 0.00,
  `total`            DECIMAL(14,2)  NOT NULL DEFAULT 0.00,
  `notes`            TEXT           DEFAULT NULL,
  `terms`            TEXT           DEFAULT NULL,
  `created_by`       INT UNSIGNED   DEFAULT NULL,
  `sent_at`          DATETIME       DEFAULT NULL,
  `created_at`       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_quot_client` FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `quotation_items` (
  `id`             INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `quotation_id`   INT UNSIGNED   NOT NULL,
  `catalog_item_id`INT UNSIGNED   DEFAULT NULL,
  `description`    VARCHAR(255)   NOT NULL,
  `quantity`       DECIMAL(10,3)  NOT NULL DEFAULT 1.000,
  `unit`           VARCHAR(40)    DEFAULT 'piece',
  `unit_price`     DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  `total`          DECIMAL(14,2)  NOT NULL DEFAULT 0.00,
  `sort_order`     TINYINT        NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_quot` (`quotation_id`),
  CONSTRAINT `fk_qitem_quot` FOREIGN KEY (`quotation_id`) REFERENCES `quotations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Orders / Production Jobs ─────────────────────────────────
CREATE TABLE IF NOT EXISTS `orders` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `order_number`     VARCHAR(20)   NOT NULL UNIQUE,       -- ORD-2026-0001
  `client_id`        INT UNSIGNED  NOT NULL,
  `quotation_id`     INT UNSIGNED  DEFAULT NULL,
  `title`            VARCHAR(200)  NOT NULL,
  `status`           ENUM('pending','in_production','ready','delivered','completed','cancelled') NOT NULL DEFAULT 'pending',
  `priority`         ENUM('normal','urgent')  NOT NULL DEFAULT 'normal',
  `due_date`         DATE          DEFAULT NULL,
  `delivery_address` TEXT          DEFAULT NULL,
  `notes`            TEXT          DEFAULT NULL,
  `created_by`       INT UNSIGNED  DEFAULT NULL,
  `created_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_ord_client` FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_items` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `order_id`    INT UNSIGNED  NOT NULL,
  `description` VARCHAR(255)  NOT NULL,
  `quantity`    VARCHAR(50)   DEFAULT NULL,
  `unit`        VARCHAR(40)   DEFAULT NULL,
  `notes`       TEXT          DEFAULT NULL,
  `sort_order`  TINYINT       NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_order` (`order_id`),
  CONSTRAINT `fk_oitem_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Invoices ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `invoices` (
  `id`               INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `invoice_number`   VARCHAR(20)    NOT NULL UNIQUE,      -- INV-2026-0001
  `client_id`        INT UNSIGNED   NOT NULL,
  `order_id`         INT UNSIGNED   DEFAULT NULL,
  `quotation_id`     INT UNSIGNED   DEFAULT NULL,
  `status`           ENUM('draft','sent','partial','paid','overdue','cancelled') NOT NULL DEFAULT 'draft',
  `subtotal`         DECIMAL(14,2)  NOT NULL DEFAULT 0.00,
  `discount_percent` DECIMAL(5,2)   NOT NULL DEFAULT 0.00,
  `tax_percent`      DECIMAL(5,2)   NOT NULL DEFAULT 0.00,
  `total`            DECIMAL(14,2)  NOT NULL DEFAULT 0.00,
  `amount_paid`      DECIMAL(14,2)  NOT NULL DEFAULT 0.00,
  `due_date`         DATE           DEFAULT NULL,
  `notes`            TEXT           DEFAULT NULL,
  `terms`            TEXT           DEFAULT NULL,
  `created_by`       INT UNSIGNED   DEFAULT NULL,
  `sent_at`          DATETIME       DEFAULT NULL,
  `paid_at`          DATETIME       DEFAULT NULL,
  `created_at`       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_inv_client` FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `invoice_items` (
  `id`             INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `invoice_id`     INT UNSIGNED   NOT NULL,
  `catalog_item_id`INT UNSIGNED   DEFAULT NULL,
  `description`    VARCHAR(255)   NOT NULL,
  `quantity`       DECIMAL(10,3)  NOT NULL DEFAULT 1.000,
  `unit`           VARCHAR(40)    DEFAULT 'piece',
  `unit_price`     DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  `total`          DECIMAL(14,2)  NOT NULL DEFAULT 0.00,
  `sort_order`     TINYINT        NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_inv` (`invoice_id`),
  CONSTRAINT `fk_iitem_inv` FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Payments ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `payments` (
  `id`           INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `invoice_id`   INT UNSIGNED   NOT NULL,
  `client_id`    INT UNSIGNED   NOT NULL,
  `amount`       DECIMAL(14,2)  NOT NULL,
  `method`       ENUM('cash','bank_transfer','mobile_money','card','cheque') NOT NULL DEFAULT 'cash',
  `reference`    VARCHAR(100)   DEFAULT NULL,
  `notes`        TEXT           DEFAULT NULL,
  `payment_date` DATE           NOT NULL,
  `recorded_by`  INT UNSIGNED   DEFAULT NULL,
  `created_at`   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_invoice` (`invoice_id`),
  CONSTRAINT `fk_pay_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Email Log ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `email_logs` (
  `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `type`            VARCHAR(60)   NOT NULL,
  `recipient_email` VARCHAR(180)  NOT NULL,
  `recipient_name`  VARCHAR(120)  DEFAULT NULL,
  `subject`         VARCHAR(255)  NOT NULL,
  `status`          ENUM('sent','failed') NOT NULL DEFAULT 'sent',
  `error_message`   TEXT          DEFAULT NULL,
  `related_type`    VARCHAR(40)   DEFAULT NULL,
  `related_id`      INT UNSIGNED  DEFAULT NULL,
  `created_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_related` (`related_type`, `related_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Activity Stream ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `activities` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `type`         VARCHAR(60)   NOT NULL,
  `description`  TEXT          NOT NULL,
  `related_type` VARCHAR(40)   DEFAULT NULL,
  `related_id`   INT UNSIGNED  DEFAULT NULL,
  `created_by`   INT UNSIGNED  DEFAULT NULL,
  `created_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_related` (`related_type`, `related_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Migrate existing inquiries → leads ──────────────────────
INSERT IGNORE INTO `leads`
  (`ref`, `name`, `email`, `phone`, `company`, `service_interest`, `budget`, `message`, `status`, `source`, `ip_address`, `created_at`, `updated_at`)
SELECT
  CONCAT('LEAD-', LPAD(id, 4, '0')),
  `name`, `email`, `phone`, `company`, `service`, `budget`, `message`,
  CASE `status`
    WHEN 'new'     THEN 'new'
    WHEN 'read'    THEN 'contacted'
    WHEN 'replied' THEN 'quoted'
    WHEN 'closed'  THEN 'won'
    ELSE 'new'
  END,
  'website',
  `ip_address`,
  `created_at`,
  `updated_at`
FROM `inquiries`;

-- ── Seed sequence counters ───────────────────────────────────
INSERT IGNORE INTO `doc_sequences` (`prefix`, `year`, `last_number`) VALUES
  ('CLI',  YEAR(CURDATE()), 0),
  ('LEAD', YEAR(CURDATE()), (SELECT IFNULL(MAX(id),0) FROM `inquiries`)),
  ('QUO',  YEAR(CURDATE()), 0),
  ('ORD',  YEAR(CURDATE()), 0),
  ('INV',  YEAR(CURDATE()), 0);
