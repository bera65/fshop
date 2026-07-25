<?php

class StockAnalysis
{
	private static bool $schemaReady = false;

	public static function ensureSchema(): void
	{
		if (self::$schemaReady) {
			return;
		}

		self::$schemaReady = true;
		Product::ensureSchema();

		$col = DB::execute("SHOW COLUMNS FROM `products` LIKE 'stock_empty_at'");

		if (empty($col)) {
			DB::execute(
				'ALTER TABLE `products`
				 ADD COLUMN `stock_empty_at` datetime DEFAULT NULL AFTER `stock`'
			);
		}
	}

	public static function touchStockEmptyAt(int $idProduct, int $oldStock, int $newStock): void
	{
		self::ensureSchema();

		if ($idProduct <= 0) {
		 return;
		}

		if ($newStock <= 0 && $oldStock > 0) {
			DB::update('products', [
				'stock_empty_at' => date('Y-m-d H:i:s'),
			], 'id_product = :where_id', ['where_id' => $idProduct]);

			return;
		}

		if ($newStock > 0) {
			DB::update('products', [
				'stock_empty_at' => null,
			], 'id_product = :where_id', ['where_id' => $idProduct]);
		}
	}

	/** @return array<int, array<string, mixed>> */
	public static function getLowStockRows(int $days = 30, int $limit = 100): array
	{
		self::ensureSchema();
		$days = max(1, min(90, $days));
		$limit = max(1, min(200, $limit));

		$sql = 'SELECT p.id_product, p.product_name, p.stock_code, p.stock, p.stock_empty_at,
				c.category_name, b.brand_name,
				COALESCE(s.sold_qty, 0) AS sold_qty,
				COALESCE(s.sold_qty, 0) / ? AS daily_sales
			FROM products p
			INNER JOIN categories c ON c.id_category = p.id_category
			INNER JOIN brands b ON b.id_brand = p.id_brand
			LEFT JOIN (
				SELECT od.id_product, SUM(od.qty) AS sold_qty
				FROM order_detail od
				INNER JOIN orders o ON o.id_order = od.id_order
				WHERE o.status NOT IN (?, ?)
				  AND o.date_add >= DATE_SUB(NOW(), INTERVAL ? DAY)
				GROUP BY od.id_product
			) s ON s.id_product = p.id_product
			WHERE p.active = 1
			  AND p.product_type != ?
			  AND p.stock <= ?
			ORDER BY daily_sales DESC, p.stock ASC, p.product_name ASC
			LIMIT ' . (int) $limit;

		$rows = DB::execute($sql, [
			$days,
			Order::STATUS_CANCELLED,
			Order::STATUS_RETURNED,
			$days,
			'pack',
			10,
		]) ?: [];

		foreach ($rows as &$row) {
			$row = self::enrichRow($row, $days);
		}
		unset($row);

		return $rows;
	}

	/** @return array<int, array<string, mixed>> */
	public static function getOutOfStockBestSellers(int $days = 30, int $limit = 50): array
	{
		self::ensureSchema();
		$days = max(1, min(90, $days));
		$limit = max(1, min(100, $limit));

		$sql = 'SELECT p.id_product, p.product_name, p.stock_code, p.stock, p.stock_empty_at,
				c.category_name, b.brand_name,
				COALESCE(s.sold_qty, 0) AS sold_qty,
				COALESCE(s.sold_qty, 0) / ? AS daily_sales
			FROM products p
			INNER JOIN categories c ON c.id_category = p.id_category
			INNER JOIN brands b ON b.id_brand = p.id_brand
			INNER JOIN (
				SELECT od.id_product, SUM(od.qty) AS sold_qty
				FROM order_detail od
				INNER JOIN orders o ON o.id_order = od.id_order
				WHERE o.status NOT IN (?, ?)
				  AND o.date_add >= DATE_SUB(NOW(), INTERVAL ? DAY)
				GROUP BY od.id_product
				HAVING sold_qty > 0
			) s ON s.id_product = p.id_product
			WHERE p.active = 1
			  AND p.stock <= 0
			ORDER BY sold_qty DESC, p.stock_empty_at DESC
			LIMIT ' . (int) $limit;

		$rows = DB::execute($sql, [
			$days,
			Order::STATUS_CANCELLED,
			Order::STATUS_RETURNED,
			$days,
		]) ?: [];

		foreach ($rows as &$row) {
			$row = self::enrichRow($row, $days);
			$row['stock_empty_label'] = self::formatDaysAgo((string) ($row['stock_empty_at'] ?? ''));
		}
		unset($row);

		return $rows;
	}

	public static function quickAddStock(int $idProduct, int $addQty): array
	{
		self::ensureSchema();
		$idProduct = (int) $idProduct;
		$addQty = max(0, (int) $addQty);

		if ($idProduct <= 0 || $addQty <= 0) {
			return self::fail('Geçerli ürün ve miktar girin');
		}

		$product = Product::getByIdAdmin($idProduct);

		if (!$product) {
			return self::fail('Ürün bulunamadı');
		}

		$oldStock = (int) ($product['stock'] ?? 0);
		$newStock = $oldStock + $addQty;
		$result = Product::patchQuick($idProduct, ['stock' => $newStock]);

		if (!$result['success']) {
			return $result;
		}

		self::touchStockEmptyAt($idProduct, $oldStock, $newStock);

		return self::ok('Stok güncellendi: ' . $newStock);
	}

	/** @param array<string, mixed> $row */
	private static function enrichRow(array $row, int $days): array
	{
		$daily = (float) ($row['daily_sales'] ?? 0);
		$stock = (int) ($row['stock'] ?? 0);
		$row['daily_sales'] = round($daily, 2);
		$row['sold_qty'] = (int) ($row['sold_qty'] ?? 0);
		$row['stock_lifetime_days'] = $daily > 0 ? (int) ceil($stock / $daily) : null;
		$row['stock_lifetime_label'] = $row['stock_lifetime_days'] !== null
			? $row['stock_lifetime_days'] . ' gün'
			: '—';
		$row['edit_url'] = Admin::url('product') . '?id=' . (int) ($row['id_product'] ?? 0);

		return $row;
	}

	private static function formatDaysAgo(string $datetime): string
	{
		if ($datetime === '') {
			return '—';
		}

		$ts = strtotime($datetime);

		if ($ts === false) {
			return '—';
		}

		$days = max(0, (int) floor((time() - $ts) / 86400));

		if ($days === 0) {
			return 'Bugün';
		}

		return $days . ' gün önce';
	}

	/** @return array{success: bool, message: string} */
	private static function ok(string $message): array
	{
		return ['success' => true, 'message' => $message];
	}

	/** @return array{success: bool, message: string} */
	private static function fail(string $message): array
	{
		return ['success' => false, 'message' => $message];
	}
}
