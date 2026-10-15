-- QIP Phase 2: clients, quotes, invoices
-- Incremental migration. Safe to re-run.

USE `qip_db`;

-- Clients
CREATE TABLE IF NOT EXISTS `clients` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id`  INT UNSIGNED NOT NULL,
  `name`         VARCHAR(255) NOT NULL,
  `company`      VARCHAR(255) DEFAULT NULL,
  `email`        VARCHAR(255) DEFAULT NULL,
  `phone`        VARCHAR(50)  DEFAULT NULL,
  `address`      TEXT         DEFAULT NULL,
  `notes`        TEXT         DEFAULT NULL,
  `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_biz_name` (`business_id`, `name`),
  KEY `idx_biz_email` (`business_id`, `email`),
  CONSTRAINT `fk_clients_biz`
    FOREIGN KEY (`business_id`) REFERENCES `business_profiles`(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quotes
CREATE TABLE IF NOT EXISTS `quotes` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id`    INT UNSIGNED NOT NULL,
  `client_id`      INT UNSIGNED NOT NULL,
  `number`         VARCHAR(32)  NOT NULL,
  `status`         ENUM('draft','sent','accepted','rejected','expired') NOT NULL DEFAULT 'draft',
  `issue_date`     DATE NOT NULL,
  `valid_until`    DATE DEFAULT NULL,
  `currency`       CHAR(3) NOT NULL,
  `subtotal`       DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `discount_type`  ENUM('none','percent','fixed') NOT NULL DEFAULT 'none',
  `discount_value` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `tax_rate`       DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
  `tax_amount`     DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total`          DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `notes`          TEXT DEFAULT NULL,
  `terms`          TEXT DEFAULT NULL,
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_biz_number` (`business_id`, `number`),
  KEY `idx_biz_status` (`business_id`, `status`),
  KEY `idx_client` (`client_id`),
  CONSTRAINT `fk_quotes_biz`
    FOREIGN KEY (`business_id`) REFERENCES `business_profiles`(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_quotes_client`
    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_quotes_currency`
    FOREIGN KEY (`currency`) REFERENCES `currencies`(`code`)
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quote line items
CREATE TABLE IF NOT EXISTS `quote_items` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `quote_id`    INT UNSIGNED NOT NULL,
  `position`    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `description` VARCHAR(500) NOT NULL,
  `quantity`    DECIMAL(15,2) NOT NULL DEFAULT 1.00,
  `unit_price`  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `line_total`  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_quote_pos` (`quote_id`, `position`),
  CONSTRAINT `fk_qi_quote`
    FOREIGN KEY (`quote_id`) REFERENCES `quotes`(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Invoices
CREATE TABLE IF NOT EXISTS `invoices` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id`      INT UNSIGNED NOT NULL,
  `client_id`        INT UNSIGNED NOT NULL,
  `source_quote_id`  INT UNSIGNED DEFAULT NULL,
  `number`           VARCHAR(32)  NOT NULL,
  `status`           ENUM('draft','sent','pending','cancelled') NOT NULL DEFAULT 'draft',
  `issue_date`       DATE NOT NULL,
  `due_date`         DATE DEFAULT NULL,
  `currency`         CHAR(3) NOT NULL,
  `subtotal`         DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `discount_type`    ENUM('none','percent','fixed') NOT NULL DEFAULT 'none',
  `discount_value`   DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `discount_amount`  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `tax_rate`         DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
  `tax_amount`       DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total`            DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `notes`            TEXT DEFAULT NULL,
  `payment_terms`    TEXT DEFAULT NULL,
  `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_biz_number` (`business_id`, `number`),
  UNIQUE KEY `uniq_source_quote` (`source_quote_id`),
  KEY `idx_biz_status` (`business_id`, `status`),
  KEY `idx_client` (`client_id`),
  CONSTRAINT `fk_inv_biz`
    FOREIGN KEY (`business_id`) REFERENCES `business_profiles`(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_inv_client`
    FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_inv_quote`
    FOREIGN KEY (`source_quote_id`) REFERENCES `quotes`(`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_inv_currency`
    FOREIGN KEY (`currency`) REFERENCES `currencies`(`code`)
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Invoice line items
CREATE TABLE IF NOT EXISTS `invoice_items` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id`  INT UNSIGNED NOT NULL,
  `position`    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `description` VARCHAR(500) NOT NULL,
  `quantity`    DECIMAL(15,2) NOT NULL DEFAULT 1.00,
  `unit_price`  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `line_total`  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_inv_pos` (`invoice_id`, `position`),
  CONSTRAINT `fk_ii_inv`
    FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;