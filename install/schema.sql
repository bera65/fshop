-- FShop schema export 2026-07-21

CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(64) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `categories` (
  `id_category` int(11) NOT NULL AUTO_INCREMENT,
  `id_parent` int(11) NOT NULL DEFAULT 0,
  `category_name` varchar(64) NOT NULL,
  `category_link` varchar(128) NOT NULL,
  `meta_title` varchar(255) NOT NULL DEFAULT '',
  `meta_description` varchar(512) NOT NULL DEFAULT '',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_category`),
  KEY `category_link` (`category_link`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `brands` (
  `id_brand` int(11) NOT NULL AUTO_INCREMENT,
  `brand_name` varchar(48) NOT NULL,
  `brand_link` varchar(64) NOT NULL,
  `meta_title` varchar(255) NOT NULL DEFAULT '',
  `meta_description` varchar(512) NOT NULL DEFAULT '',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_brand`),
  KEY `brand_link` (`brand_link`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `products` (
  `id_product` int(11) NOT NULL AUTO_INCREMENT,
  `id_category` int(11) NOT NULL,
  `id_brand` int(11) NOT NULL,
  `product_name` varchar(128) NOT NULL,
  `short_description` varchar(512) NOT NULL DEFAULT '',
  `meta_title` varchar(255) NOT NULL DEFAULT '',
  `meta_description` varchar(512) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `product_link` varchar(128) NOT NULL,
  `price` decimal(20,2) NOT NULL DEFAULT 0.00,
  `pack_price_override` decimal(20,2) DEFAULT NULL,
  `cost` decimal(20,2) NOT NULL DEFAULT 0.00,
  `doviz` varchar(16) NOT NULL DEFAULT 'try',
  `doviz_price` decimal(20,2) NOT NULL DEFAULT 0.00,
  `doviz_old_price` decimal(20,2) NOT NULL DEFAULT 0.00,
  `doviz_cost` decimal(20,2) NOT NULL DEFAULT 0.00,
  `old_price` decimal(20,2) NOT NULL DEFAULT 0.00,
  `vat` decimal(6,2) NOT NULL DEFAULT 20.00,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `stock` int(11) NOT NULL DEFAULT 100,
  `stock_empty_at` datetime DEFAULT NULL,
  `cargo_day` int(3) NOT NULL DEFAULT 0,
  `label` varchar(128) NOT NULL DEFAULT '',
  `product_video` varchar(256) NOT NULL DEFAULT '',
  `stock_code` varchar(64) NOT NULL DEFAULT '',
  `barcode` varchar(64) NOT NULL DEFAULT '',
  `desi` int(11) NOT NULL DEFAULT 1,
  `product_type` varchar(16) NOT NULL DEFAULT 'physical',
  `virtual_kind` varchar(16) NOT NULL DEFAULT '',
  `virtual_file` varchar(255) NOT NULL DEFAULT '',
  `virtual_file_name` varchar(255) NOT NULL DEFAULT '',
  `virtual_text` text DEFAULT NULL,
  PRIMARY KEY (`id_product`),
  KEY `id_category` (`id_category`),
  KEY `id_brand` (`id_brand`),
  KEY `product_link` (`product_link`),
  KEY `barcode` (`barcode`),
  KEY `stock_code` (`stock_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `images` (
  `id_image` int(11) NOT NULL AUTO_INCREMENT,
  `id_product` int(11) NOT NULL,
  `cover` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_image`),
  KEY `id_product` (`id_product`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `user_full_name` varchar(128) NOT NULL DEFAULT '',
  `phone` varchar(20) NOT NULL,
  `email` varchar(128) NOT NULL DEFAULT '',
  `google_id` varchar(64) DEFAULT NULL,
  `password` varchar(255) NOT NULL DEFAULT '',
  `image` varchar(128) NOT NULL DEFAULT '',
  `login_code` varchar(64) NOT NULL DEFAULT '',
  `reset_token` varchar(64) NOT NULL DEFAULT '',
  `reset_expires` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `date_add` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `phone` (`phone`),
  UNIQUE KEY `google_id` (`google_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `orders` (
  `id_order` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `reference` varchar(16) NOT NULL,
  `status` tinyint(2) NOT NULL DEFAULT 1,
  `cargo_company` varchar(64) NOT NULL DEFAULT '',
  `tracking_number` varchar(64) NOT NULL DEFAULT '',
  `payment_method` varchar(32) NOT NULL,
  `customer_name` varchar(128) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_email` varchar(128) NOT NULL DEFAULT '',
  `company_name` varchar(128) NOT NULL DEFAULT '',
  `tax_office` varchar(64) NOT NULL DEFAULT '',
  `tax_number` varchar(20) NOT NULL DEFAULT '',
  `address_city` varchar(64) NOT NULL,
  `address_district` varchar(64) NOT NULL,
  `address_text` text NOT NULL,
  `note` text NOT NULL,
  `coupon_code` varchar(32) NOT NULL DEFAULT '',
  `coupon_discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `promotion_name` varchar(128) NOT NULL DEFAULT '',
  `promotion_discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_discount_label` varchar(128) NOT NULL DEFAULT '',
  `subtotal` decimal(20,2) NOT NULL DEFAULT 0.00,
  `shipping` decimal(20,2) NOT NULL DEFAULT 0.00,
  `total` decimal(20,2) NOT NULL DEFAULT 0.00,
  `date_add` datetime NOT NULL DEFAULT current_timestamp(),
  `date_delivered` datetime DEFAULT NULL,
  PRIMARY KEY (`id_order`),
  KEY `id_user` (`id_user`),
  KEY `reference` (`reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `order_detail` (
  `id_order_detail` int(11) NOT NULL AUTO_INCREMENT,
  `id_order` int(11) NOT NULL,
  `id_product` int(11) NOT NULL,
  `id_variation` int(11) NOT NULL DEFAULT 0,
  `product_name` varchar(128) NOT NULL,
  `variation_label` varchar(255) NOT NULL DEFAULT '',
  `price` decimal(20,2) NOT NULL,
  `qty` int(11) NOT NULL,
  `total` decimal(20,2) NOT NULL,
  `virtual_delivery` text DEFAULT NULL,
  `download_token` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_order_detail`),
  KEY `id_order` (`id_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `favorites` (
  `id_favorite` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `id_product` int(11) NOT NULL,
  `date_add` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_favorite`),
  UNIQUE KEY `user_product` (`id_user`,`id_product`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `contact_messages` (
  `id_message` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL DEFAULT 0,
  `id_order` int(11) NOT NULL DEFAULT 0,
  `full_name` varchar(128) NOT NULL,
  `email` varchar(128) NOT NULL,
  `phone` varchar(20) NOT NULL DEFAULT '',
  `subject` varchar(128) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  `attachment_file` varchar(255) NOT NULL DEFAULT '',
  `ip_address` varchar(45) NOT NULL DEFAULT '',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `date_add` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_message`),
  KEY `id_order` (`id_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_addresses` (
  `id_address` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `label` varchar(64) NOT NULL DEFAULT '',
  `full_name` varchar(128) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `company_name` varchar(128) NOT NULL DEFAULT '',
  `tax_office` varchar(64) NOT NULL DEFAULT '',
  `tax_number` varchar(20) NOT NULL DEFAULT '',
  `city` varchar(64) NOT NULL,
  `district` varchar(64) NOT NULL,
  `address_text` text NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `date_add` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_address`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_notifications` (
  `id_notification` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `type` varchar(32) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  `link` varchar(255) NOT NULL DEFAULT '',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `date_add` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_notification`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `coupons` (
  `id_coupon` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(32) NOT NULL,
  `discount_type` enum('percent','fixed') NOT NULL DEFAULT 'percent',
  `discount_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `min_cart` decimal(10,2) NOT NULL DEFAULT 0.00,
  `max_uses` int(11) NOT NULL DEFAULT 0,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `id_user` int(11) NOT NULL DEFAULT 0,
  `date_from` datetime DEFAULT NULL,
  `date_to` datetime DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `date_add` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_coupon`),
  UNIQUE KEY `code` (`code`),
  KEY `id_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `admins` (
  `id_admin` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(128) NOT NULL,
  `email` varchar(128) NOT NULL,
  `password` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `date_add` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_admin`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `modules` (
  `id_module` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `version` varchar(16) NOT NULL DEFAULT '1.0.0',
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `installed` tinyint(1) NOT NULL DEFAULT 0,
  `date_add` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_module`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `module_display_hooks` (
  `id_hook` int(11) NOT NULL AUTO_INCREMENT,
  `module_name` varchar(64) NOT NULL,
  `hook_name` varchar(32) NOT NULL,
  `position` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_hook`),
  UNIQUE KEY `module_hook` (`module_name`,`hook_name`),
  KEY `hook_name` (`hook_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `admins` (`id_admin`, `full_name`, `email`, `password`, `active`) VALUES
(1, 'Site Yöneticisi', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

INSERT INTO `settings` (`title`, `value`) VALUES
('DOMAIN', 'http://localhost/fshop/'),
('FOLDER', '/fshop/'),
('SITE_NAME', 'FShop'),
('THEME', 'blue'),
('DEFAULT_LANG', 'tr'),
('SHOP_LANGUAGES', 'tr,en'),
('ADMIN_DEFAULT_LANG', 'tr'),
('PRODUCT_LIMIT', '5000'),
('FREE_SHIPPING_MIN', '500'),
('SHIPPING_FEE', '79.90'),
('HAVALE', '3'),
('CARGO_DAY', '3'),
('MAIL_DRIVER', 'php');

