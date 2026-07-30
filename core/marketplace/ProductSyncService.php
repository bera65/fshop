<?php

namespace Trendyol;

class ProductSyncService
{
	public static function ensureSchema(): void
	{
		$products = \DB::execute("SHOW TABLES LIKE 'trendyol_products'");

		if (empty($products)) {
			\DB::execute(
				"CREATE TABLE `trendyol_products` (
					`id_product` int(11) NOT NULL,
					`barcode` varchar(64) NOT NULL DEFAULT '',
					`content_id` varchar(64) NOT NULL DEFAULT '',
					`product_url` varchar(512) NOT NULL DEFAULT '',
					`brand_id` int(11) NOT NULL DEFAULT 0,
					`category_id` int(11) NOT NULL DEFAULT 0,
					`attributes_json` text NULL,
					`sale_price` decimal(20,2) NOT NULL DEFAULT 0.00,
					`list_price` decimal(20,2) NOT NULL DEFAULT 0.00,
					`quantity` int(11) NOT NULL DEFAULT 0,
					`approved` tinyint(1) NOT NULL DEFAULT 0,
					`batch_request_id` varchar(128) NOT NULL DEFAULT '',
					`last_status` varchar(32) NOT NULL DEFAULT '',
					`last_error` text NULL,
					`last_sync_at` datetime NULL,
					`date_add` datetime NOT NULL,
					`date_upd` datetime NOT NULL,
					PRIMARY KEY (`id_product`),
					KEY `barcode` (`barcode`),
					KEY `content_id` (`content_id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		}

		$map = \DB::execute("SHOW TABLES LIKE 'trendyol_category_map'");

		if (empty($map)) {
			\DB::execute(
				"CREATE TABLE `trendyol_category_map` (
					`id_category` int(11) NOT NULL,
					`trendyol_category_id` int(11) NOT NULL DEFAULT 0,
					`trendyol_category_name` varchar(512) NOT NULL DEFAULT '',
					`attributes_json` text NULL,
					`date_upd` datetime NOT NULL,
					PRIMARY KEY (`id_category`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		} else {
			$col = \DB::execute("SHOW COLUMNS FROM `trendyol_category_map` LIKE 'trendyol_category_name'");

			if (empty($col)) {
				\DB::execute(
					"ALTER TABLE `trendyol_category_map`
					 ADD COLUMN `trendyol_category_name` varchar(512) NOT NULL DEFAULT '' AFTER `trendyol_category_id`"
				);
			}
		}

		\MarketplaceTables::ensureSchema();
	}

	public static function isConfigured(): bool
	{
		return self::api()->isConfigured();
	}

	public static function api(): TrendyolApi
	{
		return new TrendyolApi(
			(string) \Settings::get('TRENDYOL_MERCHANT_ID'),
			(string) \Settings::get('TRENDYOL_API_KEY'),
			(string) \Settings::get('TRENDYOL_API_SECRET')
		);
	}

	/** @return array<string, mixed>|null */
	public static function findMapping(int $idProduct): ?array
	{
		self::ensureSchema();

		if ($idProduct <= 0) {
			return null;
		}

		$row = \DB::getRowSafe('trendyol_products', 'id_product = ?', [$idProduct]);

		return is_array($row) ? $row : null;
	}

	/**
	 * @param array<string, mixed> $meta brand_id, category_id, attributes (map)
	 * @return array{ok: bool, message: string, mapping?: array<string, mixed>, data?: mixed}
	 */
	public static function sync(int $idProduct, array $meta = []): array
	{
		self::ensureSchema();

		if (!self::isConfigured()) {
			return ['ok' => false, 'message' => 'Trendyol API kimlik bilgileri tanımlı değil'];
		}

		$product = \Product::getByIdAdmin($idProduct);

		if (!$product) {
			return ['ok' => false, 'message' => 'Ürün bulunamadı'];
		}

		$existing = self::findMapping($idProduct) ?: [];
		$meta = self::mergeMeta($product, $existing, $meta);
		$build = self::buildPayload($product, $meta);

		if (!$build['ok']) {
			return ['ok' => false, 'message' => $build['message']];
		}

		$payload = ['items' => [$build['payload']]];
		$api = self::api();
		$isUpdate = trim((string) ($existing['content_id'] ?? '')) !== ''
			|| trim((string) ($existing['last_status'] ?? '')) === 'synced';

		if ($isUpdate) {
			$result = $api->updateProduct($payload);
		} else {
			$result = $api->createProduct($payload);
		}

		$now = date('Y-m-d H:i:s');

		if (self::isApiError($result)) {
			self::saveMapping($idProduct, [
				'barcode' => (string) ($build['payload']['barcode'] ?? ''),
				'brand_id' => (int) ($meta['brand_id'] ?? 0),
				'category_id' => (int) ($meta['category_id'] ?? 0),
				'attributes_json' => json_encode($meta['attributes'] ?? [], JSON_UNESCAPED_UNICODE),
				'sale_price' => (float) ($build['payload']['salePrice'] ?? 0),
				'list_price' => (float) ($build['payload']['listPrice'] ?? 0),
				'quantity' => (int) ($build['payload']['quantity'] ?? 0),
				'last_status' => 'failed',
				'last_error' => (string) ($result['message'] ?? 'Trendyol API hatası'),
				'last_sync_at' => $now,
			]);

			return [
				'ok' => false,
				'message' => (string) ($result['message'] ?? 'Trendyol API hatası'),
				'data' => $result,
			];
		}

		$batchId = (string) ($result['batchRequestId'] ?? '');
		$mappingFields = [
			'barcode' => (string) ($build['payload']['barcode'] ?? ''),
			'brand_id' => (int) ($meta['brand_id'] ?? 0),
			'category_id' => (int) ($meta['category_id'] ?? 0),
			'attributes_json' => json_encode($meta['attributes'] ?? [], JSON_UNESCAPED_UNICODE),
			'sale_price' => (float) ($build['payload']['salePrice'] ?? 0),
			'list_price' => (float) ($build['payload']['listPrice'] ?? 0),
			'quantity' => (int) ($build['payload']['quantity'] ?? 0),
			'batch_request_id' => $batchId,
			'last_status' => 'synced',
			'last_error' => '',
			'last_sync_at' => $now,
		];

		self::saveMapping($idProduct, $mappingFields);
		self::refreshFromTrendyol($idProduct);
		return [
			'ok' => true,
			'message' => $isUpdate ? 'Ürün Trendyol\'da güncellendi' : 'Ürün Trendyol\'a aktarıldı',
			'mapping' => self::findMapping($idProduct),
			'data' => $result,
		];
	}

	/**
	 * @return array{ok: bool, message: string, mapping?: array<string, mixed>}
	 */
	public static function syncAfterOrderStock(
		int $idProduct,
		string $orderBarcode,
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
			$barcode = trim($orderBarcode);
			$product = \Product::getByIdAdmin($idProduct);

			if ($barcode === '' && $product) {
				$barcode = trim((string) ($product['barcode'] ?? ''));
			}

			if ($barcode === '') {
				return ['ok' => false, 'message' => 'Trendyol eşlemesi için barkod gerekli'];
			}

			$fields = [
				'barcode' => $barcode,
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

			return ['ok' => true, 'message' => 'Trendyol bağlantısı oluşturuldu'];
		}

		if (!self::hasTrendyolPrice($mapping) && $orderSalePrice !== null && $orderSalePrice > 0) {
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

		if (self::hasTrendyolPrice($mapping)) {
			return self::updatePriceStock($idProduct);
		}

		return ['ok' => true, 'message' => 'Trendyol bağlantısı mevcut'];
	}

	/**
	 * Sipariş içe aktarımında bulunan mağaza ürününü Trendyol satırıyla yerel olarak eşleştirir.
	 * Bu işlem Trendyol'a fiyat/stok göndermez; siparişteki brüt satış fiyatını yalnızca eşlemeye kaydeder.
	 *
	 * @return array{ok: bool, message: string, mapping?: array<string, mixed>}
	 */
	public static function linkFromOrder(int $idProduct, string $orderBarcode, ?float $orderSalePrice = null, ?float $orderListPrice = null): array
	{
		self::ensureSchema();

		if ($idProduct <= 0) {
			return ['ok' => false, 'message' => 'Geçersiz ürün'];
		}

		$mapping = self::findMapping($idProduct);

		if (self::isLinked($mapping)) {
			return ['ok' => true, 'message' => 'Trendyol bağlantısı mevcut', 'mapping' => $mapping];
		}

		$barcode = trim($orderBarcode);
		$product = \Product::getByIdAdmin($idProduct);

		if ($barcode === '' && $product) {
			$barcode = trim((string) ($product['barcode'] ?? ''));
		}

		if ($barcode === '') {
			return ['ok' => false, 'message' => 'Trendyol eşlemesi için barkod gerekli'];
		}

		$fields = [
			'barcode' => $barcode,
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
			'message' => 'Siparişten Trendyol bağlantısı oluşturuldu',
			'mapping' => self::findMapping($idProduct),
		];
	}

	/** @param array<string, mixed>|null $mapping */
	public static function isLinked(?array $mapping): bool
	{
		return is_array($mapping) && trim((string) ($mapping['barcode'] ?? '')) !== '';
	}

	/** @param array<string, mixed>|null $mapping */
	public static function hasTrendyolPrice(?array $mapping): bool
	{
		return is_array($mapping) && (float) ($mapping['sale_price'] ?? 0) > 0;
	}

	/**
	 * Trendyol satış fiyatı — indirim/kupon öncesi brüt birim fiyat (lineGrossAmount).
	 *
	 * @param array<string, mixed> $line
	 */
	public static function extractOrderLineSalePrice(array $line): ?float
	{
		$quantity = max(1, (int) ($line['quantity'] ?? 1));
		$lineGross = $line['lineGrossAmount'] ?? null;

		if ($lineGross !== null && $lineGross !== '' && (float) $lineGross > 0) {
			return round((float) $lineGross / $quantity, 2);
		}

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
		$candidates = [
			$line['listPrice'] ?? null,
			$line['lineListPrice'] ?? null,
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

		if ($salePrice !== null && $salePrice > 0) {
			return $salePrice;
		}

		return null;
	}

	/**
	 * FShop içindeki Trendyol eşlemesini siler; Trendyol mağazasındaki ürüne dokunmaz.
	 *
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
			return ['ok' => false, 'message' => 'Trendyol bağlantısı bulunamadı'];
		}

		\DB::execute('DELETE FROM trendyol_products WHERE id_product = ?', [$idProduct]);

		return [
			'ok' => true,
			'message' => 'Trendyol bağlantısı silindi (ürün Trendyol mağazasında durmaya devam eder)',
		];
	}

	/**
	 * @return array{ok: bool, message: string, mapping?: array<string, mixed>}
	 */
	public static function updatePriceStock(int $idProduct, ?float $saleOverride = null, ?float $listOverride = null): array
	{
		self::ensureSchema();

		if (!self::isConfigured()) {
			return ['ok' => false, 'message' => 'Trendyol API kimlik bilgileri tanımlı değil'];
		}

		$product = \Product::getByIdAdmin($idProduct);
		$mapping = self::findMapping($idProduct);

		if (!$product) {
			return ['ok' => false, 'message' => 'Ürün bulunamadı'];
		}

		$barcode = trim((string) ($mapping['barcode'] ?? ($product['barcode'] ?? '')));

		if ($barcode === '') {
			return ['ok' => false, 'message' => 'Barkod gerekli'];
		}

		$salePrice = null;
		$listPrice = null;

		if ($saleOverride !== null && $saleOverride > 0) {
			$salePrice = $saleOverride;
		} elseif ($mapping && (float) ($mapping['sale_price'] ?? 0) > 0) {
			$salePrice = (float) $mapping['sale_price'];
		}

		if ($listOverride !== null && $listOverride > 0) {
			$listPrice = $listOverride;
		} elseif ($mapping && (float) ($mapping['list_price'] ?? 0) > 0) {
			$listPrice = (float) $mapping['list_price'];
		}

		if ($salePrice === null || $salePrice <= 0) {
			return ['ok' => false, 'message' => 'Trendyol satış fiyatı tanımlı değil (ürün panelinden veya sipariş eşlemesinden)'];
		}

		if ($listPrice === null || $listPrice <= 0) {
			$listPrice = $salePrice;
		}

		if ($listPrice <= $salePrice) {
			$listPrice = $salePrice;
		}

		$stock = max(0, \Product::getStock($product));
		$sku = trim((string) ($product['stock_code'] ?? ''));
		$oldMapQty = (float) ($mapping['quantity'] ?? 0);
		$oldSale = (float) ($mapping['sale_price'] ?? 0);

		$result = self::api()->updateStockPrice($barcode, $listPrice, $salePrice, $stock, $sku !== '' ? $sku : null);
		$now = date('Y-m-d H:i:s');

		if (self::isApiError($result)) {
			self::saveMapping($idProduct, [
				'barcode' => $barcode,
				'sale_price' => $salePrice,
				'list_price' => $listPrice,
				'quantity' => $stock,
				'last_status' => 'failed',
				'last_error' => (string) ($result['message'] ?? 'Fiyat güncelleme hatası'),
				'last_sync_at' => $now,
			]);

			return [
				'ok' => false,
				'message' => (string) ($result['message'] ?? 'Fiyat güncelleme hatası'),
			];
		}

		$batchId = (string) ($result['batchRequestId'] ?? ($mapping['batch_request_id'] ?? ''));

		self::saveMapping($idProduct, [
			'barcode' => $barcode,
			'sale_price' => $salePrice,
			'list_price' => $listPrice,
			'quantity' => $stock,
			'batch_request_id' => $batchId,
			'last_status' => 'synced',
			'last_error' => '',
			'last_sync_at' => $now,
		]);

		$ref = $sku !== '' ? $sku : $barcode;

		if (abs($oldMapQty - (float) $stock) >= 0.0001) {
			\MarketplaceLog::stockChange('trendyol', $ref, $oldMapQty, $stock, 'PHANTOM_STOCK_CHANGE', $idProduct);
		}

		if ($oldSale > 0 && abs($oldSale - (float) $salePrice) >= 0.0001) {
			\MarketplaceLog::priceUpdate('trendyol', $ref, $oldSale, (float) $salePrice, $idProduct);
		}

		// Trendyol fiyat/stok güncellemelerini asenkron işler. Burada hemen tekrar
		// sorgulamak eski fiyatı döndürebildiği için kullanıcının girdiği değer yerelde korunur.
		return [
			'ok' => true,
			'message' => 'Trendyol fiyat/stok güncellendi',
			'mapping' => self::findMapping($idProduct),
		];
	}

	/**
	 * @return array{ok: bool, message: string, mapping?: array<string, mixed>}
	 */
	public static function refreshFromTrendyol(int $idProduct): array
	{
		self::ensureSchema();

		$mapping = self::findMapping($idProduct);
		$product = \Product::getByIdAdmin($idProduct);
		$barcode = trim((string) ($mapping['barcode'] ?? ($product['barcode'] ?? '')));

		if ($barcode === '') {
			return ['ok' => false, 'message' => 'Barkod yok'];
		}

		$result = self::api()->getProduct($barcode);

		if (self::isApiError($result)) {
			return ['ok' => false, 'message' => (string) ($result['message'] ?? 'Ürün bilgisi alınamadı')];
		}

		$content = null;

		if (isset($result['content']) && is_array($result['content']) && isset($result['content'][0])) {
			$content = $result['content'][0];
		} elseif (isset($result[0]) && is_array($result[0])) {
			$content = $result[0];
		}

		if (!is_array($content)) {
			return ['ok' => false, 'message' => 'Trendyol ürün kaydı henüz görünmüyor'];
		}

		self::saveMapping($idProduct, [
			'barcode' => (string) ($content['barcode'] ?? $barcode),
			'content_id' => (string) ($content['contentId'] ?? ($content['id'] ?? ($mapping['content_id'] ?? ''))),
			'product_url' => (string) ($content['productUrl'] ?? ($content['url'] ?? ($mapping['product_url'] ?? ''))),
			'brand_id' => (int) ($content['brandId'] ?? ($mapping['brand_id'] ?? 0)),
			'category_id' => (int) ($content['pimCategoryId'] ?? ($content['categoryId'] ?? ($mapping['category_id'] ?? 0))),
			'sale_price' => (float) ($content['salePrice'] ?? ($mapping['sale_price'] ?? 0)),
			'list_price' => (float) ($content['listPrice'] ?? ($mapping['list_price'] ?? 0)),
			'quantity' => (int) ($content['quantity'] ?? ($mapping['quantity'] ?? 0)),
			'approved' => !empty($content['approved']) ? 1 : 0,
			'last_status' => 'synced',
			'last_error' => '',
			'last_sync_at' => date('Y-m-d H:i:s'),
		]);

		return [
			'ok' => true,
			'message' => 'Trendyol ürün bilgisi yenilendi',
			'mapping' => self::findMapping($idProduct),
		];
	}

	/**
	 * FShop ürününü Trendyol'da mevcut olan barkodlu ürünle eşleştirir.
	 * Ürün oluşturmaz ve Trendyol'a fiyat/stok göndermez.
	 *
	 * @return array{ok: bool, message: string, mapping?: array<string, mixed>}
	 */
	public static function linkExistingProduct(int $idProduct, string $barcode = ''): array
	{
		self::ensureSchema();

		if (!self::isConfigured()) {
			return ['ok' => false, 'message' => 'Trendyol API kimlik bilgileri tanımlı değil'];
		}

		$product = \Product::getByIdAdmin($idProduct);

		if (!$product) {
			return ['ok' => false, 'message' => 'Mağaza ürünü bulunamadı'];
		}

		$barcode = trim($barcode);

		if ($barcode === '') {
			$barcode = trim((string) ($product['barcode'] ?? ''));
		}

		if ($barcode === '') {
			return ['ok' => false, 'message' => 'Eşleştirmek için Trendyol barkodu gerekli'];
		}

		$result = self::api()->getProduct($barcode);

		if (self::isApiError($result)) {
			return ['ok' => false, 'message' => (string) ($result['message'] ?? 'Trendyol ürünü bulunamadı')];
		}

		$content = null;

		if (isset($result['content']) && is_array($result['content']) && isset($result['content'][0])) {
			$content = $result['content'][0];
		} elseif (isset($result[0]) && is_array($result[0])) {
			$content = $result[0];
		}

		if (!is_array($content)) {
			return ['ok' => false, 'message' => 'Bu barkodla Trendyol ürünü bulunamadı'];
		}

		self::saveMapping($idProduct, [
			'barcode' => (string) ($content['barcode'] ?? $barcode),
			'content_id' => (string) ($content['contentId'] ?? ($content['id'] ?? '')),
			'product_url' => (string) ($content['productUrl'] ?? ($content['url'] ?? '')),
			'brand_id' => (int) ($content['brandId'] ?? 0),
			'category_id' => (int) ($content['pimCategoryId'] ?? ($content['categoryId'] ?? 0)),
			'sale_price' => (float) ($content['salePrice'] ?? 0),
			'list_price' => (float) ($content['listPrice'] ?? ($content['salePrice'] ?? 0)),
			'quantity' => (int) ($content['quantity'] ?? 0),
			'approved' => !empty($content['approved']) ? 1 : 0,
			'last_status' => 'linked',
			'last_error' => '',
			'last_sync_at' => date('Y-m-d H:i:s'),
		]);

		return [
			'ok' => true,
			'message' => 'Mevcut Trendyol ürünüyle bağlantı oluşturuldu',
			'mapping' => self::findMapping($idProduct),
		];
	}

	/**
	 * @param array<string, mixed> $product
	 * @param array<string, mixed> $existing
	 * @param array<string, mixed> $meta
	 * @return array{brand_id: int, category_id: int, attributes: array<string, mixed>, sale_price: ?float, list_price: ?float}
	 */
	private static function mergeMeta(array $product, array $existing, array $meta): array
	{
		$brandId = (int) ($meta['brand_id'] ?? 0);

		if ($brandId <= 0) {
			$brandId = (int) ($existing['brand_id'] ?? 0);
		}

		$categoryId = (int) ($meta['category_id'] ?? 0);

		if ($categoryId <= 0) {
			$categoryId = (int) ($existing['category_id'] ?? 0);
		}

		$attributes = $meta['attributes'] ?? null;

		if (!is_array($attributes) || $attributes === []) {
			$decoded = json_decode((string) ($existing['attributes_json'] ?? ''), true);
			$attributes = is_array($decoded) ? $decoded : [];
		}

		$salePrice = null;
		$listPrice = null;

		if (isset($meta['sale_price']) && $meta['sale_price'] !== '' && $meta['sale_price'] !== null) {
			$salePrice = (float) $meta['sale_price'];
		} elseif (isset($existing['sale_price']) && (float) $existing['sale_price'] > 0) {
			$salePrice = (float) $existing['sale_price'];
		}

		if (isset($meta['list_price']) && $meta['list_price'] !== '' && $meta['list_price'] !== null) {
			$listPrice = (float) $meta['list_price'];
		} elseif (isset($existing['list_price']) && (float) $existing['list_price'] > 0) {
			$listPrice = (float) $existing['list_price'];
		}

		return [
			'brand_id' => $brandId,
			'category_id' => $categoryId,
			'attributes' => $attributes,
			'sale_price' => $salePrice,
			'list_price' => $listPrice,
		];
	}

	/**
	 * @param array<string, mixed> $product
	 * @param array{brand_id: int, category_id: int, attributes: array<string, mixed>} $meta
	 * @return array{ok: bool, message: string, payload?: array<string, mixed>}
	 */
	public static function buildPayload(array $product, array $meta): array
	{
		$idProduct = (int) ($product['id_product'] ?? 0);
		$title = trim((string) ($product['product_name'] ?? ''));
		$barcode = trim((string) ($product['barcode'] ?? ''));

		if ($idProduct <= 0) {
			return ['ok' => false, 'message' => 'Geçersiz ürün'];
		}

		if ($title === '') {
			return ['ok' => false, 'message' => 'Ürün adı boş olamaz'];
		}

		if ($barcode === '') {
			$barcode = 'FS' . $idProduct;
		}

		$barcode = preg_replace('/\s+/', '', $barcode) ?: $barcode;

		if ((int) ($meta['brand_id'] ?? 0) <= 0) {
			return ['ok' => false, 'message' => 'Trendyol marka ID gerekli (ürün panelinden seçin)'];
		}

		if ((int) ($meta['category_id'] ?? 0) <= 0) {
			return ['ok' => false, 'message' => 'Trendyol kategori ID gerekli (ürün panelinden seçin)'];
		}

		$images = self::buildImages($product);

		if ($images === []) {
			return ['ok' => false, 'message' => 'En az bir herkese açık ürün görseli gerekli (localhost URL kabul edilmez)'];
		}

		$salePrice = isset($meta['sale_price']) && $meta['sale_price'] !== null && (float) $meta['sale_price'] > 0
			? (float) $meta['sale_price']
			: 0.0;
		$listPrice = isset($meta['list_price']) && $meta['list_price'] !== null && (float) $meta['list_price'] > 0
			? (float) $meta['list_price']
			: 0.0;

		if ($listPrice <= $salePrice) {
			$listPrice = $salePrice;
		}

		if ($salePrice <= 0) {
			return ['ok' => false, 'message' => 'Trendyol satış fiyatı girilmeli (mağaza fiyatı otomatik kullanılmaz)'];
		}

		$stockCode = trim((string) ($product['stock_code'] ?? ''));

		if ($stockCode === '') {
			$stockCode = 'SKU-' . $idProduct;
		}

		$vat = (int) round((float) ($product['vat'] ?? 20));
		$allowedVat = [0, 1, 10, 20];

		if (!in_array($vat, $allowedVat, true)) {
			$vat = 20;
		}

		$desi = max(1, (int) ($product['desi'] ?? 1));
		$cargoDay = (int) ($product['cargo_day'] ?? 0);
		$deliveryDuration = $cargoDay > 0 ? min(3, max(1, $cargoDay)) : 1;

		$attributes = self::api()->convertAttributes($meta['attributes'] ?? []);

		if ($attributes === []) {
			return ['ok' => false, 'message' => 'Trendyol kategori özellikleri (attributes) gerekli'];
		}

		$description = self::buildDescription($product);
		$payload = [
			'barcode' => mb_substr($barcode, 0, 40),
			'title' => mb_substr($title, 0, 100),
			'productMainId' => mb_substr('PM-' . $idProduct, 0, 40),
			'brandId' => (int) $meta['brand_id'],
			'categoryId' => (int) $meta['category_id'],
			'quantity' => max(0, \Product::getStock($product)),
			'stockCode' => mb_substr($stockCode, 0, 100),
			'dimensionalWeight' => $desi,
			'description' => $description,
			'currencyType' => 'TRY',
			'listPrice' => round($listPrice, 2),
			'salePrice' => round($salePrice, 2),
			'vatRate' => $vat,
			'images' => $images,
			'attributes' => $attributes,
			'deliveryOption' => [
				'deliveryDuration' => $deliveryDuration,
			],
		];

		return ['ok' => true, 'message' => 'OK', 'payload' => $payload];
	}

	/** @return array<int, array{url: string}> */
	private static function buildImages(array $product): array
	{
		$idProduct = (int) ($product['id_product'] ?? 0);
		$images = \Product::getImages($idProduct);
		$out = [];

		foreach ($images as $image) {
			if (count($out) >= 8) {
				break;
			}

			$url = self::absolutePublicUrl((string) ($image['url'] ?? ''));

			if ($url === '' || !self::isPublicImageUrl($url)) {
				continue;
			}

			$out[] = ['url' => $url];
		}

		return $out;
	}

	private static function buildDescription(array $product): string
	{
		$long = trim((string) ($product['description'] ?? ''));
		$short = trim((string) ($product['short_description'] ?? ''));

		if ($long !== '') {
			$text = $long;
		} elseif ($short !== '') {
			$text = $short;
		} else {
			$text = (string) ($product['product_name'] ?? '');
		}

		$text = preg_replace('/\s+/u', ' ', strip_tags($text)) ?: $text;

		return mb_substr(trim($text), 0, 30000);
	}

	private static function resolveCategoryId(int $idCategory): int
	{
		if ($idCategory <= 0) {
			return (int) (\Settings::get('TRENDYOL_DEFAULT_CATEGORY_ID') ?: 0);
		}

		self::ensureSchema();
		$row = \DB::getRowSafe('trendyol_category_map', 'id_category = ?', [$idCategory]);
		$mapped = (int) ($row['trendyol_category_id'] ?? 0);

		if ($mapped > 0) {
			return $mapped;
		}

		return (int) (\Settings::get('TRENDYOL_DEFAULT_CATEGORY_ID') ?: 0);
	}

	public static function saveCategoryMap(
		int $idCategory,
		int $trendyolCategoryId,
		string $attributesJson = '',
		string $trendyolCategoryName = ''
	): void {
		self::ensureSchema();

		if ($idCategory <= 0) {
			return;
		}

		$now = date('Y-m-d H:i:s');
		$existing = \DB::getRowSafe('trendyol_category_map', 'id_category = ?', [$idCategory]);
		$row = [
			'trendyol_category_id' => max(0, $trendyolCategoryId),
			'trendyol_category_name' => mb_substr($trendyolCategoryName, 0, 512),
			'attributes_json' => $attributesJson,
			'date_upd' => $now,
		];

		if ($existing) {
			if ($trendyolCategoryName === '' && !empty($existing['trendyol_category_name'])) {
				$row['trendyol_category_name'] = (string) $existing['trendyol_category_name'];
			}

			\DB::update('trendyol_category_map', $row, 'id_category = :where_id', ['where_id' => $idCategory]);
		} else {
			$row['id_category'] = $idCategory;
			\DB::insert('trendyol_category_map', $row);
		}
	}

	/** @return array<int, array<string, mixed>> */
	public static function getCategoryMaps(): array
	{
		self::ensureSchema();

		return \DB::execute('SELECT * FROM trendyol_category_map ORDER BY id_category ASC') ?: [];
	}

	/** @return array<int, array<string, mixed>> */
	public static function getRecentSyncs(int $limit = 40): array
	{
		self::ensureSchema();
		$limit = max(1, min(100, $limit));

		return \DB::execute(
			'SELECT tp.*, p.product_name, p.active
			 FROM trendyol_products tp
			 INNER JOIN products p ON p.id_product = tp.id_product
			 ORDER BY tp.last_sync_at DESC, tp.id_product DESC
			 LIMIT ' . (int) $limit
		) ?: [];
	}

	/** @param array<string, mixed> $fields */
	private static function saveMapping(int $idProduct, array $fields): void
	{
		self::ensureSchema();

		$now = date('Y-m-d H:i:s');
		$existing = self::findMapping($idProduct);

		$row = [
			'barcode' => (string) ($fields['barcode'] ?? ($existing['barcode'] ?? '')),
			'content_id' => (string) ($fields['content_id'] ?? ($existing['content_id'] ?? '')),
			'product_url' => (string) ($fields['product_url'] ?? ($existing['product_url'] ?? '')),
			'brand_id' => (int) ($fields['brand_id'] ?? ($existing['brand_id'] ?? 0)),
			'category_id' => (int) ($fields['category_id'] ?? ($existing['category_id'] ?? 0)),
			'attributes_json' => (string) ($fields['attributes_json'] ?? ($existing['attributes_json'] ?? '')),
			'sale_price' => (float) ($fields['sale_price'] ?? ($existing['sale_price'] ?? 0)),
			'list_price' => (float) ($fields['list_price'] ?? ($existing['list_price'] ?? 0)),
			'quantity' => (int) ($fields['quantity'] ?? ($existing['quantity'] ?? 0)),
			'approved' => (int) ($fields['approved'] ?? ($existing['approved'] ?? 0)),
			'batch_request_id' => (string) ($fields['batch_request_id'] ?? ($existing['batch_request_id'] ?? '')),
			'last_status' => (string) ($fields['last_status'] ?? ($existing['last_status'] ?? '')),
			'last_error' => (string) ($fields['last_error'] ?? ''),
			'last_sync_at' => (string) ($fields['last_sync_at'] ?? $now),
			'date_upd' => $now,
		];

		if ($existing) {
			\DB::update('trendyol_products', $row, 'id_product = :where_id', ['where_id' => $idProduct]);
		} else {
			$row['id_product'] = $idProduct;
			$row['date_add'] = $now;
			\DB::insert('trendyol_products', $row);
		}
	}

	/** @param mixed $result */
	public static function isApiError($result): bool
	{
		if ($result === null) {
			return true;
		}

		if (!is_array($result)) {
			return true;
		}

		if (array_key_exists('success', $result) && $result['success'] === false) {
			return true;
		}

		return false;
	}

	private static function absolutePublicUrl(string $url): string
	{
		$url = trim($url);

		if ($url === '') {
			return '';
		}

		if (preg_match('~^https?://~i', $url)) {
			return $url;
		}

		global $domain;

		return rtrim((string) $domain, '/') . '/' . ltrim($url, '/');
	}

	private static function isPublicImageUrl(string $url): bool
	{
		if (!preg_match('~^https?://~i', $url)) {
			return false;
		}

		$host = parse_url($url, PHP_URL_HOST);

		if (!is_string($host) || $host === '') {
			return false;
		}

		$localHosts = ['localhost', '127.0.0.1', '::1'];

		return !in_array(strtolower($host), $localHosts, true);
	}

	public static function countMarketplaceCatalog(string $query = '', string $filter = 'all'): int
	{
		self::ensureSchema();

		$sql = 'SELECT COUNT(*) FROM products p
			INNER JOIN brands b ON p.id_brand = b.id_brand
			INNER JOIN categories c ON p.id_category = c.id_category
			LEFT JOIN trendyol_products tp ON tp.id_product = p.id_product
			WHERE 1=1';
		$params = [];

		if ($query !== '') {
			$like = '%' . $query . '%';
			$sql .= ' AND (p.product_name LIKE ? OR p.barcode LIKE ? OR p.stock_code LIKE ? OR tp.barcode LIKE ?)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		if ($filter === 'linked') {
			$sql .= ' AND tp.id_product IS NOT NULL AND tp.barcode <> \'\'';
		} elseif ($filter === 'unlinked') {
			$sql .= ' AND (tp.id_product IS NULL OR tp.barcode = \'\')';
		}

		return (int) \DB::getValue($sql, $params);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function getMarketplaceCatalog(
		string $query = '',
		string $filter = 'all',
		int $limit = 30,
		int $offset = 0
	): array {
		self::ensureSchema();
		$limit = max(1, min(100, $limit));
		$offset = max(0, $offset);

		$sql = 'SELECT p.*, b.brand_name, c.category_name, i.id_image,
				tp.barcode AS ty_barcode, tp.sale_price AS ty_sale_price, tp.list_price AS ty_list_price,
				tp.quantity AS ty_quantity, tp.last_status AS ty_last_status, tp.last_error AS ty_last_error,
				tp.last_sync_at AS ty_last_sync_at, tp.approved AS ty_approved, tp.content_id AS ty_content_id,
				tp.product_url AS ty_product_url
			FROM products p
			INNER JOIN brands b ON p.id_brand = b.id_brand
			INNER JOIN categories c ON p.id_category = c.id_category
			LEFT JOIN images i ON p.id_product = i.id_product AND i.cover = 1
			LEFT JOIN trendyol_products tp ON tp.id_product = p.id_product
			WHERE 1=1';
		$params = [];

		if ($query !== '') {
			$like = '%' . $query . '%';
			$sql .= ' AND (p.product_name LIKE ? OR p.barcode LIKE ? OR p.stock_code LIKE ? OR tp.barcode LIKE ?)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		if ($filter === 'linked') {
			$sql .= ' AND tp.id_product IS NOT NULL AND tp.barcode <> \'\'';
		} elseif ($filter === 'unlinked') {
			$sql .= ' AND (tp.id_product IS NULL OR tp.barcode = \'\')';
		}

		// Bazı eski FShop kurulumlarında products.date_upd alanı bulunmaz.
		// Pazaryeri kaydı yoksa ürün kimliğine göre sıralamak listeyi tüm sürümlerde çalıştırır.
		$sql .= ' ORDER BY p.stock DESC
			LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

		$rows = \DB::execute($sql, $params) ?: [];

		foreach ($rows as &$row) {
			$row['price_formatted'] = \Tools::displayPrice((float) ($row['price'] ?? 0));
			$row['image_url'] = \Product::getImageUrl(isset($row['id_image']) ? (int) $row['id_image'] : null);
			$row['stock'] = \Product::getStock($row);
			$row['ty_linked'] = trim((string) ($row['ty_barcode'] ?? '')) !== '';
		}
		unset($row);

		return $rows;
	}
}
