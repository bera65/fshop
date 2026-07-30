<?php

/**
 * Pazaryeri sipariş işlemleri: import, durum yenile, sil, iptal + stok.
 */
class MarketplaceOrderOps
{
	private const CANCEL_HINTS = [
		'cancel', 'cancelled', 'canceled', 'unsupplied', 'return', 'returned',
		'iptal', 'iade', 'cancelbymerchant', 'cancelbycustomer',
	];

	public static function shopName(): string
	{
		$name = trim((string) Settings::get('SITE_NAME'));

		return $name !== '' ? $name : 'Mağaza';
	}

	public static function isCancelStatus(string $status): bool
	{
		$key = strtolower(preg_replace('/[\s_\-]+/', '', trim($status)) ?: '');

		if ($key === '') {
			return false;
		}

		foreach (self::CANCEL_HINTS as $hint) {
			if (strpos($key, $hint) !== false) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return array{ok: bool, message: string, count?: int}
	 */
	public static function import(string $platform, string $orderNumber): array
	{
		$platform = strtolower(trim($platform));
		$orderNumber = trim($orderNumber);

		if (!in_array($platform, ['trendyol', 'hepsiburada', 'n11'], true)) {
			return ['ok' => false, 'message' => 'Geçersiz pazaryeri'];
		}

		if ($orderNumber === '') {
			return ['ok' => false, 'message' => 'Sipariş numarası gerekli'];
		}

		Marketplace::setAllowMarketplaceStockPush(true);

		if ($platform === 'trendyol') {
			return Trendyol\OrderService::importByOrderNumber($orderNumber);
		}

		if ($platform === 'hepsiburada') {
			return Hepsiburada\OrderService::importByOrderNumber($orderNumber);
		}

		return N11\OrderService::importByOrderNumber($orderNumber);
	}

	/**
	 * @return array{ok: bool, message: string, count?: int}
	 */
	public static function refresh(string $platform, string $orderNumber, string $packageId = ''): array
	{
		return self::import($platform, $orderNumber);
	}

	/**
	 * @return array{ok: bool, message: string}
	 */
	public static function delete(string $platform, string $orderNumber, string $packageId = ''): array
	{
		$platform = strtolower(trim($platform));
		$orderNumber = trim($orderNumber);
		$packageId = trim($packageId);

		if ($orderNumber === '') {
			return ['ok' => false, 'message' => 'Sipariş numarası gerekli'];
		}

		$deleted = MarketplaceTables::deleteOrder($platform, $orderNumber, $packageId);

		if ($deleted <= 0) {
			return ['ok' => false, 'message' => 'Sipariş bulunamadı'];
		}

		return ['ok' => true, 'message' => 'Sipariş ' . self::shopName() . ' listesinden silindi'];
	}

	/**
	 * @param string $stockMode restore|zero|keep
	 * @return array{ok: bool, message: string}
	 */
	public static function cancel(
		string $platform,
		string $orderNumber,
		string $packageId = '',
		string $stockMode = 'restore'
	): array {
		$platform = strtolower(trim($platform));
		$orderNumber = trim($orderNumber);
		$packageId = trim($packageId);
		$stockMode = in_array($stockMode, ['restore', 'zero', 'keep'], true) ? $stockMode : 'restore';

		$order = MarketplaceTables::findOrder($platform, $orderNumber, $packageId);

		if (!$order) {
			return ['ok' => false, 'message' => 'Sipariş ' . self::shopName() . '\'ta bulunamadı'];
		}

		$packageId = trim((string) ($order['shipment_package_id'] ?? $packageId));
		$lines = json_decode((string) ($order['lines_json'] ?? ''), true);
		$lines = is_array($lines) ? $lines : [];

		$apiResult = null;

		if ($platform === 'trendyol') {
			$apiResult = Trendyol\OrderService::cancelOnMarketplace($order, $lines);
		} elseif ($platform === 'hepsiburada') {
			$apiResult = Hepsiburada\OrderService::cancelOnMarketplace($order, $lines);
		} else {
			$apiResult = N11\OrderService::cancelOnMarketplace($order, $lines);
		}

		if (empty($apiResult['ok'])) {
			return [
				'ok' => false,
				'message' => (string) ($apiResult['message'] ?? 'Pazaryerinde iptal başarısız'),
			];
		}

		MarketplaceTables::updateOrderById((int) $order['id'], [
			'status' => 'Cancelled',
			'last_sync_at' => date('Y-m-d H:i:s'),
		]);

		$stockMsg = self::applyCancelStock($order, $lines, $stockMode);

		return [
			'ok' => true,
			'message' => 'Sipariş iptal edildi' . ($stockMsg !== '' ? ' · ' . $stockMsg : ''),
		];
	}

	/**
	 * @param array<string, mixed> $order
	 * @param array<int, mixed> $lines
	 */
	private static function applyCancelStock(array $order, array $lines, string $stockMode): string
	{
		$touched = [];
		$stockDeducted = (int) ($order['stock_deducted'] ?? 0);
		$orderNumber = (string) ($order['order_number'] ?? '');
		$platform = (string) ($order['platform'] ?? '');

		if ($stockMode !== 'keep') {
			Marketplace::setAllowMarketplaceStockPush(true);

			foreach ($lines as $line) {
				if (!is_array($line)) {
					continue;
				}

				$qty = max(0, (int) ($line['quantity'] ?? 0));
				$idProduct = (int) ($line['id_product'] ?? 0);

				if ($idProduct <= 0) {
					$code = trim((string) (
						$line['merchantSku']
						?? $line['stockCode']
						?? $line['barcode']
						?? $line['hepsiburadaSku']
						?? ''
					));

					if ($code !== '') {
						$idProduct = (int) (\DB::getValue(
							'SELECT id_product FROM products WHERE stock_code = ? OR barcode = ? LIMIT 1',
							[$code, $code]
						) ?: 0);
					}
				}

				if ($idProduct <= 0 || $qty <= 0) {
					continue;
				}

				$product = Product::getByIdAdmin($idProduct);
				$oldStock = $product ? Product::getStock($product) : 0;
				$ref = trim((string) ($product['stock_code'] ?? '')) ?: (string) $idProduct;

				if ($stockMode === 'restore') {
					if ($stockDeducted === 1) {
						Product::increaseStock($idProduct, $qty);
						$newStock = $oldStock + $qty;
						MarketplaceLog::stockChange(
							$platform,
							$ref,
							(int) $oldStock,
							(int) $newStock,
							'ORDER_CANCEL_RESTORE [' . $orderNumber . ']',
							$idProduct,
							$orderNumber
						);
						$touched[$idProduct] = true;
					}
				} elseif ($stockMode === 'zero') {
					\DB::execute('UPDATE products SET stock = 0 WHERE id_product = ?', [$idProduct]);
					MarketplaceLog::stockChange(
						$platform,
						$ref,
						(int) $oldStock,
						0,
						'ORDER_CANCEL_ZERO [' . $orderNumber . ']',
						$idProduct,
						$orderNumber
					);
					$touched[$idProduct] = true;
				}
			}

			foreach (array_keys($touched) as $idProduct) {
				Marketplace::syncProductStockAcrossPlatforms((int) $idProduct, null);
			}
		}

		// Stok kararı verildi — sonraki sync otomatik geri yüklemesin
		if ((int) ($order['id'] ?? 0) > 0 && $stockDeducted === 1) {
			MarketplaceTables::updateOrderById((int) $order['id'], ['stock_deducted' => 2]);
		}

		if ($stockMode === 'restore') {
			return $touched === []
				? 'Stok geri yükleme gerekmedi / uygulanamadı'
				: (count($touched) . ' ürün stoğu geri yüklendi ve pazaryerlerine gönderildi');
		}

		if ($stockMode === 'zero') {
			return $touched === []
				? 'Sıfırlanacak ürün bulunamadı'
				: (count($touched) . ' ürün stoğu 0 yapıldı ve pazaryerlerine gönderildi');
		}

		return 'Stoka dokunulmadı';
	}
}