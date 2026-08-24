<?php

return new class {
	public string $version = '2.5.6';
	public string $title = 'Güvenlik, iyzico, sipariş silme, admin şifre sıfırlama';

	public function up(): void
	{
		Admin::ensureSchema();
		Order::ensureSchema();
		Coupon::ensureSchema();
		Product::ensureSchema();

		if (!class_exists('ApiKey', false) && is_file(dirname(__DIR__, 2) . '/core/ApiKey.php')) {
			require_once dirname(__DIR__, 2) . '/core/ApiKey.php';
		}

		if (class_exists('ApiKey', false)) {
			ApiKey::ensureSchema();
		}

		$this->ensureSetting('SEO_TITLE_SUFFIX', '');
		$this->ensureSetting('ORDER_GOAL_TARGET', '0');

		$menu = dirname(__DIR__, 2) . '/modules/main-menu/lib/MenuService.php';

		if (is_file($menu)) {
			require_once $menu;
		}

		if (class_exists('MainMenuService', false)) {
			MainMenuService::ensureSchema();
		}
	}

	private function ensureSetting(string $key, string $default): void
	{
		$exists = DB::getValue('SELECT id FROM settings WHERE title = ? LIMIT 1', [$key]);

		if ($exists === false) {
			Settings::set($key, $default);
		}
	}
};
