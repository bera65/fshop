<?php

return new class {
	public string $version = '2.5.3';
	public string $title = 'Fatura alanları, müşteri grupları, ürün log, sipariş düzenleme';

	public function up(): void
	{
		Order::ensureSchema();
		Order::ensureInvoiceDir();

		if (!class_exists('CustomerGroup', false) && is_file(dirname(__DIR__, 2) . '/core/CustomerGroup.php')) {
			require_once dirname(__DIR__, 2) . '/core/CustomerGroup.php';
		}

		if (class_exists('CustomerGroup', false)) {
			CustomerGroup::ensureSchema();
		}

		Customer::ensureSchema();

		if (!class_exists('ProductLog', false) && is_file(dirname(__DIR__, 2) . '/core/ProductLog.php')) {
			require_once dirname(__DIR__, 2) . '/core/ProductLog.php';
		}

		if (class_exists('ProductLog', false)) {
			ProductLog::ensureSchema();
		}
	}
};
