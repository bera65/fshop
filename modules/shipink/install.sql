CREATE TABLE IF NOT EXISTS `shipink` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_order` INT(11) NOT NULL,
  `shipink_order_id` VARCHAR(64) NOT NULL DEFAULT '',
  `shipment_id` VARCHAR(64) NOT NULL DEFAULT '',
  `tracking_number` VARCHAR(128) NOT NULL DEFAULT '',
  `tracking_url` VARCHAR(512) NOT NULL DEFAULT '',
  `carrier` VARCHAR(128) NOT NULL DEFAULT '',
  `label_url` VARCHAR(512) NOT NULL DEFAULT '',
  `raw_response` MEDIUMTEXT NULL,
  `date_add` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_order` (`id_order`),
  KEY `shipink_order_id` (`shipink_order_id`),
  KEY `shipment_id` (`shipment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
