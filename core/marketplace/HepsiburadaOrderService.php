<?php

namespace Hepsiburada;

class OrderService
{
	private const CANCEL_STATUSES = [
		'Cancelled',
		'CancelledByMerchant',
		'CancelByCustomer',
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
			return ['ok' => false, 'message' => 'Hepsiburada API kimlik bilgileri tanımlı değil'];
		}

		$result = ProductSyncService::api()->getOrders($startDate, $endDate);

		if (ProductSyncService::isApiError($result)) {
			return ['ok' => false, 'message' => (string) ($result['message'] ?? 'Siparişler alınamadı')];
		}

		$content = [];

		if (isset($result['items']) && is_array($result['items'])) {
			$content = $result['items'];
		} elseif (isset($result['content']) && is_array($result['content'])) {
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
			return ['ok' => false, 'message' => 'Hepsiburada API kimlik bilgileri tanımlı değil'];
		}

		$result = ProductSyncService::api()->getOrderDetail($orderNumber);

		if (ProductSyncService::isApiError($result)) {
			return ['ok' => false, 'message' => (string) ($result['message'] ?? 'Sipariş alınamadı')];
		}

		$packages = [];

		if (isset($result['items']) && is_array($result['items']) && isset($result['items'][0]) && is_array($result['items'][0])) {
			// order detail with line items — wrap as package
			$pkg = $result;
			if (!isset($pkg['orderNumber'])) {
				$pkg['orderNumber'] = $orderNumber;
			}
			$packages[] = $pkg;
		} elseif (is_array($result) && (isset($result['orderNumber']) || isset($result['packageNumber']))) {
			$packages[] = $result;
		} elseif (isset($result[0]) && is_array($result[0])) {
			$packages = $result;
		}

		if ($packages === []) {
			return ['ok' => false, 'message' => 'Sipariş bulunamadı'];
		}

		$count = 0;
		$stockUpdates = 0;
		$now = date('Y-m-d H:i:s');

		foreach ($packages as $pkg) {
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
			return ['ok' => false, 'message' => 'Hepsiburada API kimlik bilgileri tanımlı değil'];
		}

		$api = ProductSyncService::api();
		$cancelled = 0;
		$lastError = '';

		foreach ($lines as $line) {
			if (!is_array($line)) {
				continue;
			}

			$lineId = trim((string) (
				$line['id']
				?? $line['lineItemId']
				?? $line['lineId']
				?? $line['orderLineId']
				?? ''
			));

			if ($lineId === '') {
				continue;
			}

			$result = $api->cancelLineItem($lineId, 'out-of-stock');

			if (ProductSyncService::isApiError($result)) {
				$lastError = (string) ($result['message'] ?? 'HB iptal hatası');
				continue;
			}

			$cancelled++;
		}

		if ($cancelled <= 0) {
			return [
				'ok' => false,
				'message' => $lastError !== '' ? $lastError : 'İptal edilecek satır bulunamadı veya API reddetti',
			];
		}

		return ['ok' => true, 'message' => $cancelled . ' satır iptal edildi'];
	}

	/**
	 * @param array<string, mixed> $pkg
	 * @return int stock movement; -1 skipped cancelled new
	 */
	private static function upsertPackage(array $pkg, string $now): int
	{
		$orderNumber = (string) ($pkg['orderNumber'] ?? '');

		if ($orderNumber === '' && !empty($pkg['items'][0]['orderNumber'])) {
			$orderNumber = (string) $pkg['items'][0]['orderNumber'];
		}

		$packageId = trim((string) ($pkg['packageNumber'] ?? ($pkg['id'] ?? '')));

		if ($packageId === '') {
			$packageId = trim((string) ($pkg['barcode'] ?? ''));
		}

		if ($packageId === '') {
			$packageId = $orderNumber;
		}

		if ($orderNumber === '') {
			return 0;
		}

		$customerName = trim((string) ($pkg['customerName'] ?? ''));

		if ($customerName === '' && !empty($pkg['items'][0]['customer'])) {
			$customerName = trim((string) $pkg['items'][0]['customer']);
		}

		if ($customerName === '' && !empty($pkg['recipientName'])) {
			$customerName = trim((string) $pkg['recipientName']);
		}

		$orderDate = null;
		$rawDate = $pkg['orderDate'] ?? ($pkg['packageDate'] ?? ($pkg['createdDate'] ?? null));

		if (is_numeric($rawDate)) {
			$ts = strlen((string) $rawDate) > 10 ? (int) round(((int) $rawDate) / 1000) : (int) $rawDate;
			$orderDate = date('Y-m-d H:i:s', $ts);
		} elseif (is_string($rawDate) && $rawDate !== '') {
			$ts = strtotime($rawDate);
			$orderDate = $ts ? date('Y-m-d H:i:s', $ts) : null;
		}

		$status = (string) ($pkg['status'] ?? ($pkg['packageStatus'] ?? ($pkg['deliveryStatus'] ?? '')));
		$existing = \MarketplaceTables::findOrder('hepsiburada', $orderNumber, $packageId);
		$isCancelled = self::isCancelStatus($status);

		if ($isCancelled && !$existing) {
			return -1;
		}

		$lines = $pkg['items'] ?? ($pkg['lines'] ?? []);

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

		$totalPrice = self::extractPackageTotalPrice($pkg, $lines);

		$row = [
			'order_number' => $orderNumber,
			'shipment_package_id' => $packageId,
			'status' => $status,
			'customer_name' => mb_substr($customerName, 0, 255),
			'total_price' => $totalPrice,
			'cargo_tracking_number' => (string) ($pkg['barcode'] ?? ($pkg['cargoTrackingNumber'] ?? '')),
			'cargo_tracking_link' => \MarketplaceTables::extractCargoTrackingLink($pkg),
			'cargo_provider' => (string) ($pkg['cargoCompany'] ?? ($pkg['cargoProviderName'] ?? '')),
			'id_product' => $idProduct,
			'lines_json' => json_encode($lines, JSON_UNESCAPED_UNICODE),
			'raw_json' => json_encode($pkg, JSON_UNESCAPED_UNICODE),
			'order_date' => $orderDate,
			'last_sync_at' => $now,
		];

		$stockDeducted = (int) ($existing['stock_deducted'] ?? 0);
		$moved = 0;
		$isNew = !$existing;

		$orderId = \MarketplaceTables::upsertOrder('hepsiburada', $row, true);

		if ($orderId <= 0) {
			return 0;
		}

		if ($isNew) {
			\MarketplaceLog::newOrder('hepsiburada', $orderNumber, $lines);
			\MarketplaceLog::checkOrderLinesMinPrice('hepsiburada', $orderNumber, $lines);
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

	/**
	 * @param array<string, mixed> $pkg
	 * @param array<int, mixed> $lines
	 */
	private static function extractPackageTotalPrice(array $pkg, array $lines): float
	{
		$totalPrice = 0.0;
		$rawTotal = $pkg['totalPrice'] ?? ($pkg['merchantTotalPrice'] ?? ($pkg['totalAmount'] ?? null));

		if (is_array($rawTotal) && isset($rawTotal['amount'])) {
			$totalPrice = (float) $rawTotal['amount'];
		} elseif (is_numeric($rawTotal)) {
			$totalPrice = (float) $rawTotal;
		}

		if ($totalPrice > 1) {
			return round($totalPrice, 2);
		}

		$sum = 0.0;

		foreach ($lines as $line) {
			if (!is_array($line)) {
				continue;
			}

			$qty = max(1, (int) ($line['quantity'] ?? 1));
			$lineTotal = null;

			foreach (['totalPrice', 'merchantTotalPrice', 'lineTotalPrice'] as $key) {
				if (!isset($line[$key])) {
					continue;
				}

				$raw = $line[$key];

				if (is_array($raw) && isset($raw['amount'])) {
					$lineTotal = (float) $raw['amount'];
					break;
				}

				if (is_numeric($raw)) {
					$lineTotal = (float) $raw;
					break;
				}
			}

			if ($lineTotal === null) {
				$unit = ProductSyncService::extractOrderLineSalePrice($line);

				if ($unit !== null && $unit > 0) {
					$lineTotal = $unit * $qty;
				}
			}

			if ($lineTotal !== null && $lineTotal > 0) {
				$sum += $lineTotal;
			}
		}

		if ($sum > 0) {
			return round($sum, 2);
		}

		return round($totalPrice, 2);
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

			$sku = trim((string) ($line['merchantSku'] ?? ($line['merchantSKU'] ?? ($line['hepsiburadaSku'] ?? ''))));
			$idProduct = 0;

			if ($sku !== '') {
				$idProduct = self::findProductIdByBarcode($sku);

				if ($idProduct > 0) {
					$salePrice = ProductSyncService::extractOrderLineSalePrice($line);
					$listPrice = ProductSyncService::extractOrderLineListPrice($line, $salePrice);
					ProductSyncService::linkFromOrder($idProduct, $sku, $salePrice, $listPrice);
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
		/** @var array<int, array{sku: string, sale_price: ?float, list_price: ?float}> $touched */
		$touched = [];

		foreach ($lines as $line) {
			if (!is_array($line)) {
				continue;
			}

			$qty = max(0, (int) ($line['quantity'] ?? 0));
			$sku = trim((string) ($line['merchantSku'] ?? ($line['merchantSKU'] ?? ($line['hepsiburadaSku'] ?? ''))));

			if ($qty <= 0 || $sku === '') {
				continue;
			}

			$idProduct = self::findProductIdByBarcode($sku);

			if ($idProduct <= 0) {
				continue;
			}

			$product = \Product::getByIdAdmin($idProduct);
			$oldStock = $product ? \Product::getStock($product) : 0;
			$ref = trim((string) ($product['stock_code'] ?? ''));

			if ($ref === '') {
				$ref = $sku;
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
					'hepsiburada',
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
					'sku' => $sku,
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
				(string) $data['sku'],
				$data['sale_price'],
				$data['list_price']
			);

			\Marketplace::syncProductStockAcrossPlatforms((int) $idProduct, 'hepsiburada');
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

		$map = \DB::getRowSafe(
			'hepsiburada_products',
			'merchant_sku = ? OR hepsiburada_sku = ?',
			[$barcode, $barcode]
		);

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

		return (int) (\DB::getValue(
			'SELECT id_product FROM products WHERE stock_code = ? LIMIT 1',
			[$barcode]
		) ?: 0);
	}

	/** @return array<int, array<string, mixed>> */
	public static function getRecent(int $limit = 50): array
	{
		return \MarketplaceTables::getRecentOrders('hepsiburada', $limit);
	}
}
