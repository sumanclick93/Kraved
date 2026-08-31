<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use Throwable;

final class Schema
{
    public static function ensure(): void
    {
        try {
            $db = Database::getInstance();
            if (!$db->query("SHOW TABLES LIKE 'products'")->fetchColumn()) {
                return;
            }
            self::createProductOffers($db);
            self::createPromoCodes($db);
            self::createPromoCodeProducts($db);
            self::createProductImages($db);
            self::createProductVariants($db);
            self::alterOrders($db);
        } catch (Throwable) {
            // Never block the storefront if a migration cannot run.
        }
    }

    private static function createProductOffers(PDO $db): void
    {
        if ($db->query("SHOW TABLES LIKE 'product_offers'")->fetchColumn()) {
            return;
        }
        $db->exec(
            'CREATE TABLE `product_offers` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `product_id` INT UNSIGNED NOT NULL,
              `title` VARCHAR(120) NOT NULL,
              `discount_type` ENUM(\'percent\',\'fixed\') NOT NULL DEFAULT \'percent\',
              `discount_value` DECIMAL(10,2) NOT NULL,
              `starts_at` DATETIME DEFAULT NULL,
              `ends_at` DATETIME DEFAULT NULL,
              `status` TINYINT(1) NOT NULL DEFAULT 1,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_product_offers_product` (`product_id`, `status`),
              CONSTRAINT `fk_product_offers_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private static function createPromoCodes(PDO $db): void
    {
        if ($db->query("SHOW TABLES LIKE 'promo_codes'")->fetchColumn()) {
            return;
        }
        $db->exec(
            'CREATE TABLE `promo_codes` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `code` VARCHAR(40) NOT NULL,
              `title` VARCHAR(120) NOT NULL,
              `discount_type` ENUM(\'percent\',\'fixed\') NOT NULL DEFAULT \'percent\',
              `discount_value` DECIMAL(10,2) NOT NULL,
              `min_order` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
              `max_uses` INT UNSIGNED DEFAULT NULL,
              `used_count` INT UNSIGNED NOT NULL DEFAULT 0,
              `starts_at` DATETIME DEFAULT NULL,
              `ends_at` DATETIME DEFAULT NULL,
              `status` TINYINT(1) NOT NULL DEFAULT 1,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_promo_codes_code` (`code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private static function createPromoCodeProducts(PDO $db): void
    {
        if ($db->query("SHOW TABLES LIKE 'promo_code_products'")->fetchColumn()) {
            return;
        }
        $db->exec(
            'CREATE TABLE `promo_code_products` (
              `promo_id` INT UNSIGNED NOT NULL,
              `product_id` INT UNSIGNED NOT NULL,
              PRIMARY KEY (`promo_id`, `product_id`),
              KEY `idx_pcp_product` (`product_id`),
              CONSTRAINT `fk_pcp_promo` FOREIGN KEY (`promo_id`) REFERENCES `promo_codes` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
              CONSTRAINT `fk_pcp_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private static function createProductImages(PDO $db): void
    {
        if ($db->query("SHOW TABLES LIKE 'product_images'")->fetchColumn()) {
            return;
        }
        $db->exec(
            'CREATE TABLE `product_images` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `product_id` INT UNSIGNED NOT NULL,
              `image_path` VARCHAR(255) NOT NULL,
              `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
              `display_order` INT NOT NULL DEFAULT 0,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_product_images_product` (`product_id`, `display_order`),
              CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private static function createProductVariants(PDO $db): void
    {
        if ($db->query("SHOW TABLES LIKE 'product_variants'")->fetchColumn()) {
            return;
        }
        $db->exec(
            'CREATE TABLE `product_variants` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `product_id` INT UNSIGNED NOT NULL,
              `label` VARCHAR(80) NOT NULL,
              `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
              `stock_qty` INT NOT NULL DEFAULT 0,
              `sku` VARCHAR(60) DEFAULT NULL,
              `status` TINYINT(1) NOT NULL DEFAULT 1,
              `display_order` INT NOT NULL DEFAULT 0,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_product_variants_product` (`product_id`, `status`, `display_order`),
              CONSTRAINT `fk_product_variants_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private static function alterOrders(PDO $db): void
    {
        if (!$db->query("SHOW TABLES LIKE 'orders'")->fetchColumn()) {
            return;
        }
        $cols = $db->query('SHOW COLUMNS FROM `orders`')->fetchAll();
        $names = array_column($cols, 'Field');
        if (!in_array('discount_amount', $names, true)) {
            $db->exec('ALTER TABLE `orders` ADD `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `delivery_fee`');
        }
        if (!in_array('promo_code', $names, true)) {
            $db->exec('ALTER TABLE `orders` ADD `promo_code` VARCHAR(40) DEFAULT NULL AFTER `discount_amount`');
        }
        if (!in_array('promo_id', $names, true)) {
            $db->exec('ALTER TABLE `orders` ADD `promo_id` INT UNSIGNED DEFAULT NULL AFTER `promo_code`');
        }
        if (!in_array('stripe_session_id', $names, true)) {
            $db->exec('ALTER TABLE `orders` ADD `stripe_session_id` VARCHAR(255) DEFAULT NULL AFTER `payment_method`');
        }
        if (!in_array('stripe_payment_intent', $names, true)) {
            $db->exec('ALTER TABLE `orders` ADD `stripe_payment_intent` VARCHAR(255) DEFAULT NULL AFTER `stripe_session_id`');
        }
        if (!in_array('placed_at', $names, true)) {
            $db->exec('ALTER TABLE `orders` ADD `placed_at` DATETIME DEFAULT NULL AFTER `status_updated_at`');
            $db->exec(
                "UPDATE `orders`
                 SET `placed_at` = `created_at`
                 WHERE `placed_at` IS NULL
                   AND NOT (`payment_method` IN ('card','stripe') AND `payment_status` IN ('pending','failed'))"
            );
        }
        self::widenOrderEnums($db, $cols);
    }

    /** @param list<array<string, mixed>> $cols */
    private static function widenOrderEnums(PDO $db, array $cols): void
    {
        $byField = [];
        foreach ($cols as $col) {
            $byField[(string) ($col['Field'] ?? '')] = $col;
        }
        $paymentStatus = strtolower((string) ($byField['payment_status']['Type'] ?? ''));
        if ($paymentStatus !== '' && !str_contains($paymentStatus, "'failed'")) {
            $db->exec("ALTER TABLE `orders` MODIFY `payment_status` ENUM('pending','paid','cod','failed') NOT NULL DEFAULT 'pending'");
        }
        $paymentMethod = strtolower((string) ($byField['payment_method']['Type'] ?? ''));
        if ($paymentMethod !== '' && !str_contains($paymentMethod, "'stripe'")) {
            $db->exec("ALTER TABLE `orders` MODIFY `payment_method` ENUM('cod','card','stripe') NOT NULL DEFAULT 'cod'");
        }
    }
}
