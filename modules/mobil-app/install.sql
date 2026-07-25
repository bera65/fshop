CREATE TABLE IF NOT EXISTS `mobil_app_settings` (
	`id` tinyint unsigned NOT NULL DEFAULT 1,
	`enabled` tinyint(1) NOT NULL DEFAULT 1,
	`app_name` varchar(128) NOT NULL DEFAULT '',
	`short_name` varchar(64) NOT NULL DEFAULT '',
	`description` varchar(255) NOT NULL DEFAULT '',
	`theme_color` varchar(7) NOT NULL DEFAULT '#194e70',
	`background_color` varchar(7) NOT NULL DEFAULT '#ffffff',
	`orientation` varchar(32) NOT NULL DEFAULT 'portrait-primary',
	`menu_enabled` tinyint(1) NOT NULL DEFAULT 1,
	`menu_label` varchar(128) NOT NULL DEFAULT 'Uygulamayı yükle',
	`menu_hint_ios` varchar(255) NOT NULL DEFAULT 'Safari''de Paylaş > Ana Ekrana Ekle',
	`icon_192` varchar(255) NOT NULL DEFAULT '',
	`icon_512` varchar(255) NOT NULL DEFAULT '',
	`icon_apple` varchar(255) NOT NULL DEFAULT '',
	`offline_title` varchar(128) NOT NULL DEFAULT 'İnternet bağlantısı yok',
	`offline_message` varchar(255) NOT NULL DEFAULT 'Bağlantınızı kontrol edip tekrar deneyin.',
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `mobil_app_settings` (`id`, `enabled`, `app_name`, `short_name`, `description`)
SELECT 1, 1, '', '', ''
WHERE NOT EXISTS (SELECT 1 FROM `mobil_app_settings` WHERE `id` = 1);
