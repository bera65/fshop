CREATE TABLE IF NOT EXISTS `instagram_gallery_settings` (
	`id` tinyint unsigned NOT NULL DEFAULT 1,
	`enabled` tinyint(1) NOT NULL DEFAULT 1,
	`title` varchar(128) NOT NULL DEFAULT 'Instagram',
	`subtitle` varchar(255) NOT NULL DEFAULT '',
	`profile_url` varchar(255) NOT NULL DEFAULT '',
	`profile_label` varchar(128) NOT NULL DEFAULT '@magaza',
	PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `instagram_gallery_items` (
	`id` int unsigned NOT NULL AUTO_INCREMENT,
	`image_url` varchar(512) NOT NULL DEFAULT '',
	`link_url` varchar(512) NOT NULL DEFAULT '',
	`caption` varchar(255) NOT NULL DEFAULT '',
	`position` int unsigned NOT NULL DEFAULT 0,
	`active` tinyint(1) NOT NULL DEFAULT 1,
	PRIMARY KEY (`id`),
	KEY `position` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `instagram_gallery_settings` (`id`, `enabled`, `title`, `profile_label`)
SELECT 1, 1, 'Instagram', '@magaza'
WHERE NOT EXISTS (SELECT 1 FROM `instagram_gallery_settings` WHERE `id` = 1);
