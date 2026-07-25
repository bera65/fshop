CREATE TABLE IF NOT EXISTS `trust_bar_settings` (
	`id` tinyint unsigned NOT NULL DEFAULT 1,
	`enabled` tinyint(1) NOT NULL DEFAULT 1,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `trust_bar_items` (
	`id_item` int unsigned NOT NULL AUTO_INCREMENT,
	`title` varchar(128) NOT NULL DEFAULT '',
	`subtitle` varchar(255) NOT NULL DEFAULT '',
	`icon` varchar(32) NOT NULL DEFAULT 'shipping',
	`position` int unsigned NOT NULL DEFAULT 0,
	`active` tinyint(1) NOT NULL DEFAULT 1,
	PRIMARY KEY (`id_item`),
	KEY `position` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `trust_bar_settings` (`id`, `enabled`)
SELECT 1, 1
WHERE NOT EXISTS (SELECT 1 FROM `trust_bar_settings` WHERE `id` = 1);

INSERT INTO `trust_bar_items` (`title`, `subtitle`, `icon`, `position`, `active`)
SELECT * FROM (
	SELECT 'Ücretsiz Kargo' AS title, 'Belirli tutar üzeri' AS subtitle, 'shipping' AS icon, 1 AS position, 1 AS active
	UNION ALL SELECT 'Kolay İade', '30 gün iade', 'returns', 2, 1
	UNION ALL SELECT 'Güvenli Ödeme', '%100 güvenli', 'secure', 3, 1
	UNION ALL SELECT '7/24 Destek', 'Bize ulaşın', 'support', 4, 1
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM `trust_bar_items` LIMIT 1);
