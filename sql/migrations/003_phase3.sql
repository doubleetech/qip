-- QIP Phase 3: payments, expenses, business payment instructions
-- Incremental migration. Safe to re-run.

USE `qip_db`;

-- Payments received against invoices.
-- receipt_number is assigned at insert time and unique per business.
CREATE TABLE IF NOT EXISTS `payments` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id`    INT UNSIGNED NOT NULL,
  `invoice_id`     INT UNSIGNED NOT NULL,
  `amount`         DECIMAL(15,2) NOT NULL,
  `payment_date`   DATE NOT NULL,
  `method`         ENUM('bank_transfer','cash','card','payment_link','crypto','other') NOT NULL DEFAULT 'bank_transfer',
  `reference`      VARCHAR(255) DEFAULT NULL,
  `notes`          TEXT DEFAULT NULL,
  `receipt_number` VARCHAR(32) DEFAULT NULL,
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_biz_receipt` (`business_id`, `receipt_number`),
  KEY `idx_biz_date` (`business_id`, `payment_date`),
  KEY `idx_invoice` (`invoice_id`),
  CONSTRAINT `fk_pay_biz`
    FOREIGN KEY (`business_id`) REFERENCES `business_profiles`(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pay_inv`
    FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Manual business expenses.
CREATE TABLE IF NOT EXISTS `expenses` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id`  INT UNSIGNED NOT NULL,
  `description`  VARCHAR(500) NOT NULL,
  `category`     ENUM('advertising','software','equipment','transport','office','rent','services','other') NOT NULL DEFAULT 'other',
  `amount`       DECIMAL(15,2) NOT NULL,
  `expense_date` DATE NOT NULL,
  `method`       VARCHAR(50)  DEFAULT NULL,
  `reference`    VARCHAR(255) DEFAULT NULL,
  `notes`        TEXT DEFAULT NULL,
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_biz_date` (`business_id`, `expense_date`),
  KEY `idx_biz_cat` (`business_id`, `category`),
  CONSTRAINT `fk_exp_biz`
    FOREIGN KEY (`business_id`) REFERENCES `business_profiles`(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment instructions on the business profile.
-- crypto_qr_path is reserved for a later phase; no upload handling yet.
ALTER TABLE `business_profiles`
  ADD COLUMN `bank_name`             VARCHAR(255) DEFAULT NULL AFTER `address`,
  ADD COLUMN `bank_account_name`     VARCHAR(255) DEFAULT NULL AFTER `bank_name`,
  ADD COLUMN `bank_account_number`   VARCHAR(64)  DEFAULT NULL AFTER `bank_account_name`,
  ADD COLUMN `bank_instructions`     TEXT         DEFAULT NULL AFTER `bank_account_number`,
  ADD COLUMN `payment_url`           VARCHAR(500) DEFAULT NULL AFTER `bank_instructions`,
  ADD COLUMN `crypto_network`        VARCHAR(100) DEFAULT NULL AFTER `payment_url`,
  ADD COLUMN `crypto_address`        VARCHAR(255) DEFAULT NULL AFTER `crypto_network`,
  ADD COLUMN `crypto_qr_path`        VARCHAR(255) DEFAULT NULL AFTER `crypto_address`;