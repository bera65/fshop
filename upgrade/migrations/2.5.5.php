<?php

return new class {
	public string $version = '2.5.5';
	public string $title = 'Tedarikçiler + hediye paketi';

	public function up(): void
	{
		if (!class_exists('Supplier', false) && is_file(dirname(__DIR__, 2) . '/core/Supplier.php')) {
			require_once dirname(__DIR__, 2) . '/core/Supplier.php';
		}

		if (class_exists('Supplier', false)) {
			Supplier::ensureSchema();
		}

		Product::ensureSchema();
		Order::ensureSchema();

		$this->ensureSetting('GIFT_WRAP_ENABLED', '0');
		$this->ensureSetting('GIFT_WRAP_FEE', '0');
	}

	private function ensureSetting(string $key, string $default): void
	{
		$exists = DB::getValue('SELECT id FROM settings WHERE title = ? LIMIT 1', [$key]);

		if ($exists === false) {
			Settings::set($key, $default);
		}
	}
};
