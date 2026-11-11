-- ============================================================
-- AMBOZY GRAPHICS CRM — Delivery Notes
-- NOTE: these tables are also created automatically the first time
-- the Delivery Notes page is opened; importing this file is optional.
-- ============================================================
CREATE TABLE IF NOT EXISTS `delivery_notes` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `dn_number`        VARCHAR(20)   NOT NULL UNIQUE,
  `client_id`        INT UNSIGNED  NOT NULL,
  `quotation_id`     INT UNSIGNED  DEFAULT NULL,
  `invoice_id`       INT UNSIGNED  DEFAULT NULL,
  `order_id`         INT UNSIGNED  DEFAULT NULL,
  `title`            VARCHAR(200)  NOT NULL,
  `delivery_date`    DATE          NOT NULL,
  `delivered_to`     VARCHAR(150)  DEFAULT NULL,
  `delivery_address` TEXT          DEFAULT NULL,
  `driver_name`      VARCHAR(100)  DEFAULT NULL,
  `vehicle_no`       VARCHAR(40)   DEFAULT NULL,
  `status`           ENUM('draft','dispatched','delivered','cancelled') NOT NULL DEFAULT 'draft',
  `notes`            TEXT          DEFAULT NULL,
  `received_by`      VARCHAR(120)  DEFAULT NULL,
  `created_by`       INT UNSIGNED  DEFAULT NULL,
  `created_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_status` (`status`),
  KEY `idx_quote`  (`quotation_id`),
  KEY `idx_invoice`(`invoice_id`),
  CONSTRAINT `fk_dn_client` FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `delivery_note_items` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `delivery_note_id` INT UNSIGNED  NOT NULL,
  `description`      VARCHAR(255)  NOT NULL,
  `quantity`         DECIMAL(10,3) NOT NULL DEFAULT 1.000,
  `unit`             VARCHAR(40)   DEFAULT 'piece',
  `sort_order`       TINYINT       NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_dn` (`delivery_note_id`),
  CONSTRAINT `fk_dnitem_dn` FOREIGN KEY (`delivery_note_id`) REFERENCES `delivery_notes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
