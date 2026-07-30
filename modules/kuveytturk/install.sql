CREATE TABLE IF NOT EXISTS `kuveytturk_pending_checkouts` (
  `id_pending` int(11) NOT NULL AUTO_INCREMENT,
  `reference` varchar(64) NOT NULL,
  `cart_summary` longtext NOT NULL,
  `checkout_data` longtext NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pending`),
  UNIQUE KEY `reference` (`reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kuveytturk_log` (
  `id_log` int(11) NOT NULL AUTO_INCREMENT,
  `id_cart` int(11) DEFAULT NULL,
  `id_order` int(11) DEFAULT NULL,
  `merchant_order_id` varchar(64) NOT NULL,
  `transaction_type` varchar(32) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `request_data` text DEFAULT NULL,
  `response_data` text DEFAULT NULL,
  `response_code` varchar(32) DEFAULT NULL,
  `response_message` text DEFAULT NULL,
  `md` text DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_upd` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `merchant_order_id` (`merchant_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
