CREATE TABLE IF NOT EXISTS `recaptcha_settings` (
	`id` tinyint unsigned NOT NULL DEFAULT 1,
	`enabled` tinyint(1) NOT NULL DEFAULT 0,
	`version` varchar(8) NOT NULL DEFAULT 'v3',
	`site_key` varchar(128) NOT NULL DEFAULT '',
	`secret_key` varchar(128) NOT NULL DEFAULT '',
	`score_threshold` decimal(3,2) NOT NULL DEFAULT 0.50,
	`enable_contact` tinyint(1) NOT NULL DEFAULT 1,
	`enable_login` tinyint(1) NOT NULL DEFAULT 1,
	`enable_register` tinyint(1) NOT NULL DEFAULT 1,
	`enable_admin` tinyint(1) NOT NULL DEFAULT 1,
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `recaptcha_settings` (`id`, `enabled`, `version`)
SELECT 1, 0, 'v3'
WHERE NOT EXISTS (SELECT 1 FROM `recaptcha_settings` WHERE `id` = 1);
