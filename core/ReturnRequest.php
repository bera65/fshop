<?php

class ReturnRequest
{
	const STATUS_PENDING = 1;
	const STATUS_APPROVED = 2;
	const STATUS_REJECTED = 3;
	const STATUS_COMPLETED = 4;

	const MAX_IMAGES = 3;
	const IMAGE_MAX_BYTES = 5242880;

	public static function ensureSchema(): void
	{
		$table = DB::execute("SHOW TABLES LIKE 'return_requests'");

		if (empty($table)) {
			DB::execute(
				"CREATE TABLE `return_requests` (
					`id_return` int(11) NOT NULL AUTO_INCREMENT,
					`id_order` int(11) NOT NULL,
					`id_user` int(11) NOT NULL,
					`status` tinyint(4) NOT NULL DEFAULT 1,
					`customer_message` text NOT NULL,
					`admin_message` text NOT NULL,
					`admin_receipt_file` varchar(255) NOT NULL DEFAULT '',
					`date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
					`date_upd` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
					`date_resolved` datetime DEFAULT NULL,
					PRIMARY KEY (`id_return`),
					KEY `id_order` (`id_order`),
					KEY `id_user` (`id_user`),
					KEY `status` (`status`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		}

		$imagesTable = DB::execute("SHOW TABLES LIKE 'return_request_images'");

		if (empty($imagesTable)) {
			DB::execute(
				"CREATE TABLE `return_request_images` (
					`id_return_image` int(11) NOT NULL AUTO_INCREMENT,
					`id_return` int(11) NOT NULL,
					`image_file` varchar(255) NOT NULL,
					`date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (`id_return_image`),
					KEY `id_return` (`id_return`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		}

		$receiptCol = DB::execute("SHOW COLUMNS FROM `return_requests` LIKE 'admin_receipt_file'");

		if (empty($receiptCol)) {
			DB::execute(
				"ALTER TABLE `return_requests` ADD COLUMN `admin_receipt_file` varchar(255) NOT NULL DEFAULT '' AFTER `admin_message`"
			);
		}

		$exists = DB::getValue('SELECT id FROM settings WHERE title = ? LIMIT 1', ['RETURN_REQUEST_DAYS']);

		if ($exists === false) {
			Settings::set('RETURN_REQUEST_DAYS', '14');
		}

		self::ensureUploadDir();
	}

	public static function getAllowedDays(): int
	{
		return max(0, (int) Settings::get('RETURN_REQUEST_DAYS'));
	}

	public static function isEnabled(): bool
	{
		return self::getAllowedDays() > 0;
	}

	public static function getStatusOptions(): array
	{
		return [
			self::STATUS_PENDING => translate('Return status pending'),
			self::STATUS_APPROVED => translate('Return status approved'),
			self::STATUS_REJECTED => translate('Return status rejected'),
			self::STATUS_COMPLETED => translate('Return status completed'),
		];
	}

	public static function getStatusLabel(int $status): string
	{
		$options = self::getStatusOptions();

		return $options[$status] ?? translate('Return status unknown');
	}

	public static function getStatusBadgeClass(int $status): string
	{
		switch ($status) {
			case self::STATUS_APPROVED:
				return 'bg-primary';
			case self::STATUS_REJECTED:
				return 'bg-danger';
			case self::STATUS_COMPLETED:
				return 'bg-success';
			default:
				return 'bg-warning text-dark';
		}
	}

	public static function countPending(): int
	{
		return (int) DB::getValue(
			'SELECT COUNT(*) FROM return_requests WHERE status = ?',
			[self::STATUS_PENDING]
		);
	}

	public static function countAdmin(int $status = 0): int
	{
		if ($status > 0) {
			return (int) DB::getValue(
				'SELECT COUNT(*) FROM return_requests WHERE status = ?',
				[$status]
			);
		}

		return (int) DB::getValue('SELECT COUNT(*) FROM return_requests');
	}

	public static function getAdminList(int $status, int $limit, int $offset): array
	{
		$params = [];
		$where = '';

		if ($status > 0) {
			$where = 'WHERE rr.status = ?';
			$params[] = $status;
		}

		$rows = DB::execute(
			'SELECT rr.*, o.reference, o.total, o.date_add AS order_date,
				u.user_full_name AS customer_name, u.phone AS customer_phone, u.email AS customer_email
			FROM return_requests rr
			INNER JOIN orders o ON o.id_order = rr.id_order
			INNER JOIN users u ON u.id_user = rr.id_user
			' . $where . '
			ORDER BY rr.id_return DESC
			LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset,
			$params
		) ?: [];

		return self::enrichRows($rows);
	}

	public static function getUserList(int $idUser): array
	{
		if ($idUser <= 0) {
			return [];
		}

		$rows = DB::execute(
			'SELECT rr.*, o.reference, o.total
			FROM return_requests rr
			INNER JOIN orders o ON o.id_order = rr.id_order
			WHERE rr.id_user = ?
			ORDER BY rr.id_return DESC',
			[$idUser]
		) ?: [];

		return self::enrichRows($rows);
	}

	public static function getByIdAdmin(int $idReturn): ?array
	{
		if ($idReturn <= 0) {
			return null;
		}

		$rows = DB::execute(
			'SELECT rr.*, o.reference, o.total, o.status AS order_status, o.date_add AS order_date,
				o.payment_method, o.customer_name, o.customer_phone, o.address_city, o.address_district, o.address_text,
				u.user_full_name AS user_name, u.phone AS user_phone, u.email AS user_email
			FROM return_requests rr
			INNER JOIN orders o ON o.id_order = rr.id_order
			INNER JOIN users u ON u.id_user = rr.id_user
			WHERE rr.id_return = ?
			LIMIT 1',
			[$idReturn]
		);

		if (!$rows || !isset($rows[0])) {
			return null;
		}

		$row = $rows[0];

		$enriched = self::enrichRows([$row]);

		return $enriched[0] ?? null;
	}

	public static function getByIdForUser(int $idReturn, int $idUser): ?array
	{
		if ($idReturn <= 0 || $idUser <= 0) {
			return null;
		}

		$rows = DB::execute(
			'SELECT rr.*, o.reference, o.total
			FROM return_requests rr
			INNER JOIN orders o ON o.id_order = rr.id_order
			WHERE rr.id_return = ? AND rr.id_user = ?
			LIMIT 1',
			[$idReturn, $idUser]
		);

		if (!$rows || !isset($rows[0])) {
			return null;
		}

		$row = $rows[0];

		$enriched = self::enrichRows([$row]);

		return $enriched[0] ?? null;
	}

	public static function getEligibleOrders(int $idUser): array
	{
		if ($idUser <= 0 || !self::isEnabled()) {
			return [];
		}

		$days = self::getAllowedDays();
		$cutoff = date('Y-m-d H:i:s', strtotime('-' . $days . ' days'));

		$rows = DB::execute(
			'SELECT o.*
			FROM orders o
			WHERE o.id_user = ?
				AND o.status = ?
				AND o.date_delivered IS NOT NULL
				AND o.date_delivered >= ?
				AND NOT EXISTS (
					SELECT 1 FROM return_requests rr
					WHERE rr.id_order = o.id_order
						AND rr.status IN (?, ?, ?)
				)
			ORDER BY o.id_order DESC',
			[
				$idUser,
				Order::STATUS_DELIVERED,
				$cutoff,
				self::STATUS_PENDING,
				self::STATUS_APPROVED,
				self::STATUS_COMPLETED,
			]
		) ?: [];

		foreach ($rows as &$row) {
			$row['status_label'] = Order::getStatusLabel((int) $row['status']);
			$row['total_formatted'] = Tools::displayPrice($row['total']);
			$row['date_formatted'] = Tools::formatDate3($row['date_add']);
		}
		unset($row);

		return $rows;
	}

	public static function getForOrder(int $idOrder, int $idUser): ?array
	{
		$rows = DB::execute(
			'SELECT rr.*, o.reference, o.total
			FROM return_requests rr
			INNER JOIN orders o ON o.id_order = rr.id_order
			WHERE rr.id_order = ? AND rr.id_user = ?
			ORDER BY rr.id_return DESC
			LIMIT 1',
			[$idOrder, $idUser]
		);

		if (!$rows || !isset($rows[0])) {
			return null;
		}

		$enriched = self::enrichRows([$rows[0]]);

		return $enriched[0] ?? null;
	}

	public static function isOrderEligible(int $idOrder, int $idUser): bool
	{
		foreach (self::getEligibleOrders($idUser) as $order) {
			if ((int) $order['id_order'] === $idOrder) {
				return true;
			}
		}

		return false;
	}

	public static function create(int $idOrder, int $idUser, string $message, array $files): array
	{
		if (!self::isEnabled()) {
			return self::fail(translate('Return requests are disabled'));
		}

		if ($idUser <= 0) {
			return self::fail(translate('Please log in'));
		}

		if (!self::isOrderEligible($idOrder, $idUser)) {
			return self::fail(translate('This order is not eligible for return'));
		}

		$message = trim($message);

		if ($message === '') {
			return self::fail(translate('Please describe your return request'));
		}

		if (mb_strlen($message) > 5000) {
			return self::fail(translate('Message is too long'));
		}

		$idReturn = DB::insert('return_requests', [
			'id_order' => $idOrder,
			'id_user' => $idUser,
			'status' => self::STATUS_PENDING,
			'customer_message' => $message,
			'admin_message' => '',
		]);

		if (!$idReturn) {
			return self::fail(translate('Return request could not be created'));
		}

		$idReturn = (int) $idReturn;
		$uploadResult = self::saveUploadedImages($idReturn, $files);

		if (!$uploadResult['success']) {
			DB::execute('DELETE FROM return_requests WHERE id_return = ?', [$idReturn]);

			return $uploadResult;
		}

		$order = Order::getByIdForUser($idOrder, $idUser);

		if ($order) {
			Notification::returnRequestSubmitted($idUser, (string) $order['reference'], $idReturn);
			AdminNotification::add(
				'New return request',
				'Return request created for order #' . $order['reference'] . '.',
				self::adminLink('return?id=' . $idReturn),
				'return_request'
			);
		}

		return [
			'success' => true,
			'message' => translate('Return request submitted'),
			'id_return' => $idReturn,
		];
	}

	public static function approve(int $idReturn, string $adminMessage): array
	{
		return self::resolve($idReturn, self::STATUS_APPROVED, $adminMessage, 'approve');
	}

	public static function reject(int $idReturn, string $adminMessage): array
	{
		return self::resolve($idReturn, self::STATUS_REJECTED, $adminMessage, 'reject');
	}

	public static function complete(int $idReturn, string $adminMessage = '', array $receiptFile = []): array
	{
		$return = self::getByIdAdmin($idReturn);

		if (!$return) {
			return self::fail('İade talebi bulunamadı');
		}

		if ((int) $return['status'] !== self::STATUS_APPROVED) {
			return self::fail('Sadece onaylanmış iade talepleri tamamlanabilir');
		}

		$adminMessage = trim($adminMessage);
		$message = $adminMessage !== '' ? $adminMessage : (string) $return['admin_message'];
		$receiptFilename = (string) ($return['admin_receipt_file'] ?? '');

		if (!empty($receiptFile['tmp_name']) && is_uploaded_file($receiptFile['tmp_name'])) {
			$receiptResult = self::storeAdminReceipt($idReturn, $receiptFile, $receiptFilename);

			if (!$receiptResult['success']) {
				return $receiptResult;
			}

			$receiptFilename = $receiptResult['filename'];
		}

		$updateData = [
			'status' => self::STATUS_COMPLETED,
			'admin_message' => $message,
			'date_resolved' => date('Y-m-d H:i:s'),
		];

		if ($receiptFilename !== '') {
			$updateData['admin_receipt_file'] = $receiptFilename;
		}

		$updated = DB::update(
			'return_requests',
			$updateData,
			'id_return = :where_id',
			['where_id' => $idReturn]
		);

		if ($updated === false) {
			return self::fail('İade talebi güncellenemedi');
		}

		Order::setStatusQuiet((int) $return['id_order'], Order::STATUS_RETURNED);

		Notification::returnRequestCompleted(
			(int) $return['id_user'],
			(string) $return['reference'],
			$idReturn,
			$message,
			$receiptFilename
		);

		return ['success' => true, 'message' => 'İade talebi tamamlandı'];
	}

	private static function resolve(int $idReturn, int $newStatus, string $adminMessage, string $action): array
	{
		$return = self::getByIdAdmin($idReturn);

		if (!$return) {
			return self::fail('İade talebi bulunamadı');
		}

		if ((int) $return['status'] !== self::STATUS_PENDING) {
			return self::fail('Bu talep zaten işlendi');
		}

		$adminMessage = trim($adminMessage);

		if ($adminMessage === '') {
			return self::fail('Müşteriye bir mesaj yazın');
		}

		if (mb_strlen($adminMessage) > 5000) {
			return self::fail('Mesaj çok uzun');
		}

		$updateData = [
			'status' => $newStatus,
			'admin_message' => $adminMessage,
			'date_resolved' => date('Y-m-d H:i:s'),
		];

		$updated = DB::update(
			'return_requests',
			$updateData,
			'id_return = :where_id',
			['where_id' => $idReturn]
		);

		if ($updated === false) {
			return self::fail('İade talebi güncellenemedi');
		}

		if ($action === 'approve') {
			Order::setStatusQuiet((int) $return['id_order'], Order::STATUS_RETURN_PENDING);

			Notification::returnRequestApproved(
				(int) $return['id_user'],
				(string) $return['reference'],
				$idReturn,
				$adminMessage
			);
			$flash = 'İade talebi onaylandı, sipariş iade bekleniyor olarak işaretlendi';
		} else {
			Notification::returnRequestRejected(
				(int) $return['id_user'],
				(string) $return['reference'],
				$idReturn,
				$adminMessage
			);
			$flash = 'İade talebi reddedildi';
		}

		return ['success' => true, 'message' => $flash];
	}

	public static function getImages(int $idReturn, bool $forAdmin = false): array
	{
		$rows = DB::execute(
			'SELECT * FROM return_request_images WHERE id_return = ? ORDER BY id_return_image ASC',
			[$idReturn]
		) ?: [];

		foreach ($rows as &$row) {
			$row['url'] = self::getProtectedFileUrl((string) $row['image_file'], $forAdmin);
		}
		unset($row);

		return $rows;
	}

	private static function enrichRows(array $rows): array
	{
		$forAdmin = defined('IN_ADMIN') && IN_ADMIN;

		foreach ($rows as &$row) {
			$row['status_label'] = self::getStatusLabel((int) $row['status']);
			$row['status_badge'] = self::getStatusBadgeClass((int) $row['status']);
			$row['date_formatted'] = Tools::formatDate3($row['date_add']);
			$row['resolved_formatted'] = !empty($row['date_resolved'])
				? Tools::formatDate3($row['date_resolved'])
				: '';
			$row['total_formatted'] = Tools::displayPrice($row['total'] ?? 0);
			$row['order_date_formatted'] = !empty($row['order_date'])
				? Tools::formatDate3($row['order_date'])
				: '';
			$row['order_status_label'] = isset($row['order_status'])
				? Order::getStatusLabel((int) $row['order_status'])
				: '';
			$row['images'] = self::getImages((int) $row['id_return'], $forAdmin);
			$row['admin_receipt_url'] = self::getReceiptUrl((string) ($row['admin_receipt_file'] ?? ''), $forAdmin);
		}
		unset($row);

		return $rows;
	}

	public static function getReceiptUrl(string $filename, bool $forAdmin = false): string
	{
		return self::getProtectedFileUrl($filename, $forAdmin);
	}

	public static function getProtectedFileUrl(string $filename, bool $forAdmin = false): string
	{
		$filename = trim($filename);

		if ($filename === '') {
			return '';
		}

		global $domain;

		if ($forAdmin) {
			return self::adminLink('return-image') . '?file=' . rawurlencode($filename);
		}

		return rtrim($domain, '/') . '/api/return-image.php?file=' . rawurlencode($filename);
	}

	public static function canAccessProtectedFile(string $filename, int $idUser, bool $isAdmin): bool
	{
		if ($isAdmin) {
			return self::isAllowedFilename($filename);
		}

		if ($idUser <= 0 || !self::isAllowedFilename($filename)) {
			return false;
		}

		$imageOwner = (int) DB::getValue(
			'SELECT COUNT(*) FROM return_request_images ri
			 INNER JOIN return_requests rr ON rr.id_return = ri.id_return
			 WHERE ri.image_file = ? AND rr.id_user = ?',
			[$filename, $idUser]
		);

		if ($imageOwner > 0) {
			return true;
		}

		$returnReceipt = (int) DB::getValue(
			'SELECT COUNT(*) FROM return_requests WHERE admin_receipt_file = ? AND id_user = ?',
			[$filename, $idUser]
		);

		if ($returnReceipt > 0) {
			return true;
		}

		$cancelReceipt = (int) DB::getValue(
			'SELECT COUNT(*) FROM cancel_requests WHERE admin_receipt_file = ? AND id_user = ?',
			[$filename, $idUser]
		);

		return $cancelReceipt > 0;
	}

	public static function isAllowedFilename(string $filename): bool
	{
		$filename = basename($filename);

		return (bool) preg_match('/^(return|receipt|cancel-receipt)-[0-9]+-[a-f0-9]+\\.(jpe?g|png|webp)$/i', $filename);
	}

	public static function serveProtectedFile(string $filename): void
	{
		$filename = basename($filename);

		if (!self::isAllowedFilename($filename)) {
			http_response_code(404);
			exit;
		}

		$path = self::uploadDir() . $filename;

		if (!is_readable($path)) {
			http_response_code(404);
			exit;
		}

		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mime = $finfo ? (finfo_file($finfo, $path) ?: 'application/octet-stream') : 'application/octet-stream';

		if ($finfo) {
			finfo_close($finfo);
		}

		header('Content-Type: ' . $mime);
		header('X-Content-Type-Options: nosniff');
		header('Cache-Control: private, max-age=3600');
		readfile($path);
		exit;
	}

	private static function storeAdminReceipt(int $idReturn, array $file, string $oldFilename = ''): array
	{
		self::ensureUploadDir();

		if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
			return self::fail('İade dekontu yüklenemedi');
		}

		if ((int) ($file['size'] ?? 0) > self::IMAGE_MAX_BYTES) {
			return self::fail('İade dekontu çok büyük (en fazla 5 MB)');
		}

		$binary = file_get_contents($file['tmp_name']);

		if (!is_string($binary) || $binary === '') {
			return self::fail('İade dekontu okunamadı');
		}

		$info = @getimagesizefromstring($binary);

		if (!$info) {
			return self::fail('İade dekontu geçerli bir görsel değil');
		}

		$allowed = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];

		if (!in_array($info[2], $allowed, true)) {
			return self::fail('İade dekontu için sadece JPG, PNG veya WEBP yükleyebilirsiniz');
		}

		$extMap = [
			IMAGETYPE_JPEG => 'jpg',
			IMAGETYPE_PNG => 'png',
			IMAGETYPE_WEBP => 'webp',
		];
		$ext = $extMap[$info[2]] ?? 'jpg';
		$filename = 'receipt-' . $idReturn . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
		$path = self::uploadDir() . $filename;

		if (@file_put_contents($path, $binary) === false) {
			return self::fail('İade dekontu kaydedilemedi');
		}

		if ($oldFilename !== '') {
			$oldPath = self::uploadDir() . $oldFilename;

			if (is_file($oldPath)) {
				@unlink($oldPath);
			}
		}

		return ['success' => true, 'filename' => $filename];
	}

	private static function saveUploadedImages(int $idReturn, array $files): array
	{
		self::ensureUploadDir();

		if (empty($files['name']) || !is_array($files['name'])) {
			return ['success' => true, 'message' => ''];
		}

		$hasUpload = false;
		foreach ($files['name'] as $name) {
			if (trim((string) $name) !== '') {
				$hasUpload = true;
				break;
			}
		}

		if (!$hasUpload) {
			return ['success' => true, 'message' => ''];
		}

		$count = count($files['name']);
		$saved = 0;

		for ($i = 0; $i < $count; $i++) {
			if ($saved >= self::MAX_IMAGES) {
				break;
			}

			if (empty($files['tmp_name'][$i]) || !is_uploaded_file($files['tmp_name'][$i])) {
				continue;
			}

			$file = [
				'name' => $files['name'][$i],
				'type' => $files['type'][$i] ?? '',
				'tmp_name' => $files['tmp_name'][$i],
				'error' => $files['error'][$i] ?? UPLOAD_ERR_OK,
				'size' => $files['size'][$i] ?? 0,
			];

			$result = self::storeImage($idReturn, $file);

			if (!$result['success']) {
				self::deleteImages($idReturn);

				return $result;
			}

			$saved++;
		}

		return ['success' => true, 'message' => ''];
	}

	private static function storeImage(int $idReturn, array $file): array
	{
		if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
			return self::fail(translate('Image upload failed'));
		}

		if ((int) ($file['size'] ?? 0) > self::IMAGE_MAX_BYTES) {
			return self::fail(translate('Image is too large'));
		}

		$binary = file_get_contents($file['tmp_name']);

		if (!is_string($binary) || $binary === '') {
			return self::fail(translate('Image could not be read'));
		}

		$info = @getimagesizefromstring($binary);

		if (!$info) {
			return self::fail(translate('File is not a valid image'));
		}

		$allowed = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];

		if (!in_array($info[2], $allowed, true)) {
			return self::fail(translate('Only JPG, PNG or WEBP images are allowed'));
		}

		$extMap = [
			IMAGETYPE_JPEG => 'jpg',
			IMAGETYPE_PNG => 'png',
			IMAGETYPE_WEBP => 'webp',
		];
		$ext = $extMap[$info[2]] ?? 'jpg';
		$filename = 'return-' . $idReturn . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
		$path = self::uploadDir() . $filename;

		if (@file_put_contents($path, $binary) === false) {
			return self::fail(translate('Image could not be saved'));
		}

		$inserted = DB::insert('return_request_images', [
			'id_return' => $idReturn,
			'image_file' => $filename,
		]);

		if (!$inserted) {
			@unlink($path);

			return self::fail(translate('Image record could not be saved'));
		}

		return ['success' => true, 'message' => ''];
	}

	private static function deleteImages(int $idReturn): void
	{
		$rows = DB::execute(
			'SELECT image_file FROM return_request_images WHERE id_return = ?',
			[$idReturn]
		) ?: [];

		foreach ($rows as $row) {
			$path = self::uploadDir() . (string) $row['image_file'];

			if (is_file($path)) {
				@unlink($path);
			}
		}

		DB::execute('DELETE FROM return_request_images WHERE id_return = ?', [$idReturn]);
	}

	private static function uploadDir(): string
	{
		return dirname(__DIR__) . '/img/returns/';
	}

	private static function ensureUploadDir(): void
	{
		$dir = self::uploadDir();

		if (!is_dir($dir)) {
			@mkdir($dir, 0755, true);
		}

		$guard = $dir . 'index.php';

		if (!is_file($guard)) {
			@file_put_contents($guard, "<?php\nheader('Location: ../');\nexit;\n");
		}
	}

	private static function adminLink(string $path): string
	{
		if (class_exists('Admin', false)) {
			return Admin::url($path);
		}

		global $domain;
		$uri = 'admin';

		if (class_exists('App', false)) {
			$raw = trim((string) App::env('ADMIN_URI', 'admin'), "/ \t\n\r\0\x0B");
			$sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '', $raw);
			$uri = ($sanitized !== null && $sanitized !== '') ? $sanitized : 'admin';
		}

		return rtrim((string) $domain, '/') . '/' . $uri . '/' . ltrim($path, '/');
	}

	private static function fail(string $message): array
	{
		return ['success' => false, 'message' => $message];
	}
}
