<?php

namespace N11;

class ProductSyncService
{
	public static function ensureSchema(): void
	{
		$products = \DB::execute("SHOW TABLES LIKE 'n11_products'");

		if (empty($products)) {
			\DB::execute(
				"CREATE TABLE `n11_products` (
					`id_product` int(11) NOT NULL,
					`stock_code` varchar(64) NOT NULL DEFAULT '',
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
					KEY `stock_code` (`stock_code`),
					KEY `barcode` (`barcode`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		}

		\MarketplaceTables::ensureSchema();
	}

	public static function isConfigured(): bool
	{
		return self::api()->isConfigured();
	}

	public static function api(): N11Api
	{
		return new N11Api(
			(string) \Settings::get('N11_API_KEY'),
			(string) \Settings::get('N11_API_SECRET')
		);
	}

	/** @return array<string, mixed>|null */
	public static function findMapping(int $idProduct): ?array
	{
		self::ensureSchema();

		if ($idProduct <= 0) {
			return null;
		}

		$row = \DB::getRowSafe('n11_products', 'id_product = ?', [$idProduct]);

		return is_array($row) ? $row : null;
	}

	/** @param array<string, mixed>|null $mapping */
	public static function isLinked(?array $mapping): bool
	{
		return is_array($mapping) && trim((string) ($mapping['stock_code'] ?? '')) !== '';
	}

	/**
	 * @return array{ok: bool, message: string, mapping?: array<string, mixed>}
	 */
	public static function linkExistingProduct(int $idProduct, string $stockCode = ''): array
	{
		self::ensureSchema();

		if (!self::isConfigured()) {
			return ['ok' => false, 'message' => 'N11 API kimlik bilgileri tanımlı değil'];
		}

		$product = \Product::getByIdAdmin($idProduct);

		if (!$product) {
			return ['ok' => false, 'message' => 'Mağaza ürünü bulunamadı'];
		}

		$stockCode = trim($stockCode);

		if ($stockCode === '') {
			$stockCode = trim((string) ($product['stock_code'] ?? ''));
		}

		if ($stockCode === '') {
			return ['ok' => false, 'message' => 'Eşleştirmek için stok kodu gerekli'];
		}

		$result = self::api()->getProduct($stockCode);

		if (self::isApiError($result)) {
			return ['ok' => false, 'message' => (string) ($result['message'] ?? 'N11 ürünü bulunamadı')];
		}

		$content = null;

		if (isset($result['content']) && is_array($result['content'])) {
			$content = isset($result['content'][0]) ? $result['content'][0] : $result['content'];
		} elseif (isset($result['products'][0]) && is_array($result['products'][0])) {
			$content = $result['products'][0];
		} elseif (isset($result[0]) && is_array($result[0])) {
			$content = $result[0];
		} elseif (isset($result['stockCode']) || isset($result['title'])) {
			$content = $result;
		}

		if (!is_array($content)) {
			return ['ok' => false, 'message' => 'Bu stok koduyla N11 ürünü bulunamadı'];
		}

		$salePrice = (float) ($content['salePrice'] ?? ($content['price'] ?? 0));
		$listPrice = (float) ($content['listPrice'] ?? $salePrice);

		self::saveMapping($idProduct, [
			'stock_code' => (string) ($content['stockCode'] ?? $stockCode),
			'barcode' => (string) ($content['barcode'] ?? ($product['barcode'] ?? $stockCode)),
			'sale_price' => $salePrice,
			'list_price' => $listPrice > 0 ? $listPrice : $salePrice,
			'quantity' => (int) ($content['quantity'] ?? ($content['stock'] ?? 0)),
			'last_status' => 'linked',
			'last_error' => '',
			'last_sync_at' => date('Y-m-d H:i:s'),
		]);

		return [
			'ok' => true,
			'message' => 'Mevcut N11 ürünüyle bağlantı oluşturuldu',
			'mapping' => self::findMapping($idProduct),
		];
	}

	/**
	 * @return array{ok: bool, message: string, mapping?: array<string, mixed>}
	 */
	public static function linkFromOrder(
		int $idProduct,
		string $orderStockCode,
		?float $orderSalePrice = null,
		?float $orderListPrice = null
	): array {
		self::ensureSchema();

		if ($idProduct <= 0) {
			return ['ok' => false, 'message' => 'Geçersiz ürün'];
		}

		$mapping = self::findMapping($idProduct);

		if (self::isLinked($mapping)) {
			return ['ok' => true, 'message' => 'N11 bağlantısı mevcut', 'mapping' => $mapping];
		}

		$stockCode = trim($orderStockCode);
		$product = \Product::getByIdAdmin($idProduct);

		if ($stockCode === '' && $product) {
			$stockCode = trim((string) ($product['stock_code'] ?? ''));
		}

		if ($stockCode === '') {
			return ['ok' => false, 'message' => 'N11 eşlemesi için stok kodu gerekli'];
		}

		$fields = [
			'stock_code' => $stockCode,
			'barcode' => $stockCode,
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
			'message' => 'Siparişten N11 bağlantısı oluşturuldu',
			'mapping' => self::findMapping($idProduct),
		];
	}

	/**
	 * @return array{ok: bool, message: string, mapping?: array<string, mixed>}
	 */
	public static function syncAfterOrderStock(
		int $idProduct,
		string $orderStockCode,
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
			$stockCode = trim($orderStockCode);
			$product = \Product::getByIdAdmin($idProduct);

			if ($stockCode === '' && $product) {
				$stockCode = trim((string) ($product['stock_code'] ?? ''));
			}

			if ($stockCode === '') {
				return ['ok' => false, 'message' => 'N11 eşlemesi için stok kodu gerekli'];
			}

			$fields = [
				'stock_code' => $stockCode,
				'barcode' => $stockCode,
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

			return ['ok' => true, 'message' => 'N11 bağlantısı oluşturuldu'];
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

		return ['ok' => true, 'message' => 'N11 bağlantısı mevcut'];
	}

	/**
	 * @return array{ok: bool, message: string, mapping?: array<string, mixed>}
	 */
	public static function sync(int $idProduct, array $meta = []): array
	{
		self::ensureSchema();

		if (!self::isConfigured()) {
			return ['ok' => false, 'message' => 'N11 API kimlik bilgileri tanımlı değil'];
		}

		$product = \Product::getByIdAdmin($idProduct);

		if (!$product) {
			return ['ok' => false, 'message' => 'Ürün bulunamadı'];
		}

		$mapping = self::findMapping($idProduct);

		if (!self::isLinked($mapping)) {
			$stockCode = trim((string) ($meta['stock_code'] ?? ($product['stock_code'] ?? '')));

			if ($stockCode === '') {
				return [
					'ok' => false,
					'message' => 'Önce stok kodu ile eşleştirin',
				];
			}

			$link = self::linkExistingProduct($idProduct, $stockCode);

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
			return ['ok' => false, 'message' => 'N11 API kimlik bilgileri tanımlı değil'];
		}

		$product = \Product::getByIdAdmin($idProduct);
		$mapping = self::findMapping($idProduct);

		if (!$product) {
			return ['ok' => false, 'message' => 'Ürün bulunamadı'];
		}

		$stockCode = trim((string) ($mapping['stock_code'] ?? ($product['stock_code'] ?? '')));

		if ($stockCode === '') {
			return ['ok' => false, 'message' => 'Stok kodu gerekli'];
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
			return ['ok' => false, 'message' => 'N11 satış fiyatı tanımlı değil'];
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
		$oldMapQty = (float) ($mapping['quantity'] ?? 0);
		$oldSale = (float) ($mapping['sale_price'] ?? 0);
		$result = self::api()->updateStockPrice($stockCode, $listPrice, $salePrice, $stock);
		$now = date('Y-m-d H:i:s');

		if (self::isApiError($result)) {
			self::saveMapping($idProduct, [
				'stock_code' => $stockCode,
				'barcode' => (string) ($mapping['barcode'] ?? $stockCode),
				'sale_price' => $salePrice,
				'list_price' => $listPrice,
				'quantity' => $stock,
				'last_status' => 'failed',
				'last_error' => (string) ($result['message'] ?? 'Fiyat/stok güncelleme hatası'),
				'last_sync_at' => $now,
			]);

			return [
				'ok' => false,
				'message' => (string) ($result['message'] ?? 'Fiyat/stok güncelleme hatası'),
			];
		}

		self::saveMapping($idProduct, [
			'stock_code' => $stockCode,
			'barcode' => (string) ($mapping['barcode'] ?? $stockCode),
			'sale_price' => $salePrice,
			'list_price' => $listPrice,
			'quantity' => $stock,
			'last_status' => 'synced',
			'last_error' => '',
			'last_sync_at' => $now,
		]);

		if (abs($oldMapQty - (float) $stock) >= 0.0001) {
			\MarketplaceLog::stockChange('n11', $stockCode, $oldMapQty, $stock, 'PHANTOM_STOCK_CHANGE', $idProduct);
		}

		if ($oldSale > 0 && abs($oldSale - (float) $salePrice) >= 0.0001) {
			\MarketplaceLog::priceUpdate('n11', $stockCode, $oldSale, (float) $salePrice, $idProduct);
		}

		return [
			'ok' => true,
			'message' => 'N11 fiyat/stok güncellendi',
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
			return ['ok' => false, 'message' => 'N11 bağlantısı bulunamadı'];
		}

		\DB::execute('DELETE FROM n11_products WHERE id_product = ?', [$idProduct]);

		return [
			'ok' => true,
			'message' => 'N11 bağlantısı silindi (ürün N11 mağazasında durmaya devam eder)',
		];
	}

	/**
	 * @param array<string, mixed> $line
	 */
	public static function extractOrderLineSalePrice(array $line): ?float
	{
		$candidates = [
			$line['salePrice'] ?? null,
			$line['price'] ?? null,
			$line['amount'] ?? null,
		];

		foreach ($candidates as $raw) {
			if ($raw === null || $raw === '') {
				continue;
			}

			$value = (float) $raw;

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
			if (!isset($line[$key]) || $line[$key] === '') {
				continue;
			}

			$value = (float) $line[$key];

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
		return N11Api::isApiError($result);
	}

	/** @param array<string, mixed> $fields */
	private static function saveMapping(int $idProduct, array $fields): void
	{
		self::ensureSchema();

		$now = date('Y-m-d H:i:s');
		$existing = self::findMapping($idProduct);

		$row = [
			'stock_code' => (string) ($fields['stock_code'] ?? ($existing['stock_code'] ?? '')),
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
			\DB::update('n11_products', $row, 'id_product = :where_id', ['where_id' => $idProduct]);
		} else {
			$row['id_product'] = $idProduct;
			$row['date_add'] = $now;
			\DB::insert('n11_products', $row);
		}
	}
}
