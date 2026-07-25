-- KDV / vergi oranları (core/Tax.php ile uyumlu)
CREATE TABLE IF NOT EXISTS `taxes` (
	`id_tax` int unsigned NOT NULL AUTO_INCREMENT,
	`name` varchar(64) NOT NULL DEFAULT '',
	`rate` decimal(6,2) NOT NULL DEFAULT 0.00,
	`active` tinyint(1) NOT NULL DEFAULT 1,
	`is_default` tinyint(1) NOT NULL DEFAULT 0,
	`position` int unsigned NOT NULL DEFAULT 0,
	PRIMARY KEY (`id_tax`),
	UNIQUE KEY `rate` (`rate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `taxes` (`name`, `rate`, `active`, `is_default`, `position`)
SELECT 'KDV %1', 1, 1, 0, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `taxes` WHERE `rate` = 1 LIMIT 1);

INSERT INTO `taxes` (`name`, `rate`, `active`, `is_default`, `position`)
SELECT 'KDV %10', 10, 1, 0, 2 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `taxes` WHERE `rate` = 10 LIMIT 1);

INSERT INTO `taxes` (`name`, `rate`, `active`, `is_default`, `position`)
SELECT 'KDV %20', 20, 1, 1, 3 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `taxes` WHERE `rate` = 20 LIMIT 1);
