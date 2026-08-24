<?php

return new class {
	public string $version = '2.5.4';
	public string $title = 'Marketplace sipariş takip linki ve şema güncellemesi';

	public function up(): void
	{
		if (!class_exists('MarketplaceTables', false) && is_file(dirname(__DIR__, 2) . '/core/MarketplaceTables.php')) {
			require_once dirname(__DIR__, 2) . '/core/MarketplaceTables.php';
		}

		if (class_exists('MarketplaceTables', false)) {
			MarketplaceTables::ensureSchema();
		}
	}
};
