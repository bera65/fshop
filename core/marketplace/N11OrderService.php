<?php

namespace N11;

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
	public static function syncOrders(): array
	{
		ProductSyncService::ensureSchema();

		if (!ProductSyncService::isConfigured()) {
			return ['ok' => false, 'message' => 'N11 API kimlik bilgileri tanımlı değil'];
		}

		$result = ProductSyncService::api()->getOrders();

		if (ProductSyncService::isApiError($result)) {
			return ['ok' => false, 'message' => (string) ($result['message'] ?? 'Siparişler alınamadı')];
		}

		$content = [];

		if (is_array($result) && isset($result[0])) {
			$content = $result;
		} elseif (isset($result['content']) && is_array($result['content'])) {
			$content = $result['content'];
		}

		$count = 0;
		$stockUpdates = 0;
		$now = date('Y-m-d H:i:s');

		foreach ($content as $pkg) {
			if (!is_array($pkg)) {
				continue;
			}

			$moved = self::upsertPackage($pkg, $now);

			if ($moved < 0) {
				continue;
			}

			$stockUpdates += $moved;
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
	 * @return array{ok: bool, message: string, count?: int}
	 */
	public static function importByOrderNumber(string $orderNumber): array
	{
		ProductSyncService::ensureSchema();

		if (!ProductSyncService::isConfigured()) {
			return ['ok' => false, 'message' => 'N11 API kimlik bilgileri tanımlı değil'];
		}

		$result = ProductSyncService::api()->getOrderDetail($orderNumber);

		if (ProductSyncService::isApiError($result)) {
			return ['ok' => false, 'message' => (string) ($result['message'] ?? 'Sipariş alınamadı')];
		}

		$content = [];

		if (isset($result['content']) && is_array($result['content'])) {
			$content = $result['content'];
		} elseif (is_array($result) && isset($result[0])) {
			$content = $result;
		}

		if ($content === []) {
			return ['ok' => false, 'message' => 'Sipariş bulunamadı'];
		}

		$count = 0;
		$stockUpdates = 0;
		$now = date('Y-m-d H:i:s');

		foreach ($content as $pkg) {
			if (!is_array($pkg)) {
				continue;
			}

			$moved = self::upsertPackage($pkg, $now);

			if ($moved < 0) {
				return ['ok' => false, 'message' => 'İptal sipariş ' . \MarketplaceOrderOps::shopName() . '\'ta yok; içe aktarılmadı'];
			}

			$stockUpdates += $moved;
			$count++;
		}

		return [
			'ok' => $count > 0,
			'message' => $count > 0
				? ($count . ' paket içe aktarıldı/güncellendi'
					. ($stockUpdates > 0 ? (', ' . $stockUpdates . ' stok hareketi') : ''))
				: 'İşlenecek paket yok',
			'count' => $count,
		];
	}

	/**
	 * @param array<string, mixed> $order
	 * @param array<int, mixed> $lines
	 * @return array{ok: bool, message: string}
	 */
	public static function cancelOnMarketplace(array $order, array $lines): array
	{
		if (!ProductSyncService::isConfigured()) {
			return ['ok' => false, 'message' => 'N11 API kimlik bilgileri tanımlı değil'];
		}

		$cancelledItems = [];

		foreach ($lines as $line) {
			if (!is_array($line)) {
				continue;
			}

			$lineId = $line['orderLineId'] ?? ($line['lineId'] ?? ($line['id'] ?? null));
			$qty = max(1, (int) ($line['quantity'] ?? 1));

			if ($lineId === null || $lineId === '') {
				continue;
			}

			$cancelledItems[] = [
				'cancelReasonId' => 61,
				'orderLineId' => (int) $lineId,
				'quantity' => $qty,
			];
		}

		if ($cancelledItems === []) {
			return ['ok' => false, 'message' => 'İptal edilecek satır bulunamadı'];
		}

		$result = ProductSyncService::api()->cancelPackageItems($cancelledItems);

		if (ProductSyncService::isApiError($result)) {
			return ['ok' => false, 'message' => (string) ($result['message'] ?? 'N11 iptal hatası')];
		}

		return ['ok' => true, 'message' => 'N11 iptal edildi'];
	}

	/**
	 * @param array<string, mixed> $pkg
	 * @return int stock movement; -1 skipped cancelled new
	 */
	private static function upsertPackage(array $pkg, string $now): int
	{
		$orderNumber = (string) ($pkg['orderNumber'] ?? '');
		$packageId = (string) ($pkg['id'] ?? ($pkg['shipmentPackageId'] ?? ''));

		if ($orderNumber === '') {
			return 0;
		}

		$customerName = trim(
			(string) ($pkg['customerFullName'] ?? '')
		);

		if ($customerName === '') {
			$customerName = trim(
				(string) ($pkg['shippingAddress']['fullName'] ?? '')
			);
		}

		if ($customerName === '') {
			$customerName = trim(
				(string) ($pkg['customerFirstName'] ?? '') . ' ' . (string) ($pkg['customerLastName'] ?? '')
			);
		}

		$orderDate = null;
		$rawDate = $pkg['orderDate'] ?? ($pkg['lastModifiedDate'] ?? ($pkg['packageLastModifiedDate'] ?? null));

		if (is_numeric($rawDate)) {
			$ts = strlen((string) $rawDate) > 10 ? (int) round(((int) $rawDate) / 1000) : (int) $rawDate;
			$orderDate = date('Y-m-d H:i:s', $ts);
		} elseif (is_string($rawDate) && $rawDate !== '') {
			$ts = strtotime($rawDate);
			$orderDate = $ts ? date('Y-m-d H:i:s', $ts) : null;
		}

		$status = (string) ($pkg['status'] ?? ($pkg['packageStatus'] ?? ($pkg['shipmentStatus'] ?? '')));
		$existing = \MarketplaceTables::findOrder('n11', $orderNumber, $packageId);
		$isCancelled = self::isCancelStatus($status);

		if ($isCancelled && !$existing) {
			return -1;
		}

		$lines = $pkg['lines'] ?? [];

		if (!is_array($lines)) {
			$lines = [];
		}

		self::linkOrderProducts($lines);
		$idProduct = 0;

		foreach ($lines as $line) {
			if (is_array($line) && (int) ($line['id_product'] ?? 0) > 0) {
				$idProduct = (int) $line['id_product'];
				break;
			}
		}

		$row = [
			'order_number' => $orderNumber,
			'shipment_package_id' => $packageId,
			'status' => $status,
			'customer_name' => mb_substr($customerName, 0, 255),
			'total_price' => (float) ($pkg['totalAmount'] ?? ($pkg['totalPrice'] ?? 0)),
			'cargo_tracking_number' => (string) ($pkg['cargoTrackingNumber'] ?? ($pkg['trackingNumber'] ?? '')),
			'cargo_tracking_link' => \MarketplaceTables::extractCargoTrackingLink($pkg),
			'cargo_provider' => (string) ($pkg['cargoProviderName'] ?? ($pkg['cargoCompany'] ?? '')),
			'id_product' => $idProduct,
			'lines_json' => json_encode($lines, JSON_UNESCAPED_UNICODE),
			'raw_json' => json_encode($pkg, JSON_UNESCAPED_UNICODE),
			'order_date' => $orderDate,
			'last_sync_at' => $now,
		];

		$stockDeducted = (int) ($existing['stock_deducted'] ?? 0);
		$moved = 0;
		$isNew = !$existing;

		$orderId = \MarketplaceTables::upsertOrder('n11', $row, true);

		if ($orderId <= 0) {
			return 0;
		}

		if ($isNew) {
			\MarketplaceLog::newOrder('n11', $orderNumber, $lines);
			\MarketplaceLog::checkOrderLinesMinPrice('n11', $orderNumber, $lines);
		}

		if (!$isCancelled && $stockDeducted === 0) {
			$moved = self::applyLineStock($lines, false, $orderNumber);
			\MarketplaceTables::updateOrderById($orderId, ['stock_deducted' => 1, 'last_sync_at' => $now]);
		}

		if ($isCancelled && $stockDeducted === 1) {
			$moved = self::applyLineStock($lines, true, $orderNumber);
			\MarketplaceTables::updateOrderById($orderId, ['stock_deducted' => 2, 'last_sync_at' => $now]);
		}

		return $moved;
	}

	private static function isCancelStatus(string $status): bool
	{
		return in_array($status, self::CANCEL_STATUSES, true);
	}

	/** @param mixed $lines */
	private static function linkOrderProducts(&$lines): void
	{
		if (!is_array($lines)) {
			return;
		}

		foreach ($lines as &$line) {
			if (!is_array($line)) {
				continue;
			}

			$stockCode = trim((string) ($line['stockCode'] ?? ''));
			$idProduct = 0;

			if ($stockCode !== '') {
				$idProduct = self::findProductIdByStockCode($stockCode);

				if ($idProduct > 0) {
					$salePrice = ProductSyncService::extractOrderLineSalePrice($line);
					$listPrice = ProductSyncService::extractOrderLineListPrice($line, $salePrice);
					ProductSyncService::linkFromOrder($idProduct, $stockCode, $salePrice, $listPrice);
				}
			}

			$line['id_product'] = $idProduct;
		}
		unset($line);
	}

	/**
	 * @param mixed $lines
	 */
	private static function applyLineStock($lines, bool $restore, string $orderNumber = ''): int
	{
		if (!is_array($lines)) {
			return 0;
		}

		$moved = 0;
		/** @var array<int, array{stock_code: string, sale_price: ?float, list_price: ?float}> $touched */
		$touched = [];

		foreach ($lines as $line) {
			if (!is_array($line)) {
				continue;
			}

			$qty = max(0, (int) ($line['quantity'] ?? 0));
			$stockCode = trim((string) ($line['stockCode'] ?? ''));

			if ($qty <= 0 || $stockCode === '') {
				continue;
			}

			$idProduct = self::findProductIdByStockCode($stockCode);

			if ($idProduct <= 0) {
				continue;
			}

			$product = \Product::getByIdAdmin($idProduct);
			$oldStock = $product ? \Product::getStock($product) : 0;
			$ref = trim((string) ($product['stock_code'] ?? ''));

			if ($ref === '') {
				$ref = $stockCode;
			}

			$ok = false;

			if ($restore) {
				\Product::increaseStock($idProduct, $qty);
				$ok = true;
				$moved++;
			} else {
				if (\Product::decreaseStock($idProduct, $qty)) {
					$ok = true;
					$moved++;
				}
			}

			if ($ok) {
				$newStock = $restore ? ($oldStock + $qty) : max(0, $oldStock - $qty);
				\MarketplaceLog::stockChange(
					'n11',
					$ref,
					$oldStock,
					$newStock,
					$restore ? ('ORDER_CANCEL [' . $orderNumber . ']') : ('ORDER [' . $orderNumber . ']'),
					$idProduct,
					$orderNumber
				);
			}

			if (!isset($touched[$idProduct])) {
				$touched[$idProduct] = [
					'stock_code' => $stockCode,
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
			if (!\Marketplace::allowMarketplaceStockPush()) {
				continue;
			}

			ProductSyncService::syncAfterOrderStock(
				(int) $idProduct,
				(string) $data['stock_code'],
				$data['sale_price'],
				$data['list_price']
			);

			\Marketplace::syncProductStockAcrossPlatforms((int) $idProduct, 'n11');
		}

		return $moved;
	}

	private static function findProductIdByStockCode(string $stockCode): int
	{
		$stockCode = trim($stockCode);

		if ($stockCode === '') {
			return 0;
		}

		ProductSyncService::ensureSchema();

		$map = \DB::getRowSafe('n11_products', 'stock_code = ? OR barcode = ?', [$stockCode, $stockCode]);

		if ($map && (int) ($map['id_product'] ?? 0) > 0) {
			return (int) $map['id_product'];
		}

		$id = (int) (\DB::getValue(
			'SELECT id_product FROM products WHERE stock_code = ? LIMIT 1',
			[$stockCode]
		) ?: 0);

		if ($id > 0) {
			return $id;
		}

		return (int) (\DB::getValue(
			'SELECT id_product FROM products WHERE barcode = ? LIMIT 1',
			[$stockCode]
		) ?: 0);
	}

	/** @return array<int, array<string, mixed>> */
	public static function getRecent(int $limit = 50): array
	{
		return \MarketplaceTables::getRecentOrders('n11', $limit);
	}
}
