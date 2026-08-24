<?php

return new class {
	public string $version = '2.5.1';
	public string $title = 'Marketplace tabloları (HB / N11)';

	public function up(): void
	{
		if (!class_exists('MarketplaceTables', false) && is_file(dirname(__DIR__, 2) . '/core/MarketplaceTables.php')) {
			require_once dirname(__DIR__, 2) . '/core/MarketplaceTables.php';
		}

		if (class_exists('MarketplaceTables', false)) {
			MarketplaceTables::ensureSchema();
		}

		if (!class_exists('MarketplaceLog', false) && is_file(dirname(__DIR__, 2) . '/core/MarketplaceLog.php')) {
			require_once dirname(__DIR__, 2) . '/core/MarketplaceLog.php';
		}

		if (class_exists('MarketplaceLog', false)) {
			MarketplaceLog::ensureSchema();
		}
	}
};
