-- SQL Script for Partner Product System
-- Execute these statements to add partner product support

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

-- 2. Add category column to invoice table (if it doesn't exist)
-- Check if column exists first, then add if needed
SET @dbname = DATABASE();
SET @tablename = 'invoice';
SET @columnname = 'category';
SET @preparedStatement = (SELECT IF(
    (
        SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
        WHERE
            (TABLE_SCHEMA = @dbname)
            AND (TABLE_NAME = @tablename)
            AND (COLUMN_NAME = @columnname)
    ) > 0,
    'SELECT 1',
    CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' VARCHAR(400) NULL AFTER name_product')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Alternative simple method (if the above doesn't work, use this):
-- ALTER TABLE `invoice` ADD COLUMN IF NOT EXISTS `category` VARCHAR(400) NULL AFTER `name_product`;

-- Note: If your MySQL version doesn't support IF NOT EXISTS for ALTER TABLE,
-- you can manually check and run:
-- ALTER TABLE `invoice` ADD COLUMN `category` VARCHAR(400) NULL AFTER `name_product`;

