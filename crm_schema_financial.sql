-- ============================================================
-- AMBOZY GRAPHICS CRM — Financial Expansion Schema (Phase 2)
-- HOW TO IMPORT IN phpMyAdmin (cPanel):
--   1. Click your database name in the LEFT panel of phpMyAdmin
--   2. Click the Import tab at the top
--   3. Choose this file and click Go
-- ============================================================

-- ── Suppliers ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id`            INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `code`          VARCHAR(20)    NOT NULL UNIQUE,           -- SUP-2026-0001
  `name`          VARCHAR(120)   NOT NULL,
  `contact_name`  VARCHAR(120)   DEFAULT NULL,
  `email`         VARCHAR(180)   DEFAULT NULL,
  `phone`         VARCHAR(40)    DEFAULT NULL,
  `address`       TEXT           DEFAULT NULL,
  `city`          VARCHAR(80)    DEFAULT NULL,
  `credit_limit`  DECIMAL(14,2)  NOT NULL DEFAULT 0.00,
  `credit_days`   TINYINT UNSIGNED NOT NULL DEFAULT 30,     -- payment terms
  `status`        ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `notes`         TEXT           DEFAULT NULL,
  `created_by`    INT UNSIGNED   DEFAULT NULL,
  `created_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Purchases (purchase orders from suppliers) ────────────────
CREATE TABLE IF NOT EXISTS `purchases` (
  `id`              INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `purchase_number` VARCHAR(20)    NOT NULL UNIQUE,         -- PUR-2026-0001
  `supplier_id`     INT UNSIGNED   NOT NULL,
  `payment_type`    ENUM('cash','credit') NOT NULL DEFAULT 'cash',
  `status`          ENUM('pending','received','partial','paid','cancelled') NOT NULL DEFAULT 'pending',
  `subtotal`        DECIMAL(14,2)  NOT NULL DEFAULT 0.00,
  `tax_amount`      DECIMAL(14,2)  NOT NULL DEFAULT 0.00,
  `total`           DECIMAL(14,2)  NOT NULL DEFAULT 0.00,
  `amount_paid`     DECIMAL(14,2)  NOT NULL DEFAULT 0.00,
  `due_date`        DATE           DEFAULT NULL,
  `purchase_date`   DATE           NOT NULL,
  `notes`           TEXT           DEFAULT NULL,
  `created_by`      INT UNSIGNED   DEFAULT NULL,
  `created_at`      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_supplier` (`supplier_id`),
  KEY `idx_status`   (`status`),
  CONSTRAINT `fk_pur_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `purchase_items` (
  `id`          INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `purchase_id` INT UNSIGNED   NOT NULL,
  `description` VARCHAR(255)   NOT NULL,
  `quantity`    DECIMAL(10,3)  NOT NULL DEFAULT 1.000,
  `unit`        VARCHAR(40)    DEFAULT 'piece',
  `unit_price`  DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  `total`       DECIMAL(14,2)  NOT NULL DEFAULT 0.00,
  `sort_order`  TINYINT        NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_purchase` (`purchase_id`),
  CONSTRAINT `fk_pitem_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `purchases`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Supplier Payments ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `supplier_payments` (
  `id`           INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `purchase_id`  INT UNSIGNED   NOT NULL,
  `supplier_id`  INT UNSIGNED   NOT NULL,
  `amount`       DECIMAL(14,2)  NOT NULL,
  `method`       ENUM('cash','bank_transfer','mobile_money','card','cheque') NOT NULL DEFAULT 'cash',
  `reference`    VARCHAR(100)   DEFAULT NULL,
  `notes`        TEXT           DEFAULT NULL,
  `payment_date` DATE           NOT NULL,
  `recorded_by`  INT UNSIGNED   DEFAULT NULL,
  `created_at`   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_purchase`  (`purchase_id`),
  KEY `idx_supplier`  (`supplier_id`),
  CONSTRAINT `fk_spay_purchase`  FOREIGN KEY (`purchase_id`) REFERENCES `purchases`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_spay_supplier`  FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Expense Categories ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `expense_categories` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(80)   NOT NULL UNIQUE,
  `is_active`  TINYINT(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pre-seeded categories
INSERT IGNORE INTO `expense_categories` (`name`) VALUES
  ('Fuel & Transport'),
  ('Facilitation & Allowances'),
  ('Rent & Premises'),
  ('Utilities (Water/Electricity)'),
  ('Internet & Communication'),
  ('Office Stationery'),
  ('Equipment Maintenance'),
  ('Advertising & Marketing'),
  ('Bank Charges'),
  ('Entertainment & Hospitality'),
  ('Other');

-- ── Expenses (company operational costs) ─────────────────────
CREATE TABLE IF NOT EXISTS `expenses` (
  `id`          INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `ref`         VARCHAR(20)    NOT NULL UNIQUE,             -- EXP-2026-0001
  `category_id` INT UNSIGNED   DEFAULT NULL,
  `description` VARCHAR(255)   NOT NULL,
  `amount`      DECIMAL(14,2)  NOT NULL,
  `method`      ENUM('cash','bank_transfer','mobile_money','card','cheque') NOT NULL DEFAULT 'cash',
  `reference`   VARCHAR(100)   DEFAULT NULL,
  `vendor`      VARCHAR(120)   DEFAULT NULL,
  `expense_date`DATE           NOT NULL,
  `notes`       TEXT           DEFAULT NULL,
  `recorded_by` INT UNSIGNED   DEFAULT NULL,
  `created_at`  DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_date`     (`expense_date`),
  CONSTRAINT `fk_exp_category` FOREIGN KEY (`category_id`) REFERENCES `expense_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Employees ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `employees` (
  `id`              INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `emp_number`      VARCHAR(20)    NOT NULL UNIQUE,         -- EMP-0001
  `name`            VARCHAR(120)   NOT NULL,
  `position`        VARCHAR(100)   DEFAULT NULL,
  `department`      VARCHAR(80)    DEFAULT NULL,
  `email`           VARCHAR(180)   DEFAULT NULL,
  `phone`           VARCHAR(40)    DEFAULT NULL,
  `tin`             VARCHAR(30)    DEFAULT NULL,            -- URA TIN
  `nssf_number`     VARCHAR(30)    DEFAULT NULL,
  `gross_salary`    DECIMAL(12,2)  NOT NULL DEFAULT 0.00,   -- monthly gross
  `employment_type` ENUM('permanent','contract','casual') NOT NULL DEFAULT 'permanent',
  `hire_date`       DATE           DEFAULT NULL,
  `status`          ENUM('active','terminated','on_leave') NOT NULL DEFAULT 'active',
  `notes`           TEXT           DEFAULT NULL,
  `created_by`      INT UNSIGNED   DEFAULT NULL,
  `created_at`      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Payroll Runs ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `payroll` (
  `id`           INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `ref`          VARCHAR(20)    NOT NULL UNIQUE,            -- PAY-2026-05
  `pay_month`    TINYINT UNSIGNED NOT NULL,                 -- 1-12
  `pay_year`     YEAR           NOT NULL,
  `status`       ENUM('draft','approved','paid') NOT NULL DEFAULT 'draft',
  `total_gross`  DECIMAL(14,2)  NOT NULL DEFAULT 0.00,
  `total_paye`   DECIMAL(14,2)  NOT NULL DEFAULT 0.00,
  `total_nssf_employee` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `total_nssf_employer` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `total_net`    DECIMAL(14,2)  NOT NULL DEFAULT 0.00,
  `notes`        TEXT           DEFAULT NULL,
  `created_by`   INT UNSIGNED   DEFAULT NULL,
  `created_at`   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_month_year` (`pay_month`, `pay_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payroll_items` (
  `id`              INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `payroll_id`      INT UNSIGNED   NOT NULL,
  `employee_id`     INT UNSIGNED   NOT NULL,
  `gross_salary`    DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  `paye`            DECIMAL(10,2)  NOT NULL DEFAULT 0.00,   -- income tax withheld
  `nssf_employee`   DECIMAL(10,2)  NOT NULL DEFAULT 0.00,   -- 5% employee contribution
  `nssf_employer`   DECIMAL(10,2)  NOT NULL DEFAULT 0.00,   -- 10% employer contribution
  `other_deductions`DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `net_pay`         DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
  `notes`           VARCHAR(255)   DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_payroll`  (`payroll_id`),
  KEY `idx_employee` (`employee_id`),
  CONSTRAINT `fk_pitem_payroll`   FOREIGN KEY (`payroll_id`)  REFERENCES `payroll`(`id`)    ON DELETE CASCADE,
  CONSTRAINT `fk_pitem_employee`  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Loans / Credit Facilities ─────────────────────────────────
CREATE TABLE IF NOT EXISTS `loans` (
  `id`             INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `ref`            VARCHAR(20)    NOT NULL UNIQUE,          -- LN-2026-0001
  `lender`         VARCHAR(120)   NOT NULL,                 -- bank / person
  `loan_type`      ENUM('bank_loan','overdraft','personal','equipment','other') NOT NULL DEFAULT 'bank_loan',
  `principal`      DECIMAL(14,2)  NOT NULL,                 -- amount borrowed
  `interest_rate`  DECIMAL(5,2)   NOT NULL DEFAULT 0.00,    -- % per annum
  `disbursement_date` DATE        NOT NULL,
  `due_date`       DATE           DEFAULT NULL,
  `installment`    DECIMAL(12,2)  NOT NULL DEFAULT 0.00,    -- monthly installment
  `amount_repaid`  DECIMAL(14,2)  NOT NULL DEFAULT 0.00,
  `status`         ENUM('active','fully_paid','defaulted','written_off') NOT NULL DEFAULT 'active',
  `notes`          TEXT           DEFAULT NULL,
  `created_by`     INT UNSIGNED   DEFAULT NULL,
  `created_at`     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `loan_repayments` (
  `id`           INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `loan_id`      INT UNSIGNED   NOT NULL,
  `amount`       DECIMAL(14,2)  NOT NULL,
  `principal`    DECIMAL(14,2)  NOT NULL DEFAULT 0.00,      -- principal component
  `interest`     DECIMAL(14,2)  NOT NULL DEFAULT 0.00,      -- interest component
  `method`       ENUM('cash','bank_transfer','mobile_money','card','cheque') NOT NULL DEFAULT 'bank_transfer',
  `reference`    VARCHAR(100)   DEFAULT NULL,
  `payment_date` DATE           NOT NULL,
  `notes`        TEXT           DEFAULT NULL,
  `recorded_by`  INT UNSIGNED   DEFAULT NULL,
  `created_at`   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_loan` (`loan_id`),
  CONSTRAINT `fk_lrep_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Statutory Obligations (URA / NSSF monthly filings) ────────
CREATE TABLE IF NOT EXISTS `statutory_obligations` (
  `id`            INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  `type`          ENUM('paye','vat','withholding_tax','nssf','local_service_tax','other') NOT NULL,
  `period_month`  TINYINT UNSIGNED NOT NULL,               -- 1-12
  `period_year`   YEAR           NOT NULL,
  `amount_due`    DECIMAL(14,2)  NOT NULL DEFAULT 0.00,
  `amount_paid`   DECIMAL(14,2)  NOT NULL DEFAULT 0.00,
  `due_date`      DATE           DEFAULT NULL,
  `paid_date`     DATE           DEFAULT NULL,
  `reference`     VARCHAR(100)   DEFAULT NULL,             -- PRN / payment ref
  `status`        ENUM('pending','paid','partial','overdue') NOT NULL DEFAULT 'pending',
  `payroll_id`    INT UNSIGNED   DEFAULT NULL,             -- link to payroll run for PAYE/NSSF
  `notes`         TEXT           DEFAULT NULL,
  `created_by`    INT UNSIGNED   DEFAULT NULL,
  `created_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type_period` (`type`, `period_year`, `period_month`),
  KEY `idx_status`      (`status`),
  CONSTRAINT `fk_stat_payroll` FOREIGN KEY (`payroll_id`) REFERENCES `payroll`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Seed document sequences for new prefixes ─────────────────
INSERT IGNORE INTO `doc_sequences` (`prefix`, `year`, `last_number`) VALUES
  ('SUP',  YEAR(CURDATE()), 0),
  ('PUR',  YEAR(CURDATE()), 0),
  ('EXP',  YEAR(CURDATE()), 0),
  ('LN',   YEAR(CURDATE()), 0),
  ('PAY',  YEAR(CURDATE()), 0),
  ('EMP',  YEAR(CURDATE()), 0);
