<?php

/**
 * Pazaryeri olay günlüğü (sipariş, stok, fiyat uyarıları).
 */
class MarketplaceLog
{
	public const TABLE = 'marketplace_logs';

	public const TYPE_NEW_ORDER = 'new_order';
	public const TYPE_STOCK_CHANGE = 'stock_change';
	public const TYPE_BELOW_MIN_PRICE = 'below_min_price';
	public const TYPE_PRICE_UPDATE = 'price_update';

	private static bool $ready = false;

	public static function ensureSchema(): void
	{
		if (self::$ready) {
			return;
		}

		self::$ready = true;

		$exists = DB::execute("SHOW TABLES LIKE '" . self::TABLE . "'");

		if (empty($exists)) {
			DB::execute(
				"CREATE TABLE `" . self::TABLE . "` (
					`id` bigint(20) NOT NULL AUTO_INCREMENT,
					`platform` varchar(32) NOT NULL DEFAULT '',
					`event_type` varchar(64) NOT NULL,
					`event_label` varchar(128) NOT NULL DEFAULT '',
					`reference` varchar(128) NOT NULL DEFAULT '',
					`id_product` int(11) NOT NULL DEFAULT 0,
					`message` text NOT NULL,
					`meta_json` mediumtext NULL,
					`date_add` datetime NOT NULL,
					PRIMARY KEY (`id`),
					KEY `date_add` (`date_add`),
					KEY `event_type` (`event_type`),
					KEY `platform` (`platform`),
					KEY `reference` (`reference`),
					KEY `id_product` (`id_product`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		}
	}

	public static function storeName(string $platform = ''): string
	{
		$name = trim((string) Settings::get('SITE_NAME'));

		if ($name !== '') {
			return $name;
		}

		$labels = [
			'trendyol' => 'Trendyol',
			'hepsiburada' => 'Hepsiburada',
			'n11' => 'N11',
		];

		return $labels[$platform] ?? 'Mağaza';
	}

	public static function typeLabel(string $type): string
	{
		$map = [
			self::TYPE_NEW_ORDER => 'Yeni Sipariş',
			self::TYPE_STOCK_CHANGE => 'Stok Değişimi',
			self::TYPE_BELOW_MIN_PRICE => 'Min. Satış Fiyatı Altında Satış',
			self::TYPE_PRICE_UPDATE => 'Fiyat Güncellemesi',
		];

		return $map[$type] ?? $type;
	}

	/**
	 * @param array<string, mixed> $meta
	 */
	public static function write(
		string $eventType,
		string $message,
		string $platform = '',
		string $reference = '',
		int $idProduct = 0,
		array $meta = []
	): void {
		self::ensureSchema();

		DB::insert(self::TABLE, [
			'platform' => mb_substr($platform, 0, 32),
			'event_type' => mb_substr($eventType, 0, 64),
			'event_label' => mb_substr(self::typeLabel($eventType), 0, 128),
			'reference' => mb_substr($reference, 0, 128),
			'id_product' => max(0, $idProduct),
			'message' => $message,
			'meta_json' => $meta !== [] ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
			'date_add' => date('Y-m-d H:i:s'),
		]);
	}

	public static function newOrder(string $platform, string $orderNumber, array $lines = []): void
	{
		$orderNumber = trim($orderNumber);

		if ($orderNumber === '') {
			return;
		}

		$store = self::storeName($platform);
		self::write(
			self::TYPE_NEW_ORDER,
			$store . ' mağazanızdan ' . $orderNumber . ' numaralı yeni sipariş sisteme eklendi.',
			$platform,
			$orderNumber,
			0,
			['order_number' => $orderNumber]
		);
		
		if ($lines !== []) {
			if (!class_exists('ProductLog', false) && is_file(dirname(__DIR__) . '/core/ProductLog.php')) {
				require_once dirname(__DIR__) . '/core/ProductLog.php';
			}
			if (class_exists('ProductLog', false)) {
				foreach ($lines as $line) {
					if (!is_array($line)) continue;
					$idP = (int) ($line['id_product'] ?? 0);
					if ($idP > 0) {
						\ProductLog::add($idP, 'marketplace', $store . ' ' . $orderNumber . ' nolu sipariş geldi');
					}
				}
			}
		}
	}

	/**
	 * @param int|float|string $oldStock
	 * @param int|float|string $newStock
	 */
	public static function stockChange(
		string $platform,
		string $referenceCode,
		$oldStock,
		$newStock,
		string $reason = 'STOCK_CHANGE',
		int $idProduct = 0,
		string $orderNumber = ''
	): void {
		$referenceCode = trim($referenceCode);

		if ($referenceCode === '' && $idProduct > 0) {
			$referenceCode = (string) $idProduct;
		}

		if ($referenceCode === '') {
			return;
		}

		$reason = trim($reason) !== '' ? trim($reason) : 'STOCK_CHANGE';

		if ($orderNumber !== '' && stripos($reason, 'ORDER') === false) {
			$reason = 'ORDER [' . $orderNumber . ']';
		}

		$message = $reason . '  - ' . self::formatQty($oldStock) . '  ' . self::formatQty($newStock);

		self::write(
			self::TYPE_STOCK_CHANGE,
			$message,
			$platform,
			$referenceCode,
			$idProduct,
			[
				'old_stock' => $oldStock,
				'new_stock' => $newStock,
				'reason' => $reason,
				'order_number' => $orderNumber,
			]
		);
	}

	public static function belowMinPrice(
		string $platform,
		string $orderNumber,
		string $stockCode,
		float $salePrice,
		float $minPrice,
		int $idProduct = 0
	): void {
		$code = trim($stockCode);

		if ($code === '' && $idProduct > 0) {
			$code = (string) $idProduct;
		}

		if ($code === '') {
			return;
		}

		self::write(
			self::TYPE_BELOW_MIN_PRICE,
			$code . ' stok kodlu üründen Min. Satış Fiyatı altında sipariş alındı.',
			$platform,
			$orderNumber !== '' ? $orderNumber : $code,
			$idProduct,
			[
				'stock_code' => $code,
				'sale_price' => $salePrice,
				'min_price' => $minPrice,
				'order_number' => $orderNumber,
			]
		);
	}

	public static function priceUpdate(
		string $platform,
		string $referenceCode,
		float $oldPrice,
		float $newPrice,
		int $idProduct = 0
	): void {
		if ($oldPrice <= 0 && $newPrice <= 0) {
			return;
		}

		if (abs($oldPrice - $newPrice) < 0.0001) {
			return;
		}

		$referenceCode = trim($referenceCode);

		if ($referenceCode === '' && $idProduct > 0) {
			$referenceCode = (string) $idProduct;
		}

		self::write(
			self::TYPE_PRICE_UPDATE,
			$referenceCode . ' ürününün satış fiyatı ' . self::formatQty($oldPrice) . ' → ' . self::formatQty($newPrice) . ' olarak güncellendi.',
			$platform,
			$referenceCode,
			$idProduct,
			['old_price' => $oldPrice, 'new_price' => $newPrice]
		);
	}

	/**
	 * Sipariş satırlarında maliyet altı satış kontrolü.
	 *
	 * @param mixed $lines
	 */
	public static function checkOrderLinesMinPrice(string $platform, string $orderNumber, $lines): void
	{
		if (!is_array($lines)) {
			return;
		}

		foreach ($lines as $line) {
			if (!is_array($line)) {
				continue;
			}

			$idProduct = (int) ($line['id_product'] ?? 0);

			if ($idProduct <= 0) {
				continue;
			}

			$product = Product::getByIdAdmin($idProduct);

			if (!$product) {
				continue;
			}

			$minPrice = (float) ($product['cost'] ?? 0);

			if ($minPrice <= 0) {
				continue;
			}

			$salePrice = self::extractLineSalePrice($line);

			if ($salePrice === null || $salePrice <= 0 || $salePrice >= $minPrice) {
				continue;
			}

			$stockCode = trim((string) ($product['stock_code'] ?? ''));

			if ($stockCode === '') {
				$stockCode = trim((string) ($product['barcode'] ?? ''));
			}

			if ($stockCode === '') {
				$stockCode = (string) $idProduct;
			}

			self::belowMinPrice($platform, $orderNumber, $stockCode, $salePrice, $minPrice, $idProduct);
		}
	}

	/** @param array<string, mixed> $line */
	private static function extractLineSalePrice(array $line): ?float
	{
		foreach (['price', 'amount', 'salePrice', 'unitPrice', 'discountedPrice', 'lineUnitPrice'] as $key) {
			if (!isset($line[$key])) {
				continue;
			}

			$raw = $line[$key];

			if (is_array($raw) && isset($raw['amount'])) {
				$val = (float) $raw['amount'];

				return $val > 0 ? $val : null;
			}

			if (is_numeric($raw)) {
				$val = (float) $raw;

				return $val > 0 ? $val : null;
			}
		}

		return null;
	}

	/** @param int|float|string $qty */
	public static function formatQty($qty): string
	{
		if (is_string($qty) && $qty !== '' && !is_numeric($qty)) {
			return $qty;
		}

		$n = (float) $qty;

		if (abs($n - round($n)) < 0.0001) {
			return (string) (int) round($n);
		}

		$formatted = rtrim(rtrim(number_format($n, 2, ',', ''), '0'), ',');

		return $formatted !== '' ? $formatted : '0';
	}

	/**
	 * @return array{rows: array<int, array<string, mixed>>, total: int}
	 */
	public static function search(array $filters, int $limit = 50, int $offset = 0): array
	{
		self::ensureSchema();
		$limit = max(1, min(200, $limit));
		$offset = max(0, $offset);

		$where = ['1=1'];
		$params = [];

		$platform = trim((string) ($filters['platform'] ?? ''));
		$eventType = trim((string) ($filters['event_type'] ?? ''));
		$query = trim((string) ($filters['q'] ?? ''));
		$start = trim((string) ($filters['start_date'] ?? ''));
		$end = trim((string) ($filters['end_date'] ?? ''));

		if ($platform !== '' && $platform !== 'all') {
			$where[] = 'platform = ?';
			$params[] = $platform;
		}

		if ($eventType !== '' && $eventType !== 'all') {
			$where[] = 'event_type = ?';
			$params[] = $eventType;
		}

		if ($query !== '') {
			$like = '%' . $query . '%';
			$where[] = '(reference LIKE ? OR message LIKE ? OR event_label LIKE ?)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		if ($start !== '') {
			$where[] = 'date_add >= ?';
			$params[] = $start . ' 00:00:00';
		}

		if ($end !== '') {
			$where[] = 'date_add <= ?';
			$params[] = $end . ' 23:59:59';
		}

		$whereSql = implode(' AND ', $where);
		$total = (int) (DB::getValue('SELECT COUNT(*) FROM `' . self::TABLE . '` WHERE ' . $whereSql, $params) ?: 0);

		$rows = DB::execute(
			'SELECT * FROM `' . self::TABLE . '`
			 WHERE ' . $whereSql . '
			 ORDER BY date_add DESC, id DESC
			 LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset,
			$params
		) ?: [];

		foreach ($rows as &$row) {
			$row['date_formatted'] = !empty($row['date_add'])
				? date('d.m.Y H:i', strtotime((string) $row['date_add']))
				: '';
			$row['platform_label'] = self::platformLabel((string) ($row['platform'] ?? ''));
		}
		unset($row);

		return ['rows' => $rows, 'total' => $total];
	}

	public static function platformLabel(string $platform): string
	{
		$map = [
			'trendyol' => 'Trendyol',
			'hepsiburada' => 'Hepsiburada',
			'n11' => 'N11',
		];

		return $map[$platform] ?? ($platform !== '' ? $platform : '—');
	}
}
