<?php

class Schema
{
	private static bool $ready = false;

	public static function ensure(): void
	{
		if (self::$ready) {
			return;
		}

		self::$ready = true;
		Product::ensureSchema();

		if (is_file(dirname(__DIR__) . '/core/Tax.php')) {
			require_once dirname(__DIR__) . '/core/Tax.php';
			Tax::ensureSchema();
		}

		if (is_file(dirname(__DIR__) . '/core/ApiKey.php')) {
			require_once dirname(__DIR__) . '/core/ApiKey.php';
			ApiKey::ensureSchema();
		}

		if (is_file(dirname(__DIR__) . '/core/Cargo.php')) {
			require_once dirname(__DIR__) . '/core/Cargo.php';
			Cargo::ensureSchema();
		}

		$userCol = DB::execute("SHOW COLUMNS FROM `users` LIKE 'email'");
		if (empty($userCol)) {
			DB::execute(
				"ALTER TABLE `users` ADD COLUMN `email` varchar(128) NOT NULL DEFAULT '' AFTER `phone`"
			);
		}

		$notifTable = DB::execute("SHOW TABLES LIKE 'user_notifications'");
		if (empty($notifTable)) {
			DB::execute(
				"CREATE TABLE `user_notifications` (
					`id_notification` int(11) NOT NULL AUTO_INCREMENT,
					`id_user` int(11) NOT NULL,
					`type` varchar(32) NOT NULL DEFAULT '',
					`title` varchar(255) NOT NULL DEFAULT '',
					`message` text NOT NULL,
					`link` varchar(255) NOT NULL DEFAULT '',
					`is_read` tinyint(1) NOT NULL DEFAULT 0,
					`date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (`id_notification`),
					KEY `id_user` (`id_user`),
					KEY `is_read` (`is_read`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		}

		$couponTable = DB::execute("SHOW TABLES LIKE 'coupons'");
		if (empty($couponTable)) {
			DB::execute(
				"CREATE TABLE `coupons` (
					`id_coupon` int(11) NOT NULL AUTO_INCREMENT,
					`code` varchar(32) NOT NULL,
					`discount_type` enum('percent','fixed') NOT NULL DEFAULT 'percent',
					`discount_value` decimal(10,2) NOT NULL DEFAULT 0.00,
					`min_cart` decimal(10,2) NOT NULL DEFAULT 0.00,
					`max_uses` int(11) NOT NULL DEFAULT 0,
					`used_count` int(11) NOT NULL DEFAULT 0,
					`id_user` int(11) NOT NULL DEFAULT 0,
					`date_from` datetime DEFAULT NULL,
					`date_to` datetime DEFAULT NULL,
					`active` tinyint(1) NOT NULL DEFAULT 1,
					`date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (`id_coupon`),
					UNIQUE KEY `code` (`code`),
					KEY `id_user` (`id_user`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		}

		Coupon::ensureSchema();

		$orderCoupon = DB::execute("SHOW COLUMNS FROM `orders` LIKE 'coupon_code'");
		if (empty($orderCoupon)) {
			DB::execute(
				"ALTER TABLE `orders`
				 ADD COLUMN `coupon_code` varchar(32) NOT NULL DEFAULT '' AFTER `note`,
				 ADD COLUMN `coupon_discount` decimal(10,2) NOT NULL DEFAULT 0.00 AFTER `coupon_code`"
			);
		}

		$resetToken = DB::execute("SHOW COLUMNS FROM `users` LIKE 'reset_token'");
		if (empty($resetToken)) {
			DB::execute(
				"ALTER TABLE `users`
				 ADD COLUMN `reset_token` varchar(64) NOT NULL DEFAULT '' AFTER `login_code`,
				 ADD COLUMN `reset_expires` datetime DEFAULT NULL AFTER `reset_token`"
			);
		}

		self::ensureSetting('THEME', 'blue');
		self::ensureSetting('MAIL_DRIVER', 'php');
		self::ensureSetting('MAIL_HEADER', '');
		self::ensureSetting('MAIL_FOOTER', '');
		self::ensureSetting('SHOP_ACTIVE', '1');
		self::ensureSetting('SHOP_MAINTENANCE_MESSAGE', '');
		self::ensureSetting('SHOP_MAINTENANCE_IPS', '');
		self::ensureSetting('SITE_VISIBILITY', 'public');
		self::ensureSetting('MEMBER_APPROVAL', 'auto');
		self::ensureSetting('GATE_TITLE', '');
		self::ensureSetting('GATE_FEATURES', '');
		self::ensureSetting('CONTACT_ADDRESS', '');
		self::ensureSetting('CONTACT_CITY', '');
		self::ensureSetting('CONTACT_COUNTRY', '');
		self::ensureSetting('POSTAL_CODE', '');
		self::ensureSetting('OPEN_HOUR', '09:00');
		self::ensureSetting('CLOSE_HOUR', '18:00');
		self::ensureSetting('FACEBOOK_LINK', '');
		self::ensureSetting('INSTAGRAM_LINK', '');
		self::ensureSetting('X_LINK', '');
		self::ensureSetting('YOUTUBE_LINK', '');
		self::ensureSetting('LINKEDIN_LINK', '');
		self::ensureSetting('PINTEREST_LINK', '');
		self::ensureSetting('TIKTOK_LINK', '');
		self::ensureSetting('DEFAULT_LANG', 'tr');
		self::ensureSetting('SHOP_LANGUAGES', 'tr,en');
		self::ensureSetting('ADMIN_DEFAULT_LANG', 'tr');
		self::ensureSetting('LANG_LABELS', '{"tr":"Türkçe","en":"English"}');
		self::ensureSetting('SHOP_CURRENCIES', 'try,usd,eur');
		self::ensureSetting(
			'CURRENCY_META',
			'{"try":{"label":"Türk Lirası","symbol":"₺"},"usd":{"label":"Amerikan Doları","symbol":"$"},"eur":{"label":"Euro","symbol":"€"}}'
		);
		self::ensureSetting('SHOP_CURRENCY', 'try');

		if (!class_exists('Currency', false)) {
			require_once dirname(__DIR__) . '/core/Currency.php';
		}

		Currency::ensureDefaults();

		if (class_exists('CartPromotion', false)) {
			CartPromotion::ensureSchema();
		}

		Order::ensureSchema();

		if (!class_exists('ReturnRequest', false)) {
			require_once dirname(__DIR__) . '/core/ReturnRequest.php';
		}

		ReturnRequest::ensureSchema();

		if (!class_exists('CancelRequest', false)) {
			require_once dirname(__DIR__) . '/core/CancelRequest.php';
		}

		CancelRequest::ensureSchema();

		if (!class_exists('AdminNotification', false)) {
			require_once dirname(__DIR__) . '/core/AdminNotification.php';
		}

		AdminNotification::ensureSchema();

		Contact::ensureSchema();

		RateLimit::ensureSchema();

		if (!class_exists('Address', false)) {
			require_once dirname(__DIR__) . '/core/Address.php';
		}

		Address::ensureSchema();
		Cms::ensureSchema();
		Lang::ensureSchema();

		if (!class_exists('ProductLog', false) && is_file(dirname(__DIR__) . '/core/ProductLog.php')) {
			require_once dirname(__DIR__) . '/core/ProductLog.php';
		}

		if (class_exists('ProductLog', false)) {
			ProductLog::ensureSchema();
		}

		if (!class_exists('MarketplaceTables', false) && is_file(dirname(__DIR__) . '/core/MarketplaceTables.php')) {
			require_once dirname(__DIR__) . '/core/MarketplaceTables.php';
		}

		if (!class_exists('MarketplaceLog', false) && is_file(dirname(__DIR__) . '/core/MarketplaceLog.php')) {
			require_once dirname(__DIR__) . '/core/MarketplaceLog.php';
		}

		if (class_exists('MarketplaceTables', false)) {
			MarketplaceTables::ensureSchema();
		}
	}

	private static function ensureSetting(string $key, string $default): void
	{
		$exists = DB::getValue('SELECT id FROM settings WHERE title = ? LIMIT 1', [$key]);

		if ($exists === false) {
			Settings::set($key, $default);
		}
	}
}
