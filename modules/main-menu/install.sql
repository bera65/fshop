CREATE TABLE IF NOT EXISTS `main_menu_items` (
  `id_menu_item` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(128) NOT NULL DEFAULT '',
  `link_type` varchar(32) NOT NULL DEFAULT 'custom',
  `link_value` varchar(512) NOT NULL DEFAULT '',
  `target` varchar(16) NOT NULL DEFAULT '_self',
  `position` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `show_header` tinyint(1) NOT NULL DEFAULT 1,
  `show_mobile` tinyint(1) NOT NULL DEFAULT 1,
  `show_footer` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_menu_item`),
  KEY `position` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
