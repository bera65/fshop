<?php

class ProductVariation
{
	private static bool $schemaReady = false;

	public static function ensureSchema(): void
	{
		if (self::$schemaReady) {
			return;
		}

		self::$schemaReady = true;

		$table = DB::execute("SHOW TABLES LIKE 'product_variations'");

		if (empty($table)) {
			DB::execute(
				"CREATE TABLE `product_variations` (
					`id_variation` int(11) NOT NULL AUTO_INCREMENT,
					`id_product` int(11) NOT NULL,
					`sku` varchar(64) NOT NULL DEFAULT '',
					`barcode` varchar(64) NOT NULL DEFAULT '',
					`options_json` varchar(1024) NOT NULL DEFAULT '{}',
					`price` decimal(20,2) DEFAULT NULL,
					`stock` decimal(12,3) NOT NULL DEFAULT 0.000,
					`active` tinyint(1) NOT NULL DEFAULT 1,
					PRIMARY KEY (`id_variation`),
					KEY `id_product` (`id_product`),
					UNIQUE KEY `product_sku` (`id_product`, `sku`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		} else {
			$stockCol = DB::execute("SHOW COLUMNS FROM `product_variations` LIKE 'stock'");
			$stockType = strtolower((string) ($stockCol[0]['Type'] ?? ''));
			if ($stockType !== '' && strpos($stockType, 'decimal') === false) {
				DB::execute(
					'ALTER TABLE `product_variations` MODIFY COLUMN `stock` decimal(12,3) NOT NULL DEFAULT 0.000'
				);
			}
		}

		$col = DB::execute("SHOW COLUMNS FROM `order_detail` LIKE 'id_variation'");

		if (empty($col)) {
			DB::execute(
				"ALTER TABLE `order_detail`
				 ADD COLUMN `id_variation` int(11) NOT NULL DEFAULT 0 AFTER `id_product`,
				 ADD COLUMN `variation_label` varchar(255) NOT NULL DEFAULT '' AFTER `product_name`"
			);
		}
	}

	public static function hasVariations(int $idProduct): bool
	{
		self::ensureSchema();

		if ($idProduct <= 0) {
			return false;
		}

		return (int) DB::getValue(
			'SELECT COUNT(*) FROM product_variations WHERE id_product = ? AND active = 1 LIMIT 1',
			[$idProduct]
		) > 0;
	}

	public static function getTotalStock(int $idProduct): int
	{
		self::ensureSchema();

		return (int) DB::getValue(
			'SELECT COALESCE(SUM(stock), 0) FROM product_variations WHERE id_product = ? AND active = 1',
			[$idProduct]
		);
	}

	/** @return array<int, array<string, mixed>> */
	public static function getByProduct(int $idProduct, bool $activeOnly = false): array
	{
		self::ensureSchema();

		if ($idProduct <= 0) {
			return [];
		}

		$sql = 'SELECT * FROM product_variations WHERE id_product = ?';

		if ($activeOnly) {
			$sql .= ' AND active = 1';
		}

		$sql .= ' ORDER BY id_variation ASC';

		$rows = DB::execute($sql, [$idProduct]) ?: [];
		$list = [];

		foreach ($rows as $row) {
			$list[] = self::normalizeRow($row);
		}

		return $list;
	}

	public static function getById(int $idVariation): ?array
	{
		self::ensureSchema();

		if ($idVariation <= 0) {
			return null;
		}

		$row = DB::getRowSafe('product_variations', 'id_variation = ?', [$idVariation]);

		return $row ? self::normalizeRow($row) : null;
	}

	public static function getEffectivePrice(array $variation, float $basePrice): float
	{
		if (isset($variation['price']) && $variation['price'] !== null && $variation['price'] !== '') {
			return max(0.0, (float) $variation['price']);
		}

		return max(0.0, $basePrice);
	}

	public static function formatLabel(array $variation): string
	{
		$options = is_array($variation['options'] ?? null) ? $variation['options'] : [];
		$parts = [];

		foreach ($options as $name => $value) {
			$name = trim((string) $name);
			$value = trim((string) $value);

			if ($name === '' || $value === '') {
				continue;
			}

			$parts[] = $name . ': ' . $value;
		}

		return implode(', ', $parts);
	}

	/** Admin form satırı → kayıt formatı */
	public static function formatFormRow(array $variation): array
	{
		$opts = is_array($variation['options'] ?? null) ? $variation['options'] : [];
		$keys = array_keys($opts);
		$vals = array_values($opts);

		return [
			'id_variation' => (int) ($variation['id_variation'] ?? 0),
			'option1_name' => (string) ($keys[0] ?? 'Renk'),
			'option1_value' => (string) ($vals[0] ?? ''),
			'option2_name' => (string) ($keys[1] ?? 'Beden'),
			'option2_value' => (string) ($vals[1] ?? ''),
			'sku' => (string) ($variation['sku'] ?? ''),
			'barcode' => (string) ($variation['barcode'] ?? ''),
			'price' => $variation['price'] !== null && $variation['price'] !== '' ? (string) $variation['price'] : '',
			'stock' => (int) ($variation['stock'] ?? 0),
			'active' => (int) ($variation['active'] ?? 1) === 1,
		];
	}

	/** POST variations[...] dizisini API/kayıt formatına çevirir */
	public static function parseFormRows(array $rows): array
	{
		$parsed = [];

		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}

			$option1Name = trim((string) ($row['option1_name'] ?? ''));
			$option1Value = trim((string) ($row['option1_value'] ?? ''));
			$option2Name = trim((string) ($row['option2_name'] ?? ''));
			$option2Value = trim((string) ($row['option2_value'] ?? ''));

			if (!empty($row['options']) && is_array($row['options'])) {
				$optionIndex = 0;

				foreach ($row['options'] as $name => $value) {
					$optionIndex++;

					if ($optionIndex === 1) {
						$option1Name = trim((string) $name);
						$option1Value = trim((string) $value);
					} elseif ($optionIndex === 2) {
						$option2Name = trim((string) $name);
						$option2Value = trim((string) $value);
						break;
					}
				}
			}

			$sku = trim((string) ($row['sku'] ?? ''));
			$barcode = trim((string) ($row['barcode'] ?? ''));

			if ($option1Name === '') {
				$option1Name = 'Renk';
			}

			if ($option2Name === '') {
				$option2Name = 'Beden';
			}

			if ($option1Value === '' && $option2Value === '' && $sku === '' && $barcode === '') {
				continue;
			}

			$options = [];

			if ($option1Value !== '') {
				$options[$option1Name] = $option1Value;
			}

			if ($option2Value !== '') {
				$options[$option2Name] = $option2Value;
			}

			if ($options === []) {
				continue;
			}

			$price = null;

			if (isset($row['price']) && trim((string) $row['price']) !== '') {
				$price = max(0.0, (float) str_replace(',', '.', (string) $row['price']));
			}

			$parsed[] = [
				'id' => (int) ($row['id_variation'] ?? 0),
				'sku' => $sku,
				'barcode' => $barcode,
				'options' => $options,
				'price' => $price,
				'stock' => max(0, (int) ($row['stock'] ?? 0)),
				'active' => !empty($row['active']),
			];
		}

		return $parsed;
	}

	/** Mağaza ürün sayfası için varyasyon verisi (özellik grupları: Renk, Beden …) */
	public static function getForStorefront(int $idProduct, float $basePrice): array
	{
		self::ensureSchema();

		$variations = self::getByProduct($idProduct, true);

		if ($variations === []) {
			return [
				'has_variations' => false,
				'groups' => [],
				'items' => [],
			];
		}

		$groupOrder = [];
		$canonicalGroupNames = [];
		$groupValues = [];

		foreach ($variations as $variation) {
			$options = is_array($variation['options'] ?? null) ? $variation['options'] : [];

			foreach ($options as $name => $value) {
				$name = trim((string) $name);
				$value = trim((string) $value);

				if ($name === '' || $value === '') {
					continue;
				}

				$groupKey = mb_strtolower($name, 'UTF-8');

				if (!isset($canonicalGroupNames[$groupKey])) {
					$canonicalGroupNames[$groupKey] = $name;
					$groupOrder[] = $groupKey;
				}

				$canonName = $canonicalGroupNames[$groupKey];
				$valKey = self::normalizeOptionValueKey($value);

				if (!isset($groupValues[$canonName][$valKey])) {
					$groupValues[$canonName][$valKey] = $value;
				}
			}
		}

		$items = [];

		foreach ($variations as $variation) {
			$options = is_array($variation['options'] ?? null) ? $variation['options'] : [];
			$canonicalOptions = [];

			foreach ($options as $name => $value) {
				$name = trim((string) $name);
				$value = trim((string) $value);

				if ($name === '' || $value === '') {
					continue;
				}

				$groupKey = mb_strtolower($name, 'UTF-8');
				$canonName = $canonicalGroupNames[$groupKey] ?? $name;
				$valKey = self::normalizeOptionValueKey($value);
				$canonicalOptions[$canonName] = $groupValues[$canonName][$valKey] ?? $value;
			}

			if ($canonicalOptions === []) {
				continue;
			}

			$price = self::getEffectivePrice($variation, $basePrice);
			$stock = max(0, (int) ($variation['stock'] ?? 0));

			$items[] = [
				'id_variation' => (int) ($variation['id_variation'] ?? 0),
				'options' => $canonicalOptions,
				'sku' => (string) ($variation['sku'] ?? ''),
				'stock' => $stock,
				'price' => $price,
				'price_formatted' => Tools::displayPrice($price),
				'in_stock' => $stock > 0,
			];
		}

		$groupList = [];

		foreach ($groupOrder as $groupKey) {
			$name = $canonicalGroupNames[$groupKey];
			$values = array_values($groupValues[$name] ?? []);
			$values = self::sortGroupValues($name, $values);

			if ($values === []) {
				continue;
			}

			$groupList[] = [
				'name' => $name,
				'values' => $values,
			];
		}

		return [
			'has_variations' => $items !== [],
			'groups' => $groupList,
			'items' => $items,
		];
	}

	private static function normalizeOptionValueKey(string $value): string
	{
		return mb_strtolower(trim($value), 'UTF-8');
	}

	/** @param array<int, string> $values */
	private static function sortGroupValues(string $groupName, array $values): array
	{
		$nameLower = mb_strtolower($groupName, 'UTF-8');
		$isSize = strpos($nameLower, 'beden') !== false
			|| strpos($nameLower, 'size') !== false
			|| strpos($nameLower, 'ebat') !== false
			|| strpos($nameLower, 'numara') !== false;

		if (!$isSize) {
			sort($values, SORT_NATURAL | SORT_FLAG_CASE);

			return $values;
		}

		$sizeOrder = [
			'xxs' => 1,
			'xs' => 2,
			's' => 3,
			'm' => 4,
			'l' => 5,
			'xl' => 6,
			'xxl' => 7,
			'2xl' => 7,
			'3xl' => 8,
			'xxxl' => 8,
		];

		usort($values, static function (string $a, string $b) use ($sizeOrder): int {
			$ka = $sizeOrder[mb_strtolower(trim($a), 'UTF-8')] ?? 99;
			$kb = $sizeOrder[mb_strtolower(trim($b), 'UTF-8')] ?? 99;

			if ($ka !== $kb) {
				return $ka <=> $kb;
			}

			return strnatcasecmp($a, $b);
		});

		return $values;
	}

	/** @param array<int, array<string, mixed>> $variations */
	public static function saveForProduct(int $idProduct, array $variations, float $basePrice): ?array
	{
		self::ensureSchema();

		if ($idProduct <= 0) {
			return ['success' => false, 'message' => 'Geçersiz ürün'];
		}

		$keepIds = [];
		$seenSkus = [];

		foreach ($variations as $index => $item) {
			if (!is_array($item)) {
				return ['success' => false, 'message' => 'Varyasyon #' . ($index + 1) . ' geçersiz'];
			}

			$idVariation = (int) ($item['id'] ?? $item['id_variation'] ?? 0);
			$sku = trim((string) ($item['sku'] ?? $item['stock_code'] ?? ''));
			$barcode = trim((string) ($item['barcode'] ?? ''));
			$options = self::normalizeOptions($item['options'] ?? $item['attributes'] ?? []);
			$stock = max(0, (float) str_replace(',', '.', (string) ($item['stock'] ?? 0)));
			$stock = round($stock, 3);
			$active = !isset($item['active']) || filter_var($item['active'], FILTER_VALIDATE_BOOLEAN);
			$price = null;

			if (array_key_exists('price', $item) && $item['price'] !== '' && $item['price'] !== null) {
				$price = max(0.0, (float) str_replace(',', '.', (string) $item['price']));
			}

			if ($options === []) {
				return ['success' => false, 'message' => 'Varyasyon #' . ($index + 1) . ' için en az bir seçenek girin'];
			}

			if ($sku === '') {
				$sku = 'VAR-' . $idProduct . '-' . ($index + 1);
			}

			$skuKey = strtolower($sku);

			if (isset($seenSkus[$skuKey])) {
				return ['success' => false, 'message' => 'Tekrarlayan varyasyon SKU: ' . $sku];
			}

			$seenSkus[$skuKey] = true;

			$row = [
				'id_product' => $idProduct,
				'sku' => mb_substr($sku, 0, 64),
				'barcode' => mb_substr($barcode, 0, 64),
				'options_json' => json_encode($options, JSON_UNESCAPED_UNICODE),
				'price' => $price,
				'stock' => $stock,
				'active' => $active ? 1 : 0,
			];

			if ($idVariation > 0) {
				$existing = self::getById($idVariation);

				if (!$existing || (int) $existing['id_product'] !== $idProduct) {
					return ['success' => false, 'message' => 'Varyasyon bulunamadı: ' . $idVariation];
				}

				DB::update('product_variations', $row, 'id_variation = :where_id', ['where_id' => $idVariation]);
				$keepIds[] = $idVariation;
			} else {
				$newId = DB::insert('product_variations', $row);

				if (!$newId) {
					return ['success' => false, 'message' => 'Varyasyon kaydedilemedi'];
				}

				$keepIds[] = (int) $newId;
			}
		}

		$existingRows = self::getByProduct($idProduct);

		foreach ($existingRows as $existing) {
			$existingId = (int) ($existing['id_variation'] ?? 0);

			if ($existingId > 0 && !in_array($existingId, $keepIds, true)) {
				DB::execute('DELETE FROM product_variations WHERE id_variation = ?', [$existingId]);
			}
		}

		if ($keepIds !== []) {
			$totalStock = self::getTotalStock($idProduct);
			DB::update('products', ['stock' => $totalStock], 'id_product = :where_id', ['where_id' => $idProduct]);
		}

		return null;
	}

	public static function deleteByProduct(int $idProduct): void
	{
		self::ensureSchema();
		DB::execute('DELETE FROM product_variations WHERE id_product = ?', [$idProduct]);
	}

	public static function decreaseStock(int $idVariation, float $qty, int $idProduct = 0): bool
	{
		self::ensureSchema();

		$qty = round($qty, 3);

		if ($idVariation <= 0 || $qty <= 0) {
			return false;
		}

		global $db;

		$sql = 'UPDATE product_variations SET stock = stock - ? WHERE id_variation = ? AND stock >= ? AND active = 1';
		$params = [$qty, $idVariation, $qty];

		if ($idProduct > 0) {
			$sql .= ' AND id_product = ?';
			$params[] = $idProduct;
		}

		$stmt = $db->prepare($sql);
		$stmt->execute($params);

		if ($stmt->rowCount() <= 0) {
			return false;
		}

		if ($idProduct > 0) {
			$totalStock = self::getTotalStock($idProduct);
			DB::update('products', ['stock' => $totalStock], 'id_product = :where_id', ['where_id' => $idProduct]);
		}

		return true;
	}

	public static function increaseStock(int $idVariation, float $qty, int $idProduct = 0): void
	{
		self::ensureSchema();

		$qty = round($qty, 3);

		if ($idVariation <= 0 || $qty <= 0) {
			return;
		}

		$sql = 'UPDATE product_variations SET stock = stock + ? WHERE id_variation = ?';
		$params = [$qty, $idVariation];

		if ($idProduct > 0) {
			$sql .= ' AND id_product = ?';
			$params[] = $idProduct;
		}

		DB::execute($sql, $params);

		if ($idProduct > 0) {
			$totalStock = self::getTotalStock($idProduct);
			DB::update('products', ['stock' => $totalStock], 'id_product = :where_id', ['where_id' => $idProduct]);
		}
	}

	/** @return array<string, mixed> */
	public static function formatForApi(array $variation): array
	{
		return [
			'id' => (int) ($variation['id_variation'] ?? 0),
			'id_variation' => (int) ($variation['id_variation'] ?? 0),
			'sku' => (string) ($variation['sku'] ?? ''),
			'barcode' => (string) ($variation['barcode'] ?? ''),
			'options' => is_array($variation['options'] ?? null) ? $variation['options'] : [],
			'price' => $variation['price'] !== null ? (float) $variation['price'] : null,
			'stock' => (int) ($variation['stock'] ?? 0),
			'active' => (int) ($variation['active'] ?? 0) === 1,
		];
	}

	/** @return array<string, string> */
	private static function normalizeOptions($options): array
	{
		if (!is_array($options)) {
			return [];
		}

		$normalized = [];

		foreach ($options as $key => $value) {
			if (is_array($value) && isset($value['name'], $value['value'])) {
				$name = trim((string) $value['name']);
				$val = trim((string) $value['value']);
			} else {
				$name = trim((string) $key);
				$val = trim((string) $value);
			}

			if ($name === '' || $val === '') {
				continue;
			}

			$normalized[$name] = $val;
		}

		return $normalized;
	}

	/** @param array<string, mixed> $row */
	private static function normalizeRow(array $row): array
	{
		$options = json_decode((string) ($row['options_json'] ?? '{}'), true);

		$row['options'] = is_array($options) ? $options : [];
		$row['id_variation'] = (int) ($row['id_variation'] ?? 0);
		$row['id_product'] = (int) ($row['id_product'] ?? 0);
		$row['stock'] = (int) ($row['stock'] ?? 0);
		$row['active'] = (int) ($row['active'] ?? 0);
		$row['price'] = $row['price'] !== null ? (float) $row['price'] : null;

		return $row;
	}
}
