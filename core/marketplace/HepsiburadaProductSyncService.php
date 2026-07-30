<?php

namespace Hepsiburada;

class ProductSyncService
{
	public static function ensureSchema(): void
	{
		$products = \DB::execute("SHOW TABLES LIKE 'hepsiburada_products'");

		if (empty($products)) {
			\DB::execute(
				"CREATE TABLE `hepsiburada_products` (
					`id_product` int(11) NOT NULL,
					`merchant_sku` varchar(64) NOT NULL DEFAULT '',
					`hepsiburada_sku` varchar(64) NOT NULL DEFAULT '',
					`barcode` varchar(64) NOT NULL DEFAULT '',
					`sale_price` decimal(20,2) NOT NULL DEFAULT 0.00,
					`list_price` decimal(20,2) NOT NULL DEFAULT 0.00,
					`quantity` int(11) NOT NULL DEFAULT 0,
					`last_status` varchar(32) NOT NULL DEFAULT '',
					`last_error` text NULL,
					`last_sync_at` datetime NULL,
					`date_add` datetime NOT NULL,
					`date_upd` datetime NOT NULL,
					PRIMARY KEY (`id_product`),
					KEY `merchant_sku` (`merchant_sku`),
					KEY `hepsiburada_sku` (`hepsiburada_sku`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		}

		\MarketplaceTables::ensureSchema();
	}

	public static function isConfigured(): bool
	{
		return self::api()->isConfigured();
	}

	public static function api(): HepsiburadaApi
	{
		return new HepsiburadaApi(
			(string) \Settings::get('HEPSIBURADA_MERCHANT_ID'),
			(string) \Settings::get('HEPSIBURADA_API_KEY'),
			(string) \Settings::get('HEPSIBURADA_API_PASS')
		);
	}

	/** @return array<string, mixed>|null */
	public static function findMapping(int $idProduct): ?array
	{
		self::ensureSchema();

		if ($idProduct <= 0) {
			return null;
		}

		$row = \DB::getRowSafe('hepsiburada_products', 'id_product = ?', [$idProduct]);

		return is_array($row) ? $row : null;
	}

	/** @param array<string, mixed>|null $mapping */
	public static function isLinked(?array $mapping): bool
	{
		if (!is_array($mapping)) {
			return false;
		}

		return trim((string) ($mapping['merchant_sku'] ?? '')) !== ''
			|| trim((string) ($mapping['hepsiburada_sku'] ?? '')) !== '';
	}

	/**
	 * @return array{ok: bool, message: string, mapping?: array<string, mixed>}
	 */
	public static function linkExistingProduct(int $idProduct, string $merchantSku = ''): array
	{
		self::ensureSchema();

		if (!self::isConfigured()) {
			return ['ok' => false, 'message' => 'Hepsiburada API kimlik bilgileri tanımlı değil'];
		}

		$product = \Product::getByIdAdmin($idProduct);

		if (!$product) {
			return ['ok' => false, 'message' => 'Mağaza ürünü bulunamadı'];
		}

		$merchantSku = trim($merchantSku);

		if ($merchantSku === '') {
			$merchantSku = trim((string) ($product['stock_code'] ?? ''));
		}

		if ($merchantSku === '') {
			return ['ok' => false, 'message' => 'Eşleştirmek için merchant SKU gerekli'];
		}

		$result = self::api()->getProduct($merchantSku);

		if (self::isApiError($result)) {
			return ['ok' => false, 'message' => (string) ($result['message'] ?? 'Hepsiburada ürünü bulunamadı')];
		}

		$listing = null;

		if (isset($result['listings'][0]) && is_array($result['listings'][0])) {
			$listing = $result['listings'][0];
		}

		if (!is_array($listing)) {
			return ['ok' => false, 'message' => 'Bu merchant SKU ile Hepsiburada listesi bulunamadı'];
		}

		$salePrice = (float) ($listing['price'] ?? ($listing['salePrice'] ?? 0));
		$listPrice = (float) ($listing['listPrice'] ?? $salePrice);

		self::saveMapping($idProduct, [
			'merchant_sku' => (string) ($listing['merchantSku'] ?? $merchantSku),
			'hepsiburada_sku' => (string) ($listing['hepsiburadaSku'] ?? ''),
			'barcode' => (string) ($listing['barcode'] ?? ($product['barcode'] ?? '')),
			'sale_price' => $salePrice,
			'list_price' => $listPrice > 0 ? $listPrice : $salePrice,
			'quantity' => (int) ($listing['availableStock'] ?? ($listing['stock'] ?? 0)),
			'last_status' => 'linked',
			'last_error' => '',
			'last_sync_at' => date('Y-m-d H:i:s'),
		]);

		return [
			'ok' => true,
			'message' => 'Mevcut Hepsiburada ürünüyle bağlantı oluşturuldu',
			'mapping' => self::findMapping($idProduct),
		];
	}

	/**
	 * @return array{ok: bool, message: string, mapping?: array<string, mixed>}
	 */
	public static function linkFromOrder(
		int $idProduct,
		string $orderMerchantSku,
		?float $orderSalePrice = null,
		?float $orderListPrice = null
	): array {
		self::ensureSchema();

		if ($idProduct <= 0) {
			return ['ok' => false, 'message' => 'Geçersiz ürün'];
		}

		$mapping = self::findMapping($idProduct);

		if (self::isLinked($mapping)) {
			return ['ok' => true, 'message' => 'Hepsiburada bağlantısı mevcut', 'mapping' => $mapping];
		}

		$merchantSku = trim($orderMerchantSku);
		$product = \Product::getByIdAdmin($idProduct);

		if ($merchantSku === '' && $product) {
			$merchantSku = trim((string) ($product['stock_code'] ?? ''));
		}

		if ($merchantSku === '') {
			return ['ok' => false, 'message' => 'Hepsiburada eşlemesi için merchant SKU gerekli'];
		}

		$fields = [
			'merchant_sku' => $merchantSku,
			'last_status' => 'linked',
			'last_error' => '',
		];

		if ($orderSalePrice !== null && $orderSalePrice > 0) {
			$fields['sale_price'] = round($orderSalePrice, 2);
			$fields['list_price'] = round(
				($orderListPrice !== null && $orderListPrice > $orderSalePrice) ? $orderListPrice : $orderSalePrice,
				2
			);
		}

		self::saveMapping($idProduct, $fields);

		return [
			'ok' => true,
			'message' => 'Siparişten Hepsiburada bağlantısı oluşturuldu',
			'mapping' => self::findMapping($idProduct),
		];
	}

	/**
	 * @return array{ok: bool, message: string, mapping?: array<string, mixed>}
	 */
	public static function syncAfterOrderStock(
		int $idProduct,
		string $orderMerchantSku,
		?float $orderSalePrice = null,
		?float $orderListPrice = null
	): array {
		self::ensureSchema();

		if ($idProduct <= 0) {
			return ['ok' => false, 'message' => 'Geçersiz ürün'];
		}

		$mapping = self::findMapping($idProduct);
		$linked = self::isLinked($mapping);

		if (!$linked) {
			$merchantSku = trim($orderMerchantSku);
			$product = \Product::getByIdAdmin($idProduct);

			if ($merchantSku === '' && $product) {
				$merchantSku = trim((string) ($product['stock_code'] ?? ''));
			}

			if ($merchantSku === '') {
				return ['ok' => false, 'message' => 'Hepsiburada eşlemesi için merchant SKU gerekli'];
			}

			$fields = [
				'merchant_sku' => $merchantSku,
				'last_status' => 'linked',
				'last_error' => '',
			];

			if ($orderSalePrice !== null && $orderSalePrice > 0) {
				$listPrice = ($orderListPrice !== null && $orderListPrice > $orderSalePrice)
					? $orderListPrice
					: $orderSalePrice;
				$fields['sale_price'] = round($orderSalePrice, 2);
				$fields['list_price'] = round($listPrice, 2);
			}

			self::saveMapping($idProduct, $fields);

			if ($orderSalePrice !== null && $orderSalePrice > 0) {
				return self::updatePriceStock($idProduct);
			}

			return ['ok' => true, 'message' => 'Hepsiburada bağlantısı oluşturuldu'];
		}

		if ((float) ($mapping['sale_price'] ?? 0) <= 0 && $orderSalePrice !== null && $orderSalePrice > 0) {
			$listPrice = ($orderListPrice !== null && $orderListPrice > $orderSalePrice)
				? $orderListPrice
				: $orderSalePrice;

			self::saveMapping($idProduct, [
				'sale_price' => round($orderSalePrice, 2),
				'list_price' => round($listPrice, 2),
				'last_status' => 'linked',
				'last_error' => '',
			]);

			return self::updatePriceStock($idProduct);
		}

		if ((float) ($mapping['sale_price'] ?? 0) > 0) {
			return self::updatePriceStock($idProduct);
		}

		return ['ok' => true, 'message' => 'Hepsiburada bağlantısı mevcut'];
	}

	/**
	 * Basic sync: merchant_sku from stock_code; update price/stock if linked.
	 *
	 * @return array{ok: bool, message: string, mapping?: array<string, mixed>}
	 */
	public static function sync(int $idProduct, array $meta = []): array
	{
		self::ensureSchema();

		if (!self::isConfigured()) {
			return ['ok' => false, 'message' => 'Hepsiburada API kimlik bilgileri tanımlı değil'];
		}

		$product = \Product::getByIdAdmin($idProduct);

		if (!$product) {
			return ['ok' => false, 'message' => 'Ürün bulunamadı'];
		}

		$mapping = self::findMapping($idProduct);

		if (!self::isLinked($mapping)) {
			$merchantSku = trim((string) ($meta['merchant_sku'] ?? ($product['stock_code'] ?? '')));

			if ($merchantSku === '') {
				return [
					'ok' => false,
					'message' => 'Önce merchant SKU ile eşleştirin (ürün stok kodu veya mevcut liste)',
				];
			}

			$link = self::linkExistingProduct($idProduct, $merchantSku);

			if (!$link['ok']) {
				return $link;
			}
		}

		return self::updatePriceStock(
			$idProduct,
			isset($meta['sale_price']) ? (float) $meta['sale_price'] : null,
			isset($meta['list_price']) ? (float) $meta['list_price'] : null
		);
	}

	/**
	 * @return array{ok: bool, message: string, mapping?: array<string, mixed>}
	 */
	public static function updatePriceStock(int $idProduct, ?float $saleOverride = null, ?float $listOverride = null): array
	{
		self::ensureSchema();

		if (!self::isConfigured()) {
			return ['ok' => false, 'message' => 'Hepsiburada API kimlik bilgileri tanımlı değil'];
		}

		$product = \Product::getByIdAdmin($idProduct);
		$mapping = self::findMapping($idProduct);

		if (!$product) {
			return ['ok' => false, 'message' => 'Ürün bulunamadı'];
		}

		$merchantSku = trim((string) ($mapping['merchant_sku'] ?? ($product['stock_code'] ?? '')));
		$hbSku = trim((string) ($mapping['hepsiburada_sku'] ?? ''));

		if ($merchantSku === '' && $hbSku === '') {
			return ['ok' => false, 'message' => 'Merchant SKU gerekli'];
		}

		$salePrice = null;

		if ($saleOverride !== null && $saleOverride > 0) {
			$salePrice = $saleOverride;
		} elseif ($mapping && (float) ($mapping['sale_price'] ?? 0) > 0) {
			$salePrice = (float) $mapping['sale_price'];
		} else {
			$salePrice = (float) ($product['price'] ?? 0);
		}

		if ($salePrice <= 0) {
			return ['ok' => false, 'message' => 'Hepsiburada satış fiyatı tanımlı değil'];
		}

		$listPrice = $salePrice;

		if ($listOverride !== null && $listOverride > 0) {
			$listPrice = $listOverride;
		} elseif ($mapping && (float) ($mapping['list_price'] ?? 0) > 0) {
			$listPrice = (float) $mapping['list_price'];
		}

		if ($listPrice < $salePrice) {
			$listPrice = $salePrice;
		}

		$stock = max(0, \Product::getStock($product));
		$skuForApi = $merchantSku !== '' ? $merchantSku : $hbSku;
		$oldMapQty = (float) ($mapping['quantity'] ?? 0);
		$oldSale = (float) ($mapping['sale_price'] ?? 0);
		$combined = self::api()->updateStockPrice(
			$skuForApi,
			$listPrice,
			$stock,
			$hbSku !== '' ? $hbSku : null
		);

		$now = date('Y-m-d H:i:s');
		$priceErr = self::isApiError($combined['price'] ?? null);
		$stockErr = self::isApiError($combined['stock'] ?? null);

		if ($priceErr || $stockErr) {
			$msg = '';

			if ($priceErr) {
				$msg .= (string) (($combined['price']['message'] ?? '') ?: 'Fiyat güncelleme hatası');
			}

			if ($stockErr) {
				$msg .= ($msg !== '' ? '; ' : '')
					. (string) (($combined['stock']['message'] ?? '') ?: 'Stok güncelleme hatası');
			}

			self::saveMapping($idProduct, [
				'merchant_sku' => $merchantSku,
				'hepsiburada_sku' => $hbSku,
				'sale_price' => $salePrice,
				'list_price' => $listPrice,
				'quantity' => $stock,
				'last_status' => 'failed',
				'last_error' => $msg,
				'last_sync_at' => $now,
			]);

			return ['ok' => false, 'message' => $msg];
		}

		self::saveMapping($idProduct, [
			'merchant_sku' => $merchantSku,
			'hepsiburada_sku' => $hbSku,
			'sale_price' => $salePrice,
			'list_price' => $listPrice,
			'quantity' => $stock,
			'last_status' => 'synced',
			'last_error' => '',
			'last_sync_at' => $now,
		]);

		$ref = $merchantSku !== '' ? $merchantSku : $hbSku;

		if (abs($oldMapQty - (float) $stock) >= 0.0001) {
			\MarketplaceLog::stockChange('hepsiburada', $ref, $oldMapQty, $stock, 'PHANTOM_STOCK_CHANGE', $idProduct);
		}

		if ($oldSale > 0 && abs($oldSale - (float) $salePrice) >= 0.0001) {
			\MarketplaceLog::priceUpdate('hepsiburada', $ref, $oldSale, (float) $salePrice, $idProduct);
		}

		return [
			'ok' => true,
			'message' => 'Hepsiburada fiyat/stok güncellendi',
			'mapping' => self::findMapping($idProduct),
		];
	}

	/**
	 * @return array{ok: bool, message: string}
	 */
	public static function unlinkProduct(int $idProduct): array
	{
		self::ensureSchema();

		if ($idProduct <= 0) {
			return ['ok' => false, 'message' => 'Geçersiz ürün'];
		}

		if (!\Product::getByIdAdmin($idProduct)) {
			return ['ok' => false, 'message' => 'Ürün bulunamadı'];
		}

		if (!self::findMapping($idProduct)) {
			return ['ok' => false, 'message' => 'Hepsiburada bağlantısı bulunamadı'];
		}

		\DB::execute('DELETE FROM hepsiburada_products WHERE id_product = ?', [$idProduct]);

		return [
			'ok' => true,
			'message' => 'Hepsiburada bağlantısı silindi (ürün Hepsiburada mağazasında durmaya devam eder)',
		];
	}

	/**
	 * @param array<string, mixed> $line
	 */
	public static function extractOrderLineSalePrice(array $line): ?float
	{
		$quantity = max(1, (int) ($line['quantity'] ?? 1));

		if (isset($line['unitPrice']['amount']) && (float) $line['unitPrice']['amount'] > 0) {
			return round((float) $line['unitPrice']['amount'], 2);
		}

		if (isset($line['price']['amount']) && (float) $line['price']['amount'] > 0) {
			return round((float) $line['price']['amount'], 2);
		}

		if (isset($line['totalPrice']['amount']) && (float) $line['totalPrice']['amount'] > 0) {
			return round((float) $line['totalPrice']['amount'] / $quantity, 2);
		}

		if (isset($line['merchantTotalPrice']['amount']) && (float) $line['merchantTotalPrice']['amount'] > 0) {
			return round((float) $line['merchantTotalPrice']['amount'] / $quantity, 2);
		}

		foreach (['price', 'unitPrice', 'salePrice', 'amount'] as $key) {
			if (!isset($line[$key]) || is_array($line[$key])) {
				continue;
			}

			$value = (float) $line[$key];

			if ($value > 0) {
				return round($value, 2);
			}
		}

		return null;
	}

	/**
	 * @param array<string, mixed> $line
	 */
	public static function extractOrderLineListPrice(array $line, ?float $salePrice): ?float
	{
		foreach (['listPrice', 'lineListPrice'] as $key) {
			if (!isset($line[$key])) {
				continue;
			}

			$raw = $line[$key];

			if (is_array($raw) && isset($raw['amount'])) {
				$raw = $raw['amount'];
			}

			$value = (float) $raw;

			if ($value > 0) {
				return round($value, 2);
			}
		}

		if ($salePrice !== null && $salePrice > 0) {
			return $salePrice;
		}

		return null;
	}

	/** @param mixed $result */
	public static function isApiError($result): bool
	{
		return HepsiburadaApi::isApiError($result);
	}

	/** @param array<string, mixed> $fields */
	private static function saveMapping(int $idProduct, array $fields): void
	{
		self::ensureSchema();

		$now = date('Y-m-d H:i:s');
		$existing = self::findMapping($idProduct);

		$row = [
			'merchant_sku' => (string) ($fields['merchant_sku'] ?? ($existing['merchant_sku'] ?? '')),
			'hepsiburada_sku' => (string) ($fields['hepsiburada_sku'] ?? ($existing['hepsiburada_sku'] ?? '')),
			'barcode' => (string) ($fields['barcode'] ?? ($existing['barcode'] ?? '')),
			'sale_price' => (float) ($fields['sale_price'] ?? ($existing['sale_price'] ?? 0)),
			'list_price' => (float) ($fields['list_price'] ?? ($existing['list_price'] ?? 0)),
			'quantity' => (int) ($fields['quantity'] ?? ($existing['quantity'] ?? 0)),
			'last_status' => (string) ($fields['last_status'] ?? ($existing['last_status'] ?? '')),
			'last_error' => (string) ($fields['last_error'] ?? ''),
			'last_sync_at' => (string) ($fields['last_sync_at'] ?? $now),
			'date_upd' => $now,
		];

		if ($existing) {
			\DB::update('hepsiburada_products', $row, 'id_product = :where_id', ['where_id' => $idProduct]);
		} else {
			$row['id_product'] = $idProduct;
			$row['date_add'] = $now;
			\DB::insert('hepsiburada_products', $row);
		}
	}
}
