-- =============================================================================
-- Kraved CMS alteration — run on existing databases (safe / idempotent)
-- Adds site_sections + extra store settings for admin-managed homepage content
-- =============================================================================

CREATE TABLE IF NOT EXISTS `site_sections` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `section_key`   VARCHAR(60)  NOT NULL,
  `label`         VARCHAR(120) NOT NULL,
  `content_json`  LONGTEXT     NOT NULL,
  `is_visible`    TINYINT(1)   NOT NULL DEFAULT 1,
  `display_order` INT          NOT NULL DEFAULT 0,
  `updated_at`    DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_site_sections_key` (`section_key`),
  KEY `idx_site_sections_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('contact_email', 'hello@kraveddesserts.com'),
('contact_phone', '01282 943888'),
('contact_hours', 'Mon–Fri 5pm to 11pm · Sat & Sun 2pm to 11pm');

INSERT IGNORE INTO `site_sections` (`section_key`, `label`, `content_json`, `is_visible`, `display_order`) VALUES
('nav', 'Header Navigation', '{"links":[{"label":"Home","href":"/"},{"label":"Menu","href":"/#menu"},{"label":"Best Sellers","href":"/#popular"},{"label":"Offers","href":"/#offers"},{"label":"About Us","href":"/#about"},{"label":"Contact","href":"/#contact"}]}', 1, 10),
('hero', 'Hero Section', '{"headline_line1":"Desserts That","headline_line2":"Crave You Back.","subhead":"Taste the sweetness in every bite. Handcrafted in the heart of Colne.","image":"images/hero-dessert.png","image_alt":"Chocolate brownie stack with ice cream and dripping sauce","badge_html":"Based in<br>Colne","default_location":"Colne, UK","outlets_text":"Collection & delivery from our Colne kitchen","delivery_title":"Order Delivery","delivery_subtitle":"From our Colne kitchen","takeaway_title":"Collection","takeaway_subtitle":"Collect in store"}', 1, 20),
('popular', 'Popular Right Now', '{"title":"Popular Right Now","view_all_label":"View All","view_all_href":"#menu","limit":8,"empty_note":"Mark products as Featured in Products admin to show them here."}', 1, 40),
('about', 'About Banner', '{"eyebrow":"About Us","title":"Feed the Kraving","copy":"Kraved started the way most good things do, with a craving that just wouldn\'t quit.","copy_extra":"Based in the heart of Colne, built on the belief that a little indulgence, done properly, can turn an ordinary day into a good one.","quote":"Feed the Kraving","mission":"Our mission is simple, to make every moment sweet and memorable.","cta_label":"Order Online","cta_href":"#menu"}', 1, 60),
('trust_bar', 'Trust / Offers Bar', '{"chips":["Rich Chocolate Bliss","Made with Real Goodness","Sweets That Make Life Sweeter","Taste the Sweetness in Every Bite","Handcrafted in Colne"]}', 1, 70),
('hygiene', 'Food Hygiene Rating', '{"eyebrow":"FOOD SAFETY & HYGIENE","title":"⭐ 5-Star Food Hygiene Rated","description":"We’re proud to have achieved a 5-Star Food Hygiene Rating, the highest rating available. It reflects our commitment to maintaining excellent standards of food safety, cleanliness and hygiene, so you can enjoy your Kraved favourites with confidence.","rating":"5","rating_label":"VERY GOOD","badge_image":"images/food-hygiene-rating-5.svg"}', 1, 65),
('menu', 'Full Menu Intro', '{"title":"Full Menu","lead":"Customise sauces, toppings & gelato — or build a mix & match box."}', 1, 80),
('footer', 'Footer', '{"mission":"Our mission is simple, to make every moment sweet and memorable.","tagline":"Taste the sweetness in every bite.","newsletter_title":"Stay Updated","newsletter_text":"Subscribe for exclusive offers & updates","copyright":"Kraved Desserts. All rights reserved.","made_in":"Made with ♥ in Colne","quick_links":[{"label":"Menu","href":"/#menu"},{"label":"Best Sellers","href":"/#popular"},{"label":"Offers","href":"/#offers"},{"label":"About Us","href":"/#about"},{"label":"Contact","href":"/#contact"}],"info_links":[{"label":"Delivery Info","href":"#fulfillment"},{"label":"FAQs","href":"#"},{"label":"Terms & Conditions","href":"#"},{"label":"Privacy Policy","href":"#"}],"social":[{"label":"IG","url":"#","aria":"Instagram"},{"label":"FB","url":"#","aria":"Facebook"},{"label":"TT","url":"#","aria":"TikTok"},{"label":"X","url":"#","aria":"X"}]}', 1, 90);
