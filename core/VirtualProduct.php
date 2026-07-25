<?php

class VirtualProduct
{
	private static bool $schemaReady = false;

	public static function ensureSchema(): void
	{
		if (self::$schemaReady) {
			return;
		}

		self::$schemaReady = true;

		$productType = DB::execute("SHOW COLUMNS FROM `products` LIKE 'product_type'");
		if (empty($productType)) {
			DB::execute(
				"ALTER TABLE `products`
				 ADD COLUMN `product_type` varchar(16) NOT NULL DEFAULT 'physical' AFTER `desi`,
				 ADD COLUMN `virtual_kind` varchar(16) NOT NULL DEFAULT '' AFTER `product_type`,
				 ADD COLUMN `virtual_file` varchar(255) NOT NULL DEFAULT '' AFTER `virtual_kind`,
				 ADD COLUMN `virtual_file_name` varchar(255) NOT NULL DEFAULT '' AFTER `virtual_file`,
				 ADD COLUMN `virtual_text` text NULL AFTER `virtual_file_name`"
			);
		}

		$detailDelivery = DB::execute("SHOW COLUMNS FROM `order_detail` LIKE 'virtual_delivery'");
		if (empty($detailDelivery)) {
			DB::execute(
				"ALTER TABLE `order_detail`
				 ADD COLUMN `virtual_delivery` text NULL AFTER `total`,
				 ADD COLUMN `download_token` varchar(64) NOT NULL DEFAULT '' AFTER `virtual_delivery`"
			);
		}

		$table = DB::execute("SHOW TABLES LIKE 'product_license_keys'");
		if (empty($table)) {
			DB::execute(
				"CREATE TABLE `product_license_keys` (
					`id_license` int(11) NOT NULL AUTO_INCREMENT,
					`id_product` int(11) NOT NULL,
					`license_key` varchar(512) NOT NULL,
					`status` varchar(16) NOT NULL DEFAULT 'available',
					`id_order_detail` int(11) NOT NULL DEFAULT 0,
					`date_used` datetime DEFAULT NULL,
					PRIMARY KEY (`id_license`),
					KEY `id_product` (`id_product`),
					KEY `status` (`status`),
					UNIQUE KEY `product_key` (`id_product`, `license_key`(191))
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		}
	}

	public static function isVirtualProduct(array $product): bool
	{
		return ($product['product_type'] ?? 'physical') === 'virtual';
	}

	public static function getKind(array $product): string
	{
		return trim((string) ($product['virtual_kind'] ?? ''));
	}

	public static function getKindLabel(string $kind): string
	{
		$labels = [
			'download' => 'Downloadable file',
			'license' => 'License key',
			'text' => 'Text delivery',
		];

		$key = $labels[$kind] ?? '';

		return $key !== '' ? translate($key) : '';
	}

	public static function storageDir(int $idProduct): string
	{
		return dirname(__DIR__) . '/storage/digital/' . (int) $idProduct;
	}

	/** Ensure storage/ is not web-accessible (Apache). */
	public static function ensureStorageProtection(): void
	{
		$root = dirname(__DIR__) . '/storage';
		$htaccess = $root . '/.htaccess';

		if (!is_dir($root)) {
			@mkdir($root, 0755, true);
		}

		if (!is_file($htaccess)) {
			@file_put_contents(
				$htaccess,
				"# Deny all web access (digital downloads served via PHP only)\n"
				. "<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n"
				. "<IfModule !mod_authz_core.c>\n\tOrder deny,allow\n\tDeny from all\n</IfModule>\n"
				. "Options -Indexes\n"
			);
		}
	}

	public static function countAvailableLicenses(int $idProduct): int
	{
		self::ensureSchema();

		return (int) DB::getValue(
			"SELECT COUNT(*) FROM product_license_keys WHERE id_product = ? AND status = 'available'",
			[$idProduct]
		);
	}

	public static function countUsedLicenses(int $idProduct): int
	{
		self::ensureSchema();

		return (int) DB::getValue(
			"SELECT COUNT(*) FROM product_license_keys WHERE id_product = ? AND status = 'used'",
			[$idProduct]
		);
	}

	public static function getAvailableLicenses(int $idProduct): array
	{
		self::ensureSchema();

		$rows = DB::execute(
			"SELECT id_license, license_key
			 FROM product_license_keys
			 WHERE id_product = ? AND status = 'available'
			 ORDER BY id_license ASC",
			[$idProduct]
		);

		return $rows ?: [];
	}

	public static function getAssignedLicensesForOrderDetail(int $idOrderDetail, ?string $virtualDelivery = null): array
	{
		self::ensureSchema();

		$rows = DB::execute(
			"SELECT license_key, date_used
			 FROM product_license_keys
			 WHERE id_order_detail = ? AND status = 'used'
			 ORDER BY id_license ASC",
			[$idOrderDetail]
		);

		if ($rows) {
			return $rows;
		}

		$delivery = trim((string) ($virtualDelivery ?? ''));

		if ($delivery === '') {
			return [];
		}

		$keys = [];

		foreach (preg_split('/\R+/', $delivery) ?: [] as $line) {
			$key = trim($line);

			if ($key !== '') {
				$keys[] = [
					'license_key' => $key,
					'date_used' => '',
				];
			}
		}

		return $keys;
	}

	public static function enrichAdminOrderItem(array &$item): void
	{
		self::ensureSchema();

		$idProduct = (int) ($item['id_product'] ?? 0);
		$idOrderDetail = (int) ($item['id_order_detail'] ?? 0);
		$product = $idProduct > 0 ? Product::getByIdAdmin($idProduct) : null;

		$item['is_virtual'] = $product ? self::isVirtualProduct($product) : false;
		$item['virtual_kind'] = $product ? self::getKind($product) : '';
		$item['virtual_kind_label'] = self::getKindLabel($item['virtual_kind']);
		$item['license_keys'] = [];
		$item['virtual_delivery_admin'] = trim((string) ($item['virtual_delivery'] ?? ''));
		$item['has_download'] = trim((string) ($item['download_token'] ?? '')) !== '';

		if (!$item['is_virtual']) {
			return;
		}

		if ($item['virtual_kind'] === 'license') {
			$item['license_keys'] = self::getAssignedLicensesForOrderDetail(
				$idOrderDetail,
				$item['virtual_delivery_admin']
			);
		}
	}

	public static function syncLicenseStock(int $idProduct): void
	{
		$available = self::countAvailableLicenses($idProduct);
		DB::update('products', ['stock' => $available], 'id_product = :where_id', ['where_id' => $idProduct]);
	}

	public static function saveLicenseKeys(int $idProduct, string $raw): array
	{
		self::ensureSchema();

		if ($idProduct <= 0) {
			return ['success' => false, 'message' => 'Ürün bulunamadı', 'added' => 0];
		}

		$lines = preg_split('/\R+/', $raw) ?: [];
		$added = 0;
		$skipped = 0;

		foreach ($lines as $line) {
			$key = trim($line);

			if ($key === '') {
				continue;
			}

			$key = mb_substr($key, 0, 512);
			$exists = (int) DB::getValue(
				'SELECT COUNT(*) FROM product_license_keys WHERE id_product = ? AND license_key = ?',
				[$idProduct, $key]
			);

			if ($exists > 0) {
				$skipped++;
				continue;
			}

			$ok = DB::insert('product_license_keys', [
				'id_product' => $idProduct,
				'license_key' => $key,
				'status' => 'available',
			]);

			if ($ok) {
				$added++;
			}
		}

		self::syncLicenseStock($idProduct);

		$message = $added > 0
			? $added . ' lisans anahtarı eklendi'
			: 'Yeni lisans anahtarı eklenmedi';

		if ($skipped > 0) {
			$message .= ' (' . $skipped . ' tekrar atlandı)';
		}

		return [
			'success' => true,
			'message' => $message,
			'added' => $added,
		];
	}

	public static function uploadFile(int $idProduct, array $file): array
	{
		self::ensureSchema();

		if ($idProduct <= 0 || !Product::getByIdAdmin($idProduct)) {
			return ['success' => false, 'message' => 'Ürün bulunamadı'];
		}

		if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
			return ['success' => false, 'message' => 'Geçerli bir dosya seçin'];
		}

		$originalName = trim((string) ($file['name'] ?? 'dosya'));
		$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
		$allowed = ['zip', 'rar', '7z', 'pdf', 'txt', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'mp3', 'mp4', 'key', 'lic'];

		if ($extension === '' || !in_array($extension, $allowed, true)) {
			return ['success' => false, 'message' => 'Desteklenmeyen dosya türü. İzin verilen: ' . implode(', ', $allowed)];
		}

		$maxBytes = 50 * 1024 * 1024;
		$size = (int) ($file['size'] ?? 0);

		if ($size <= 0 || $size > $maxBytes) {
			return ['success' => false, 'message' => 'Dosya boyutu en fazla 50 MB olabilir'];
		}

		$dir = self::storageDir($idProduct);
		self::ensureStorageProtection();

		if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
			return ['success' => false, 'message' => 'Dosya klasörü oluşturulamadı'];
		}

		$storedName = bin2hex(random_bytes(16)) . '.' . $extension;
		$dest = $dir . '/' . $storedName;

		if (!move_uploaded_file($file['tmp_name'], $dest)) {
			return ['success' => false, 'message' => 'Dosya kaydedilemedi'];
		}

		$oldFile = (string) DB::getValue('SELECT virtual_file FROM products WHERE id_product = ?', [$idProduct]);
		if ($oldFile !== '') {
			$oldPath = $dir . '/' . basename($oldFile);
			if (is_file($oldPath)) {
				@unlink($oldPath);
			}
		}

		DB::update('products', [
			'virtual_file' => $storedName,
			'virtual_file_name' => mb_substr($originalName, 0, 255),
		], 'id_product = :where_id', ['where_id' => $idProduct]);

		return [
			'success' => true,
			'message' => 'Dijital dosya yüklendi',
		];
	}

	public static function deleteFile(int $idProduct): array
	{
		self::ensureSchema();

		$row = DB::getRowSafe('products', 'id_product = ?', [$idProduct]);

		if (!$row) {
			return ['success' => false, 'message' => 'Ürün bulunamadı'];
		}

		$file = trim((string) ($row['virtual_file'] ?? ''));

		if ($file !== '') {
			$path = self::storageDir($idProduct) . '/' . basename($file);
			if (is_file($path)) {
				@unlink($path);
			}
		}

		DB::update('products', [
			'virtual_file' => '',
			'virtual_file_name' => '',
		], 'id_product = :where_id', ['where_id' => $idProduct]);

		return [
			'success' => true,
			'message' => 'Dijital dosya silindi',
		];
	}

	public static function getFilePath(int $idProduct): ?string
	{
		$file = trim((string) DB::getValue('SELECT virtual_file FROM products WHERE id_product = ?', [$idProduct]));

		if ($file === '') {
			return null;
		}

		$path = self::storageDir($idProduct) . '/' . basename($file);

		return is_file($path) ? $path : null;
	}

	public static function generateDownloadToken(): string
	{
		return bin2hex(random_bytes(32));
	}

	public static function getDownloadUrl(string $token): string
	{
		global $domain;

		return $domain . 'api/download.php?token=' . urlencode($token);
	}

	public static function fulfillOrderLine(int $idOrderDetail, int $idProduct, int $qty): void
	{
		self::ensureSchema();

		$product = Product::getByIdAdmin($idProduct);

		if (!$product || !self::isVirtualProduct($product)) {
			return;
		}

		$kind = self::getKind($product);
		$delivery = '';
		$downloadToken = '';

		if ($kind === 'download') {
			if (self::getFilePath($idProduct)) {
				$downloadToken = self::generateDownloadToken();
				$delivery = trim((string) ($product['virtual_file_name'] ?? 'Dijital dosya'));
			}
		} elseif ($kind === 'license') {
			$keys = self::assignLicenseKeys($idProduct, $idOrderDetail, max(1, $qty));
			$delivery = implode("\n", $keys);
			self::syncLicenseStock($idProduct);
		} elseif ($kind === 'text') {
			$delivery = trim((string) ($product['virtual_text'] ?? ''));
		}

		DB::update('order_detail', [
			'virtual_delivery' => $delivery,
			'download_token' => $downloadToken,
		], 'id_order_detail = :where_id', ['where_id' => $idOrderDetail]);
	}

	public static function fulfillOrder(int $idOrder): void
	{
		self::ensureSchema();

		$order = DB::getRowSafe('orders', 'id_order = ?', [$idOrder]);

		if (!$order || !Order::isPaymentAccepted((int) $order['status'])) {
			return;
		}

		$lines = DB::execute(
			'SELECT * FROM order_detail WHERE id_order = ? ORDER BY id_order_detail ASC',
			[$idOrder]
		) ?: [];

		foreach ($lines as $line) {
			$idDetail = (int) $line['id_order_detail'];
			$idProduct = (int) $line['id_product'];
			$product = Product::getByIdAdmin($idProduct);

			if (!$product || !self::isVirtualProduct($product)) {
				continue;
			}

			$hasToken = trim((string) ($line['download_token'] ?? '')) !== '';
			$hasDelivery = trim((string) ($line['virtual_delivery'] ?? '')) !== '';

			if ($hasToken || $hasDelivery) {
				continue;
			}

			self::fulfillOrderLine($idDetail, $idProduct, (int) $line['qty']);
		}
	}

	private static function assignLicenseKeys(int $idProduct, int $idOrderDetail, int $qty): array
	{
		$keys = [];

		for ($i = 0; $i < $qty; $i++) {
			$row = DB::execute(
				"SELECT id_license, license_key FROM product_license_keys
				 WHERE id_product = ? AND status = 'available'
				 ORDER BY id_license ASC
				 LIMIT 1",
				[$idProduct]
			);

			if (!$row || !isset($row[0])) {
				break;
			}

			$idLicense = (int) $row[0]['id_license'];
			$key = (string) $row[0]['license_key'];

			DB::update('product_license_keys', [
				'status' => 'used',
				'id_order_detail' => $idOrderDetail,
				'date_used' => date('Y-m-d H:i:s'),
			], 'id_license = :where_id', ['where_id' => $idLicense]);

			$keys[] = $key;
		}

		return $keys;
	}

	public static function enrichOrderItem(array &$item, int $idUser, int $orderStatus = 0): void
	{
		self::ensureSchema();

		$idProduct = (int) ($item['id_product'] ?? 0);
		$product = $idProduct > 0 ? Product::getByIdAdmin($idProduct) : null;

		$item['is_virtual'] = $product ? self::isVirtualProduct($product) : false;
		$item['virtual_kind'] = $product ? self::getKind($product) : '';
		$item['virtual_kind_label'] = self::getKindLabel($item['virtual_kind']);
		$item['delivery_pending'] = false;
		$item['virtual_delivery'] = '';
		$item['delivery_lines'] = [];
		$item['has_download'] = false;
		$item['download_url'] = '';

		if (!$item['is_virtual']) {
			unset($item['download_token']);
			return;
		}

		if (!Order::isPaymentAccepted($orderStatus)) {
			$item['delivery_pending'] = true;
			unset($item['download_token']);
			return;
		}

		$item['virtual_delivery'] = trim((string) ($item['virtual_delivery'] ?? ''));
		$item['delivery_lines'] = $item['virtual_delivery'] !== ''
			? preg_split('/\R+/', $item['virtual_delivery']) ?: []
			: [];
		$item['has_download'] = trim((string) ($item['download_token'] ?? '')) !== '';
		$item['download_url'] = $item['has_download']
			? self::getDownloadUrl((string) $item['download_token'])
			: '';

		unset($item['download_token']);
	}

	public static function serveDownload(string $token, int $idUser): void
	{
		self::ensureSchema();

		$token = trim($token);

		if ($token === '' || strlen($token) !== 64) {
			http_response_code(404);
			exit('Dosya bulunamadı');
		}

		$rows = DB::execute(
			'SELECT od.*, o.id_user, o.status
			 FROM order_detail od
			 INNER JOIN orders o ON o.id_order = od.id_order
			 WHERE od.download_token = ?
			 LIMIT 1',
			[$token]
		);

		if (!$rows || !isset($rows[0])) {
			http_response_code(404);
			exit('Dosya bulunamadı');
		}

		$row = $rows[0];

		if ((int) $row['id_user'] !== $idUser) {
			http_response_code(403);
			exit('Bu dosyaya erişim yetkiniz yok');
		}

		if (!Order::isPaymentAccepted((int) $row['status'])) {
			http_response_code(403);
			exit('Ödeme onayı bekleniyor');
		}

		$path = self::getFilePath((int) $row['id_product']);

		if (!$path) {
			http_response_code(404);
			exit('Dosya bulunamadı');
		}

		$product = Product::getByIdAdmin((int) $row['id_product']);
		$downloadName = $product && trim((string) ($product['virtual_file_name'] ?? '')) !== ''
			? (string) $product['virtual_file_name']
			: basename($path);

		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="' . str_replace('"', '', $downloadName) . '"');
		header('Content-Length: ' . filesize($path));
		header('Cache-Control: private, no-cache');

		readfile($path);
		exit;
	}
}
