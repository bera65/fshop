CREATE TABLE IF NOT EXISTS `product_tabs` (
	`id_tab` int(11) NOT NULL AUTO_INCREMENT,
	`title` varchar(128) NOT NULL,
	`content` mediumtext NOT NULL,
	`scope` enum('all','selected') NOT NULL DEFAULT 'all',
	`position` int(11) NOT NULL DEFAULT 0,
	`active` tinyint(1) NOT NULL DEFAULT 1,
	`date_add` datetime NOT NULL,
	`date_upd` datetime NOT NULL,
	PRIMARY KEY (`id_tab`),
	KEY `active_position` (`active`, `position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `product_tab_products` (
	`id_tab` int(11) NOT NULL,
	`id_product` int(11) NOT NULL,
	PRIMARY KEY (`id_tab`, `id_product`),
	KEY `id_product` (`id_product`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
