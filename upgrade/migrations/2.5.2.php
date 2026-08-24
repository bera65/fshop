<?php

return new class {
	public string $version = '2.5.2';
	public string $title = 'm² satış birimi, ondalık stok / sipariş miktarı';

	public function up(): void
	{
		Product::ensureSchema();
		Order::ensureSchema();

		if (class_exists('SaleUnit', false) && method_exists('SaleUnit', 'ensureSchema')) {
			SaleUnit::ensureSchema();
		}
	}
};
