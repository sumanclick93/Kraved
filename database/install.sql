-- Kraved tables only (no CREATE DATABASE / USE) — for shared hosting
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION';

DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `wishlists`;
DROP TABLE IF EXISTS `promo_code_products`;
DROP TABLE IF EXISTS `promo_codes`;
DROP TABLE IF EXISTS `product_offers`;
DROP TABLE IF EXISTS `product_addon_groups`;
DROP TABLE IF EXISTS `box_deal_products`;
DROP TABLE IF EXISTS `product_images`;
DROP TABLE IF EXISTS `product_variants`;
DROP TABLE IF EXISTS `addons`;
DROP TABLE IF EXISTS `addon_groups`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `sub_categories`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `delivery_zones`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `site_sections`;

CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
  `address` VARCHAR(255) DEFAULT NULL,
  `postcode` VARCHAR(20) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `slug` VARCHAR(140) NOT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `display_order` INT NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`),
  KEY `idx_categories_order_status` (`display_order`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sub_categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `slug` VARCHAR(140) NOT NULL,
  `display_order` INT NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sub_categories_slug` (`slug`),
  KEY `idx_sub_categories_category` (`category_id`, `display_order`, `status`),
  CONSTRAINT `fk_sub_categories_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `addon_groups` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(120) NOT NULL,
  `min_selection` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `max_selection` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `is_required` TINYINT(1) NOT NULL DEFAULT 0,
  `display_order` INT NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_addon_groups_status` (`status`, `display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `addons` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `display_order` INT NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_addons_group` (`group_id`, `display_order`, `status`),
  CONSTRAINT `fk_addons_group` FOREIGN KEY (`group_id`) REFERENCES `addon_groups` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `sub_category_id` INT UNSIGNED DEFAULT NULL,
  `title` VARCHAR(180) NOT NULL,
  `slug` VARCHAR(200) NOT NULL,
  `short_description` VARCHAR(255) DEFAULT NULL,
  `full_description` TEXT DEFAULT NULL,
  `base_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `stock_qty` INT NOT NULL DEFAULT 0,
  `weight_label` VARCHAR(40) DEFAULT NULL,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_box_deal` TINYINT(1) NOT NULL DEFAULT 0,
  `box_max_items` TINYINT UNSIGNED DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `display_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_slug` (`slug`),
  KEY `idx_products_category` (`category_id`, `status`, `display_order`),
  KEY `idx_products_sub_category` (`sub_category_id`),
  KEY `idx_products_featured` (`is_featured`, `status`),
  KEY `idx_products_box_deal` (`is_box_deal`, `status`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_products_sub_category` FOREIGN KEY (`sub_category_id`) REFERENCES `sub_categories` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_offers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(120) NOT NULL,
  `discount_type` ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  `discount_value` DECIMAL(10,2) NOT NULL,
  `starts_at` DATETIME DEFAULT NULL,
  `ends_at` DATETIME DEFAULT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_offers_product` (`product_id`, `status`),
  CONSTRAINT `fk_product_offers_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `promo_codes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(40) NOT NULL,
  `title` VARCHAR(120) NOT NULL,
  `discount_type` ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `promo_code_products` (
  `promo_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`promo_id`, `product_id`),
  KEY `idx_pcp_product` (`product_id`),
  CONSTRAINT `fk_pcp_promo` FOREIGN KEY (`promo_id`) REFERENCES `promo_codes` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_pcp_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `display_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_images_product` (`product_id`, `display_order`),
  CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_variants` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_addon_groups` (
  `product_id` INT UNSIGNED NOT NULL,
  `group_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`product_id`, `group_id`),
  KEY `idx_pag_group` (`group_id`),
  CONSTRAINT `fk_pag_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_pag_group` FOREIGN KEY (`group_id`) REFERENCES `addon_groups` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `box_deal_products` (
  `box_product_id` INT UNSIGNED NOT NULL,
  `choice_product_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`box_product_id`, `choice_product_id`),
  KEY `idx_bdp_choice` (`choice_product_id`),
  CONSTRAINT `fk_bdp_box` FOREIGN KEY (`box_product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_bdp_choice` FOREIGN KEY (`choice_product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `delivery_zones` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `postcode_prefix` VARCHAR(10) NOT NULL,
  `delivery_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `min_order_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `estimated_mins` SMALLINT UNSIGNED DEFAULT 45,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_delivery_zones_prefix` (`postcode_prefix`),
  KEY `idx_delivery_zones_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_number` VARCHAR(32) NOT NULL,
  `customer_id` INT UNSIGNED DEFAULT NULL,
  `fulfillment_type` ENUM('delivery', 'collection') NOT NULL DEFAULT 'collection',
  `customer_name` VARCHAR(120) NOT NULL,
  `customer_email` VARCHAR(190) NOT NULL,
  `customer_phone` VARCHAR(30) NOT NULL,
  `delivery_address` VARCHAR(255) DEFAULT NULL,
  `postcode` VARCHAR(20) DEFAULT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `delivery_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `promo_code` VARCHAR(40) DEFAULT NULL,
  `promo_id` INT UNSIGNED DEFAULT NULL,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` ENUM('pending', 'paid', 'cod', 'failed') NOT NULL DEFAULT 'pending',
  `payment_method` ENUM('cod', 'card', 'stripe') NOT NULL DEFAULT 'cod',
  `stripe_session_id` VARCHAR(255) DEFAULT NULL,
  `stripe_payment_intent` VARCHAR(255) DEFAULT NULL,
  `order_status` ENUM('received','baking','ready','out_for_delivery','completed','cancelled') NOT NULL DEFAULT 'received',
  `delivery_time_slot` VARCHAR(80) DEFAULT NULL,
  `scheduled_at` DATETIME DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `status_updated_at` DATETIME DEFAULT NULL,
  `placed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_orders_number` (`order_number`),
  KEY `idx_orders_customer` (`customer_id`),
  KEY `idx_orders_status` (`order_status`, `created_at`),
  KEY `idx_orders_created` (`created_at`),
  KEY `idx_orders_payment` (`payment_status`),
  CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `order_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED DEFAULT NULL,
  `product_name` VARCHAR(180) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `quantity` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `addons_json` JSON DEFAULT NULL,
  `total_item_price` DECIMAL(10,2) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`),
  KEY `idx_order_items_product` (`product_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `wishlists` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wishlist_user_product` (`user_id`, `product_id`),
  KEY `idx_wishlist_user` (`user_id`),
  CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_wishlist_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `site_sections` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `section_key` VARCHAR(60) NOT NULL,
  `label` VARCHAR(120) NOT NULL,
  `content_json` LONGTEXT NOT NULL,
  `is_visible` TINYINT(1) NOT NULL DEFAULT 1,
  `display_order` INT NOT NULL DEFAULT 0,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_site_sections_key` (`section_key`),
  KEY `idx_site_sections_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO `users` (`name`, `email`, `phone`, `password_hash`, `role`, `address`, `postcode`) VALUES
('Kraved Admin', 'admin@kraved.local', '07000000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '12 Baker Street, London', 'W1U 3BW');

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('store_name', 'Kraved'),
('store_tagline', 'Taste the sweetness in every bite.'),
('currency_symbol', '£'),
('free_delivery_threshold', '25.00'),
('collection_address', 'Unit 2, Old Biscuit Factory, Dockray Street, Colne, BB8 9HT'),
('asap_prep_mins', '35'),
('order_prefix', 'KRV'),
('contact_email', 'hello@kraveddesserts.com'),
('contact_phone', '01282 943888'),
('contact_hours', 'Mon–Fri 5pm to 11pm · Sat & Sun 2pm to 11pm'),
('stripe_enabled', '0'),
('stripe_mode', 'sandbox');

INSERT INTO `site_sections` (`section_key`, `label`, `content_json`, `is_visible`, `display_order`) VALUES
('nav', 'Header Navigation', '{"links":[{"label":"Home","href":"/"},{"label":"Menu","href":"/#menu"},{"label":"Best Sellers","href":"/#popular"},{"label":"Offers","href":"/#offers"},{"label":"About Us","href":"/#about"},{"label":"Contact","href":"/#contact"}]}', 1, 10),
('hero', 'Hero Section', '{"headline_line1":"Desserts That","headline_line2":"Crave You Back.","subhead":"Taste the sweetness in every bite. Handcrafted in the heart of Colne.","image":"images/hero-dessert.png","image_alt":"Chocolate brownie stack with ice cream and dripping sauce","badge_html":"Based in<br>Colne","default_location":"Colne, UK","outlets_text":"Collection & delivery from our Colne kitchen","delivery_title":"Order Delivery","delivery_subtitle":"From our Colne kitchen","takeaway_title":"Collection","takeaway_subtitle":"Collect in store"}', 1, 20),
('popular', 'Popular Right Now', '{"title":"Popular Right Now","view_all_label":"View All","view_all_href":"#menu","limit":8}', 1, 40),
('about', 'About Banner', '{"eyebrow":"About Us","title":"Feed the Kraving","copy":"Kraved started the way most good things do, with a craving that just wouldn\'t quit.","copy_extra":"Based in the heart of Colne, built on the belief that a little indulgence, done properly, can turn an ordinary day into a good one.","quote":"Feed the Kraving","mission":"Our mission is simple, to make every moment sweet and memorable.","cta_label":"Order Online","cta_href":"#menu"}', 1, 60),
('trust_bar', 'Trust / Offers Bar', '{"chips":["Rich Chocolate Bliss","Made with Real Goodness","Sweets That Make Life Sweeter","Taste the Sweetness in Every Bite","Handcrafted in Colne"]}', 1, 70),
('menu', 'Full Menu Intro', '{"title":"Full Menu","lead":"Milkshakes, cookie dough, waffles, pancakes and puddings — handcrafted in Colne."}', 1, 80),
('footer', 'Footer', '{"mission":"Our mission is simple, to make every moment sweet and memorable.","tagline":"Taste the sweetness in every bite.","newsletter_title":"Stay Updated","newsletter_text":"Subscribe for exclusive offers & updates","copyright":"Kraved Desserts. All rights reserved.","made_in":"Made with ♥ in Colne","quick_links":[{"label":"Menu","href":"/#menu"},{"label":"Best Sellers","href":"/#popular"},{"label":"Offers","href":"/#offers"},{"label":"About Us","href":"/#about"},{"label":"Contact","href":"/#contact"}],"info_links":[{"label":"Delivery Info","href":"#fulfillment"},{"label":"FAQs","href":"#"},{"label":"Terms & Conditions","href":"#"},{"label":"Privacy Policy","href":"#"}],"social":[{"label":"IG","url":"#","aria":"Instagram"},{"label":"FB","url":"#","aria":"Facebook"},{"label":"TT","url":"#","aria":"TikTok"},{"label":"X","url":"#","aria":"X"}]}', 1, 90);

INSERT INTO `categories` (`name`, `slug`, `display_order`, `status`) VALUES
('Milkshakes', 'milkshakes', 1, 1),
('Cookie Dough', 'cookie-dough', 2, 1),
('Waffles', 'waffles', 3, 1),
('Classic Shakes', 'classic-shakes', 4, 1),
('Mini Pancakes', 'mini-pancakes', 5, 1),
('Cups', 'cups', 6, 1),
('Puddings', 'puddings', 7, 1),
('Matilda Cake', 'matilda-cake', 8, 1),
('Cheesecakes', 'cheesecakes', 9, 1);

INSERT INTO `sub_categories` (`category_id`, `name`, `slug`, `display_order`, `status`) VALUES
(1, 'Kraved Shakes', 'kraved-shakes', 1, 1),
(1, 'Create Your Own', 'create-your-own-shake', 2, 1);

INSERT INTO `delivery_zones` (`postcode_prefix`, `delivery_fee`, `min_order_amount`, `estimated_mins`, `status`) VALUES
('BB8',  2.49, 12.00, 30, 1),
('BB9',  2.99, 12.00, 35, 1),
('BB10', 3.49, 15.00, 40, 1),
('BB11', 3.49, 15.00, 40, 1),
('BB12', 3.99, 15.00, 45, 1);
