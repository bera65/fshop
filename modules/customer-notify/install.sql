CREATE TABLE IF NOT EXISTS `customer_notify_broadcasts` (
  `id_broadcast` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  `link` varchar(512) NOT NULL DEFAULT '',
  `scope` varchar(16) NOT NULL DEFAULT 'all',
  `recipient_count` int(11) NOT NULL DEFAULT 0,
  `send_email` tinyint(1) NOT NULL DEFAULT 0,
  `selected_users_json` text DEFAULT NULL,
  `created_by` int(11) NOT NULL DEFAULT 0,
  `date_add` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_broadcast`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
