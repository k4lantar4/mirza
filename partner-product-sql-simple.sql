-- Simple SQL Script for Partner Product System
-- Use this if the conditional ALTER TABLE doesn't work

-- 1. Create partner_product table
CREATE TABLE IF NOT EXISTS `partner_product` (
    `id` INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `bot_token` VARCHAR(500) NOT NULL,
    `code_product` varchar(200) NULL,
    `name_product` varchar(2000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
    `price_product` varchar(2000) NULL,
    `Volume_constraint` varchar(2000) NULL,
    `Location` varchar(200) NULL,
    `Service_time` varchar(200) NULL,
    `note` TEXT NULL,
    `data_limit_reset` varchar(200) NULL DEFAULT 'no_reset',
    `one_buy_status` varchar(20) NOT NULL DEFAULT '0',
    `inbounds` TEXT NULL,
    `proxies` TEXT NULL,
    `category` varchar(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
    `hide_panel` TEXT NOT NULL DEFAULT '{}',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_bot_token` (`bot_token`),
    INDEX `idx_code_product` (`code_product`),
    INDEX `idx_category` (`category`),
    INDEX `idx_location` (`Location`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Add category column to invoice table
-- Run this manually if column doesn't exist (check first with: SHOW COLUMNS FROM invoice LIKE 'category')
ALTER TABLE `invoice` ADD COLUMN `category` VARCHAR(400) NULL AFTER `name_product`;

