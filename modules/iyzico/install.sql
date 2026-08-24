CREATE TABLE IF NOT EXISTS `iyzico_pending_checkouts` (
  `reference` varchar(32) NOT NULL,
  `token` varchar(128) NOT NULL DEFAULT '',
  `payload` longtext NOT NULL,
  `date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`reference`),
  KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `iyzico_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` varchar(64) NOT NULL DEFAULT '',
  `id_order` int(11) NOT NULL DEFAULT 0,
  `reference` varchar(32) NOT NULL DEFAULT '',
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `installment` int(11) NOT NULL DEFAULT 1,
  `status` varchar(32) NOT NULL DEFAULT '',
  `token` varchar(128) NOT NULL DEFAULT '',
  `date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_order` (`id_order`),
  KEY `reference` (`reference`),
  KEY `payment_id` (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
