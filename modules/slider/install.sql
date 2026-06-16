CREATE TABLE IF NOT EXISTS `slider_slides` (
  `id_slide` int(11) NOT NULL AUTO_INCREMENT,
  `slide_group` varchar(32) NOT NULL DEFAULT 'hero',
  `title` varchar(255) NOT NULL DEFAULT '',
  `subtitle` varchar(255) NOT NULL DEFAULT '',
  `promo_lines` text DEFAULT NULL,
  `button_text` varchar(64) NOT NULL DEFAULT 'Keşfet',
  `link_url` varchar(512) NOT NULL DEFAULT '',
  `image_file` varchar(128) NOT NULL DEFAULT '',
  `position` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_slide`),
  KEY `slide_group` (`slide_group`, `active`, `position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
