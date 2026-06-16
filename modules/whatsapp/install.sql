CREATE TABLE IF NOT EXISTS `whatsapp` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `phone` VARCHAR(128) NOT NULL,
  `name` VARCHAR(128) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
