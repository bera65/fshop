CREATE TABLE IF NOT EXISTS `alert_stock_subscriptions` (
	`id_subscription` int unsigned NOT NULL AUTO_INCREMENT,
	`id_product` int unsigned NOT NULL,
	`id_variation` int unsigned NOT NULL DEFAULT 0,
	`id_user` int unsigned NOT NULL DEFAULT 0,
	`email` varchar(255) NOT NULL,
	`product_name` varchar(255) NOT NULL DEFAULT '',
	`product_url` varchar(500) NOT NULL DEFAULT '',
	`is_sent` tinyint(1) NOT NULL DEFAULT 0,
	`date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`sent_at` datetime DEFAULT NULL,
	PRIMARY KEY (`id_subscription`),
	KEY `idx_product` (`id_product`),
	KEY `idx_email` (`email`),
	KEY `idx_pending` (`id_product`, `is_sent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `alert_stock_snapshots` (
	`id_snapshot` int unsigned NOT NULL AUTO_INCREMENT,
	`id_product` int unsigned NOT NULL,
	`id_variation` int unsigned NOT NULL DEFAULT 0,
	`last_stock` int NOT NULL DEFAULT 0,
	`date_upd` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id_snapshot`),
	UNIQUE KEY `product_variation` (`id_product`, `id_variation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
