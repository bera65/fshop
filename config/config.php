<?php
	Class Settings
	{
		public static function get($title)
		{
			$value = DB::getValue('SELECT value FROM settings WHERE title = ? LIMIT 1', [$title]);

			return $value !== false ? $value : '';
		}

		public static function set(string $title, string $value): bool
		{
			$exists = DB::getValue('SELECT id FROM settings WHERE title = ? LIMIT 1', [$title]);

			if ($exists !== false) {
				$result = DB::update('settings', ['value' => $value], 'title = :where_title', ['where_title' => $title]);

				// MySQL/PDO: deger ayni kaldiysa rowCount 0 doner; bu hata degildir.
				return $result !== false;
			}

			$inserted = DB::insert('settings', [
				'title' => $title,
				'value' => $value,
			]);

			return $inserted !== false;
		}

		public static function getEditableKeys(): array
		{
			return [
				'SITE_NAME' => ['label' => 'Site name', 'group' => 'general'],
				'SITE_VISIBILITY' => ['label' => 'Site visibility', 'group' => 'general', 'type' => 'select'],
				'MEMBER_APPROVAL' => ['label' => 'Member approval', 'group' => 'general', 'type' => 'select'],
				'GATE_TITLE' => ['label' => 'Gate title', 'group' => 'general'],
				'GATE_FEATURES' => ['label' => 'Gate features', 'group' => 'general', 'type' => 'textarea'],
				'SHOP_ACTIVE' => ['label' => 'Store active', 'group' => 'general', 'type' => 'checkbox'],
				'SHOP_MAINTENANCE_MESSAGE' => ['label' => 'Maintenance message', 'group' => 'general', 'type' => 'html'],
				'SHOP_MAINTENANCE_IPS' => ['label' => 'Allowed IPs when store is closed', 'group' => 'general', 'type' => 'textarea'],
				'CONTACT_EMAIL' => ['label' => 'Contact email', 'group' => 'contact'],
				'CONTACT_PHONE' => ['label' => 'Phone (display)', 'group' => 'contact'],
				'CONTACT_PHONE_TEL' => ['label' => 'Phone (tel link)', 'group' => 'contact'],
				'CONTACT_ADDRESS' => ['label' => 'Store address', 'group' => 'contact', 'type' => 'textarea'],
				'CONTACT_CITY' => ['label' => 'City', 'group' => 'contact'],
				'CONTACT_COUNTRY' => ['label' => 'Country', 'group' => 'contact'],
				'POSTAL_CODE' => ['label' => 'Postal code', 'group' => 'contact'],
				'OPEN_HOUR' => ['label' => 'Opening hour', 'group' => 'contact'],
				'CLOSE_HOUR' => ['label' => 'Closing hour', 'group' => 'contact'],
				'FACEBOOK_LINK' => ['label' => 'Facebook URL', 'group' => 'contact'],
				'INSTAGRAM_LINK' => ['label' => 'Instagram URL', 'group' => 'contact'],
				'X_LINK' => ['label' => 'X (Twitter) URL', 'group' => 'contact'],
				'YOUTUBE_LINK' => ['label' => 'YouTube URL', 'group' => 'contact'],
				'LINKEDIN_LINK' => ['label' => 'LinkedIn URL', 'group' => 'contact'],
				'PINTEREST_LINK' => ['label' => 'Pinterest URL', 'group' => 'contact'],
				'TIKTOK_LINK' => ['label' => 'TikTok URL', 'group' => 'contact'],
				'RETURN_REQUEST_DAYS' => ['label' => 'Return request window (days)', 'group' => 'returns'],
				'ORDER_REF_PREFIX' => ['label' => 'Order number prefix', 'group' => 'orders'],
				'ORDER_REF_SUFFIX_MODE' => ['label' => 'Order number format', 'group' => 'orders', 'type' => 'select'],
				'ORDER_REF_PAD' => ['label' => 'Sequential digit count', 'group' => 'orders'],
				'GIFT_WRAP_ENABLED' => ['label' => 'Enable gift wrap', 'group' => 'orders', 'type' => 'checkbox'],
				'GIFT_WRAP_FEE' => ['label' => 'Gift wrap fee', 'group' => 'orders'],
				'MAIL_DRIVER' => ['label' => 'Mail driver', 'group' => 'mail', 'type' => 'select'],
				'SMTP_HOST' => ['label' => 'SMTP host', 'group' => 'smtp', 'type' => 'text'],
				'SMTP_PORT' => ['label' => 'SMTP port', 'group' => 'smtp', 'type' => 'text'],
				'SMTP_USER' => ['label' => 'SMTP user (email)', 'group' => 'smtp', 'type' => 'text'],
				'SMTP_PASS' => ['label' => 'SMTP password', 'group' => 'smtp', 'type' => 'password'],
				'SMTP_ENCRYPTION' => ['label' => 'Encryption (ssl / tls)', 'group' => 'smtp', 'type' => 'text'],
				'SMTP_FROM_EMAIL' => ['label' => 'From email', 'group' => 'smtp', 'type' => 'text'],
				'SMTP_FROM_NAME' => ['label' => 'From name (empty = site name)', 'group' => 'smtp', 'type' => 'text'],
				'MAIL_HEADER' => ['label' => 'Email header (HTML)', 'group' => 'mail_template', 'type' => 'html'],
				'MAIL_FOOTER' => ['label' => 'Email footer (HTML)', 'group' => 'mail_template', 'type' => 'html'],
			];
		}

		public static function getAllForAdmin(): array
		{
			$values = [];

			foreach (self::getEditableKeys() as $key => $meta) {
				$values[$key] = self::get($key);
			}

			return $values;
		}

		public static function getFooterDescriptionDefault(): string
		{
			if (class_exists('Lang', false)) {
				$translated = trim(Lang::translate('Footer description'));

				if ($translated !== '' && $translated !== 'Footer description') {
					return $translated;
				}
			}

			return 'Güvenilir alışveriş deneyimi, hızlı teslimat ve geniş ürün yelpazesi ile yanınızdayız.';
		}

		public static function getFooterDescription(): string
		{
			$custom = trim((string) self::get('FOOTER_DESCRIPTION'));

			return $custom !== '' ? $custom : self::getFooterDescriptionDefault();
		}
	}
