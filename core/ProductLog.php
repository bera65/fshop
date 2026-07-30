<?php

class ProductLog
{
	private static bool $schemaReady = false;

	public static function ensureSchema(): void
	{
		if (self::$schemaReady) {
			return;
		}

		self::$schemaReady = true;

		DB::execute(
			'CREATE TABLE IF NOT EXISTS `product_logs` (
				`id_log` int(11) NOT NULL AUTO_INCREMENT,
				`id_product` int(11) NOT NULL,
				`event_type` varchar(32) NOT NULL DEFAULT \'\',
				`message` varchar(512) NOT NULL DEFAULT \'\',
				`meta` text DEFAULT NULL,
				`id_admin` int(11) NOT NULL DEFAULT 0,
				`id_order` int(11) NOT NULL DEFAULT 0,
				`date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id_log`),
				KEY `id_product` (`id_product`),
				KEY `date_add` (`date_add`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
		);
	}

	/**
	 * @param array<string, mixed> $meta
	 */
	public static function add(
		int $idProduct,
		string $eventType,
		string $message,
		array $meta = [],
		int $idAdmin = 0,
		int $idOrder = 0
	): void {
		if ($idProduct <= 0 || $message === '') {
			return;
		}

		self::ensureSchema();

		DB::insert('product_logs', [
			'id_product' => $idProduct,
			'event_type' => mb_substr(trim($eventType), 0, 32),
			'message' => mb_substr(trim($message), 0, 512),
			'meta' => $meta !== [] ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
			'id_admin' => max(0, $idAdmin),
			'id_order' => max(0, $idOrder),
			'date_add' => date('Y-m-d H:i:s'),
		]);
	}

	/** @return array<int, array<string, mixed>> */
	public static function getForProduct(int $idProduct, int $limit = 100): array
	{
		self::ensureSchema();

		if ($idProduct <= 0) {
			return [];
		}

		$limit = max(1, min(500, $limit));
		$rows = DB::execute(
			'SELECT * FROM product_logs WHERE id_product = ? ORDER BY id_log DESC LIMIT ' . (int) $limit,
			[$idProduct]
		) ?: [];

		$out = [];

		foreach ($rows as $row) {
			$meta = [];
			$rawMeta = (string) ($row['meta'] ?? '');

			if ($rawMeta !== '') {
				$decoded = json_decode($rawMeta, true);
				if (is_array($decoded)) {
					$meta = $decoded;
				}
			}

			$out[] = [
				'id_log' => (int) ($row['id_log'] ?? 0),
				'id_product' => (int) ($row['id_product'] ?? 0),
				'event_type' => (string) ($row['event_type'] ?? ''),
				'message' => (string) ($row['message'] ?? ''),
				'meta' => $meta,
				'id_admin' => (int) ($row['id_admin'] ?? 0),
				'id_order' => (int) ($row['id_order'] ?? 0),
				'date_add' => (string) ($row['date_add'] ?? ''),
				'date_formatted' => !empty($row['date_add']) ? Tools::formatDate3($row['date_add']) : '',
			];
		}

		return $out;
	}

	public static function currentAdminId(): int
	{
		return (int) ($_SESSION['id_admin'] ?? 0);
	}

	/**
	 * @param array<string, mixed>|null $before
	 * @param array<string, mixed> $after
	 */
	public static function logSaveDiff(?array $before, array $after, int $idProduct, bool $isCreate): void
	{
		$idAdmin = self::currentAdminId();

		if ($isCreate || $before === null) {
			self::add($idProduct, 'created', 'Ürün oluşturuldu', [
				'price' => (float) ($after['price'] ?? 0),
				'stock' => (float) ($after['stock'] ?? 0),
			], $idAdmin);

			return;
		}

		$priceBefore = round((float) ($before['price'] ?? 0), 2);
		$priceAfter = round((float) ($after['price'] ?? 0), 2);

		if ($priceBefore !== $priceAfter) {
			self::add($idProduct, 'price_change', sprintf(
				'Fiyat güncellendi: %s → %s',
				Tools::displayPrice($priceBefore),
				Tools::displayPrice($priceAfter)
			), [
				'old' => $priceBefore,
				'new' => $priceAfter,
			], $idAdmin);
		}

		$stockBefore = round((float) ($before['stock'] ?? 0), 3);
		$stockAfter = round((float) ($after['stock'] ?? 0), 3);

		if ($stockBefore !== $stockAfter) {
			$delta = round($stockAfter - $stockBefore, 3);
			$msg = $delta > 0
				? sprintf('Stok eklendi: +%s (toplam %s)', self::formatQty($delta), self::formatQty($stockAfter))
				: sprintf('Stok azaltıldı: %s (toplam %s)', self::formatQty($delta), self::formatQty($stockAfter));

			self::add($idProduct, 'stock_change', $msg, [
				'old' => $stockBefore,
				'new' => $stockAfter,
				'delta' => $delta,
			], $idAdmin);
		}

		$tracked = [
			'product_name' => 'Ürün adı',
			'active' => 'Durum',
			'id_category' => 'Kategori',
			'id_brand' => 'Marka',
			'product_type' => 'Ürün türü',
			'stock_code' => 'Stok kodu',
			'barcode' => 'Barkod',
			'old_price' => 'Eski fiyat',
			'cost' => 'Maliyet',
			'label' => 'Etiket',
			'cargo_day' => 'Termin süresi',
			'sale_unit' => 'Satış birimi',
			'desi' => 'Desi',
			'virtual_kind' => 'Sanal teslimat',
		];

		$fieldChanges = [];

		foreach ($tracked as $key => $label) {
			$old = $before[$key] ?? null;
			$new = $after[$key] ?? null;

			if ($key === 'active') {
				$old = ((int) $old === 1) ? 'Aktif' : 'Pasif';
				$new = ((int) $new === 1) ? 'Aktif' : 'Pasif';
			} elseif (in_array($key, ['old_price', 'cost'], true)) {
				$old = round((float) $old, 2);
				$new = round((float) $new, 2);
			} elseif (in_array($key, ['id_category', 'id_brand', 'cargo_day', 'desi'], true)) {
				$old = (int) $old;
				$new = (int) $new;
			} else {
				$old = (string) $old;
				$new = (string) $new;
			}

			if ($old != $new) {
				$fieldChanges[] = $label;
			}
		}

		if ($fieldChanges !== []) {
			self::add($idProduct, 'updated', 'Ürün güncellendi: ' . implode(', ', $fieldChanges), [
				'fields' => $fieldChanges,
			], $idAdmin);
		} elseif ($priceBefore === $priceAfter && $stockBefore === $stockAfter) {
			self::add($idProduct, 'updated', 'Ürün güncellendi', [], $idAdmin);
		}
	}

	public static function logSold(int $idProduct, float $qty, int $idOrder = 0, string $reference = ''): void
	{
		$qty = round($qty, 3);

		if ($idProduct <= 0 || $qty <= 0) {
			return;
		}

		$ref = $reference !== '' ? ' (#' . $reference . ')' : '';
		self::add(
			$idProduct,
			'sold',
			sprintf('%s adet satıldı%s', self::formatQty($qty), $ref),
			['qty' => $qty, 'reference' => $reference],
			0,
			$idOrder
		);
	}

	public static function logStockRestored(int $idProduct, float $qty, int $idOrder = 0): void
	{
		$qty = round($qty, 3);

		if ($idProduct <= 0 || $qty <= 0) {
			return;
		}

		self::add(
			$idProduct,
			'stock_restored',
			sprintf('Stok iade edildi: +%s', self::formatQty($qty)),
			['qty' => $qty],
			self::currentAdminId(),
			$idOrder
		);
	}

	private static function formatQty(float $qty): string
	{
		$formatted = rtrim(rtrim(number_format($qty, 3, ',', ''), '0'), ',');

		return $formatted !== '' ? $formatted : '0';
	}
}
