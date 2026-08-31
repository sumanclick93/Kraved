-- =============================================================================
-- Kraved — product gallery images + size/weight variants
-- Run on existing databases (safe / idempotent)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `product_images` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id`    INT UNSIGNED NOT NULL,
  `image_path`    VARCHAR(255) NOT NULL,
  `is_primary`    TINYINT(1) NOT NULL DEFAULT 0,
  `display_order` INT NOT NULL DEFAULT 0,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_images_product` (`product_id`, `display_order`),
  CONSTRAINT `fk_product_images_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_variants` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id`    INT UNSIGNED NOT NULL,
  `label`         VARCHAR(80) NOT NULL COMMENT 'e.g. 100g, 250g, Box of 6',
  `price`         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `stock_qty`     INT NOT NULL DEFAULT 0,
  `sku`           VARCHAR(60) DEFAULT NULL,
  `status`        TINYINT(1) NOT NULL DEFAULT 1,
  `display_order` INT NOT NULL DEFAULT 0,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_variants_product` (`product_id`, `status`, `display_order`),
  CONSTRAINT `fk_product_variants_product`
    FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backfill cover images into gallery (skip if already present)
INSERT INTO `product_images` (`product_id`, `image_path`, `is_primary`, `display_order`)
SELECT p.`id`, p.`image`, 1, 0
FROM `products` p
WHERE p.`image` IS NOT NULL
  AND p.`image` <> ''
  AND NOT EXISTS (
    SELECT 1 FROM `product_images` pi
    WHERE pi.`product_id` = p.`id` AND pi.`image_path` = p.`image`
  );
