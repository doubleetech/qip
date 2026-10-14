-- ============================================================
-- QIP — Quote. Invoice. Payment.
-- Phase 1 Schema: Users, Business Profiles, Currencies
-- Database: qip_db
-- ============================================================

CREATE DATABASE IF NOT EXISTS `qip_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `qip_db`;

-- ------------------------------------------------------------
-- Table: currencies
-- Master list of ISO 4217 currencies with symbols
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `currencies` (
  `code`        CHAR(3)      NOT NULL,
  `name`        VARCHAR(100) NOT NULL,
  `symbol`      VARCHAR(10)  NOT NULL,
  `decimal_places` TINYINT UNSIGNED NOT NULL DEFAULT 2,
  PRIMARY KEY (`code`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: users
-- One row per authenticated Google user
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `google_id`  VARCHAR(255) NOT NULL,
  `email`      VARCHAR(255) NOT NULL,
  `name`       VARCHAR(255) NOT NULL,
  `avatar_url` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_google_id` (`google_id`),
  UNIQUE KEY `uniq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: business_profiles
-- One workspace per user (1:1 with users)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `business_profiles` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`          INT UNSIGNED NOT NULL,
  `business_name`    VARCHAR(255) NOT NULL,
  `business_email`   VARCHAR(255) NOT NULL,
  `phone`            VARCHAR(50)  DEFAULT NULL,
  `address`          TEXT         DEFAULT NULL,
  `default_currency` CHAR(3)      NOT NULL DEFAULT 'USD',
  `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_id` (`user_id`),
  KEY `idx_currency` (`default_currency`),
  CONSTRAINT `fk_bp_user`
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bp_currency`
    FOREIGN KEY (`default_currency`) REFERENCES `currencies`(`code`)
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Seed: currencies (~50 common)
-- ------------------------------------------------------------
INSERT INTO `currencies` (`code`, `name`, `symbol`, `decimal_places`) VALUES
('USD','United States Dollar','$',2),
('EUR','Euro','€',2),
('GBP','British Pound Sterling','£',2),
('NGN','Nigerian Naira','₦',2),
('KES','Kenyan Shilling','KSh',2),
('GHS','Ghanaian Cedi','₵',2),
('ZAR','South African Rand','R',2),
('EGP','Egyptian Pound','E£',2),
('MAD','Moroccan Dirham','د.م.',2),
('TND','Tunisian Dinar','د.ت',3),
('INR','Indian Rupee','₹',2),
('PKR','Pakistani Rupee','₨',2),
('BDT','Bangladeshi Taka','৳',2),
('JPY','Japanese Yen','¥',0),
('CNY','Chinese Yuan','¥',2),
('KRW','South Korean Won','₩',0),
('SGD','Singapore Dollar','S$',2),
('HKD','Hong Kong Dollar','HK$',2),
('MYR','Malaysian Ringgit','RM',2),
('IDR','Indonesian Rupiah','Rp',2),
('PHP','Philippine Peso','₱',2),
('THB','Thai Baht','฿',2),
('VND','Vietnamese Dong','₫',0),
('AED','UAE Dirham','د.إ',2),
('SAR','Saudi Riyal','﷼',2),
('QAR','Qatari Riyal','﷼',2),
('ILS','Israeli New Shekel','₪',2),
('TRY','Turkish Lira','₺',2),
('RUB','Russian Ruble','₽',2),
('CAD','Canadian Dollar','C$',2),
('MXN','Mexican Peso','$',2),
('BRL','Brazilian Real','R$',2),
('ARS','Argentine Peso','$',2),
('CLP','Chilean Peso','$',0),
('COP','Colombian Peso','$',2),
('AUD','Australian Dollar','A$',2),
('NZD','New Zealand Dollar','NZ$',2),
('CHF','Swiss Franc','CHF',2),
('SEK','Swedish Krona','kr',2),
('NOK','Norwegian Krone','kr',2),
('DKK','Danish Krone','kr',2),
('PLN','Polish Zloty','zł',2),
('CZK','Czech Koruna','Kč',2),
('HUF','Hungarian Forint','Ft',2),
('RON','Romanian Leu','lei',2),
('UAH','Ukrainian Hryvnia','₴',2),
('BGN','Bulgarian Lev','лв',2),
('HRK','Croatian Kuna','kn',2),
('ISK','Icelandic Krona','kr',0),
('BTC','Bitcoin','₿',8);

-- ------------------------------------------------------------
-- Sanity check
-- ------------------------------------------------------------
-- SELECT COUNT(*) FROM currencies;  -- should return 50