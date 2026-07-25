<?php

namespace Trendyol;

class OrderService
{
	private const CANCEL_STATUSES = [
		'Cancelled',
		'UnSupplied',
		'Returned',
		'UnDelivered',
	];

	/**
	 * @return array{ok: bool, message: string, count?: int, stock_updates?: int, orders?: array<int, array<string, mixed>>}
	 */
	public static function syncOrders(?string $startDate = null, ?string $endDate = null): array
	{
		ProductSyncService::ensureSchema();

		if (!ProductSyncService::isConfigured()) {
			return ['ok' => false, 'message' => 'Trendyol API kimlik bilgileri tanımlı değil'];
		}

		$api = ProductSyncService::api();

		if ($startDate && $endDate) {
			$result = $api->getOrderInf(0, $startDate, $endDate);
		} else {
			$result = $api->getOrders();
		}

		if (ProductSyncService::isApiError($result)) {
			return ['ok' => false, 'message' => (string) ($result['message'] ?? 'Siparişler alınamadı')];
		}

		$content = [];

		if (isset($result['content']) && is_array($result['content'])) {
			$content = $result['content'];
		} elseif (is_array($result) && isset($result[0])) {
			$content = $result;
		}

		$count = 0;
		$stockUpdates = 0;
		$now = date('Y-m-d H:i:s');

		foreach ($content as $pkg) {
			if (!is_array($pkg)) {
				continue;
			}

			$stockUpdates += self::upsertPackage($pkg, $now);
			$count++;
		}

		return [
			'ok' => true,
			'message' => $count . ' sipariş paketi senkronize edildi'
				. ($stockUpdates > 0 ? (', ' . $stockUpdates . ' stok hareketi') : ''),
			'count' => $count,
			'stock_updates' => $stockUpdates,
			'orders' => self::getRecent(50),
		];
	}

	/**
	 * @param array<string, mixed> $pkg
	 * @return int stock movement count
	 */
	private static function upsertPackage(array $pkg, string $now): int
	{
		$orderNumber = (string) ($pkg['orderNumber'] ?? '');
		$packageId = (string) ($pkg['id'] ?? ($pkg['shipmentPackageId'] ?? ''));

		if ($orderNumber === '') {
			return 0;
		}

		$customerName = trim(
			(string) ($pkg['customerFirstName'] ?? '') . ' ' . (string) ($pkg['customerLastName'] ?? '')
		);

		$orderDate = null;
		$ts = $pkg['orderDate'] ?? ($pkg['packageLastModifiedDate'] ?? null);

		if (is_numeric($ts)) {
			$orderDate = date('Y-m-d H:i:s', (int) round(((int) $ts) / 1000));
		}

		$status = (string) ($pkg['status'] ?? '');
		$lines = $pkg['lines'] ?? [];
		self::linkOrderProducts($lines);
		$row = [
			'order_number' => $orderNumber,
			'shipment_package_id' => $packageId,
			'status' => $status,
			'customer_name' => mb_substr($customerName, 0, 255),
			'total_price' => (float) ($pkg['totalPrice'] ?? 0),
			'cargo_tracking_number' => (string) ($pkg['cargoTrackingNumber'] ?? ''),
			'cargo_provider' => (string) ($pkg['cargoProviderName'] ?? ''),
			'lines_json' => json_encode($lines, JSON_UNESCAPED_UNICODE),
			'raw_json' => json_encode($pkg, JSON_UNESCAPED_UNICODE),
			'order_date' => $orderDate,
			'last_sync_at' => $now,
		];

		$existing = \DB::getRowSafe(
			'trendyol_orders',
			'order_number = ? AND shipment_package_id = ?',
			[$orderNumber, $packageId]
		);

		$stockDeducted = (int) ($existing['stock_deducted'] ?? 0);
		$isCancelled = self::isCancelStatus($status);
		$moved = 0;

		if ($existing) {
			\DB::update(
				'trendyol_orders',
				$row,
				'id = :where_id',
				['where_id' => (int) $existing['id']]
			);
			$orderId = (int) $existing['id'];
		} else {
			$row['stock_deducted'] = 0;
			\DB::insert('trendyol_orders', $row);
			$orderId = (int) (\DB::getValue(
				'SELECT id FROM trendyol_orders WHERE order_number = ? AND shipment_package_id = ? LIMIT 1',
				[$orderNumber, $packageId]
			) ?: 0);
		}

		if ($orderId <= 0) {
			return 0;
		}

		// Aktif sipariş → stok düş (bir kez)
		if (!$isCancelled && $stockDeducted === 0) {
			$moved = self::applyLineStock($lines, false);
			\DB::update(
				'trendyol_orders',
				['stock_deducted' => 1, 'last_sync_at' => $now],
				'id = :where_id',
				['where_id' => $orderId]
			);
		}

		// İptal / iade → stok geri ekle (bir kez)
		if ($isCancelled && $stockDeducted === 1) {
			$moved = self::applyLineStock($lines, true);
			\DB::update(
				'trendyol_orders',
				['stock_deducted' => 2, 'last_sync_at' => $now],
				'id = :where_id',
				['where_id' => $orderId]
			);
		}

		return $moved;
	}

	private static function isCancelStatus(string $status): bool
	{
		return in_array($status, self::CANCEL_STATUSES, true);
	}

	/** @param mixed $lines */
	private static function linkOrderProducts($lines): void
	{
		if (!is_array($lines)) {
			return;
		}

		foreach ($lines as $line) {
			if (!is_array($line)) {
				continue;
			}

			$barcode = trim((string) ($line['barcode'] ?? ($line['merchantSku'] ?? '')));

			if ($barcode === '') {
				continue;
			}

			$idProduct = self::findProductIdByBarcode($barcode);

			if ($idProduct <= 0) {
				continue;
			}

			$salePrice = ProductSyncService::extractOrderLineSalePrice($line);
			$listPrice = ProductSyncService::extractOrderLineListPrice($line, $salePrice);
			ProductSyncService::linkFromOrder($idProduct, $barcode, $salePrice, $listPrice);
		}
	}

	/**
	 * @param mixed $lines
	 */
	private static function applyLineStock($lines, bool $restore): int
	{
		if (!is_array($lines)) {
			return 0;
		}

		$moved = 0;
		/** @var array<int, array{barcode: string, sale_price: ?float, list_price: ?float}> $touched */
		$touched = [];

		foreach ($lines as $line) {
			if (!is_array($line)) {
				continue;
			}

			$qty = max(0, (int) ($line['quantity'] ?? 0));
			$barcode = trim((string) ($line['barcode'] ?? ($line['merchantSku'] ?? '')));

			if ($qty <= 0 || $barcode === '') {
				continue;
			}

			$idProduct = self::findProductIdByBarcode($barcode);

			if ($idProduct <= 0) {
				continue;
			}

			if ($restore) {
				\Product::increaseStock($idProduct, $qty);
				$moved++;
			} else {
				if (\Product::decreaseStock($idProduct, $qty)) {
					$moved++;
				}
			}

			if (!isset($touched[$idProduct])) {
				$touched[$idProduct] = [
					'barcode' => $barcode,
					'sale_price' => null,
					'list_price' => null,
				];
			}

			if ($touched[$idProduct]['sale_price'] === null) {
				$lineSale = ProductSyncService::extractOrderLineSalePrice($line);

				if ($lineSale !== null && $lineSale > 0) {
					$touched[$idProduct]['sale_price'] = $lineSale;
					$touched[$idProduct]['list_price'] = ProductSyncService::extractOrderLineListPrice($line, $lineSale);
				}
			}
		}

		foreach ($touched as $idProduct => $data) {
			ProductSyncService::syncAfterOrderStock(
				(int) $idProduct,
				(string) $data['barcode'],
				$data['sale_price'],
				$data['list_price']
			);

			if (class_exists('Marketplace')) {
				\Marketplace::syncProductStockAcrossPlatforms((int) $idProduct, 'trendyol');
			}
		}

		return $moved;
	}

	private static function findProductIdByBarcode(string $barcode): int
	{
		$barcode = trim($barcode);

		if ($barcode === '') {
			return 0;
		}

		ProductSyncService::ensureSchema();

		$map = \DB::getRowSafe('trendyol_products', 'barcode = ?', [$barcode]);

		if ($map && (int) ($map['id_product'] ?? 0) > 0) {
			return (int) $map['id_product'];
		}

		$id = (int) (\DB::getValue(
			'SELECT id_product FROM products WHERE barcode = ? LIMIT 1',
			[$barcode]
		) ?: 0);

		if ($id > 0) {
			return $id;
		}

		$id = (int) (\DB::getValue(
			'SELECT id_product FROM products WHERE stock_code = ? LIMIT 1',
			[$barcode]
		) ?: 0);

		return $id;
	}

	/** @return array<int, array<string, mixed>> */
	public static function getRecent(int $limit = 50): array
	{
		ProductSyncService::ensureSchema();
		$limit = max(1, min(200, $limit));

		$rows = \DB::execute(
			'SELECT * FROM trendyol_orders
			 ORDER BY COALESCE(order_date, last_sync_at) DESC, id DESC
			 LIMIT ' . (int) $limit
		) ?: [];

		foreach ($rows as &$row) {
			$lines = json_decode((string) ($row['lines_json'] ?? ''), true);
			$row['lines'] = is_array($lines) ? $lines : [];
		}
		unset($row);

		return $rows;
	}
}
