<?php

class CancelRequest
{
	const STATUS_PENDING = 1;
	const STATUS_APPROVED = 2;
	const STATUS_REJECTED = 3;

	const IMAGE_MAX_BYTES = 5242880;

	public static function ensureSchema(): void
	{
		$table = DB::execute("SHOW TABLES LIKE 'cancel_requests'");

		if (empty($table)) {
			DB::execute(
				"CREATE TABLE `cancel_requests` (
					`id_cancel` int(11) NOT NULL AUTO_INCREMENT,
					`id_order` int(11) NOT NULL,
					`id_user` int(11) NOT NULL,
					`status` tinyint(4) NOT NULL DEFAULT 1,
					`customer_message` text NOT NULL,
					`admin_message` text NOT NULL,
					`admin_receipt_file` varchar(255) NOT NULL DEFAULT '',
					`date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
					`date_upd` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
					`date_resolved` datetime DEFAULT NULL,
					PRIMARY KEY (`id_cancel`),
					KEY `id_order` (`id_order`),
					KEY `id_user` (`id_user`),
					KEY `status` (`status`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		}

		ReturnRequest::ensureSchema();
	}

	public static function getStatusOptions(): array
	{
		return [
			self::STATUS_PENDING => 'Beklemede',
			self::STATUS_APPROVED => 'Onaylandı',
			self::STATUS_REJECTED => 'Reddedildi',
		];
	}

	public static function getStatusLabel(int $status): string
	{
		return self::getStatusOptions()[$status] ?? 'Bilinmiyor';
	}

	public static function getStatusBadgeClass(int $status): string
	{
		switch ($status) {
			case self::STATUS_APPROVED:
				return 'bg-success';
			case self::STATUS_REJECTED:
				return 'bg-danger';
			default:
				return 'bg-warning text-dark';
		}
	}

	public static function countPending(): int
	{
		return (int) DB::getValue(
			'SELECT COUNT(*) FROM cancel_requests WHERE status = ?',
			[self::STATUS_PENDING]
		);
	}

	public static function countAdmin(int $status = 0): int
	{
		if ($status > 0) {
			return (int) DB::getValue(
				'SELECT COUNT(*) FROM cancel_requests WHERE status = ?',
				[$status]
			);
		}

		return (int) DB::getValue('SELECT COUNT(*) FROM cancel_requests');
	}

	public static function getAdminList(int $status, int $limit, int $offset): array
	{
		$params = [];
		$where = '';

		if ($status > 0) {
			$where = 'WHERE cr.status = ?';
			$params[] = $status;
		}

		$rows = DB::execute(
			'SELECT cr.*, o.reference, o.total, o.status AS order_status, o.date_add AS order_date,
				u.user_full_name AS customer_name, u.phone AS customer_phone, u.email AS customer_email
			FROM cancel_requests cr
			INNER JOIN orders o ON o.id_order = cr.id_order
			INNER JOIN users u ON u.id_user = cr.id_user
			' . $where . '
			ORDER BY cr.id_cancel DESC
			LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset,
			$params
		) ?: [];

		return self::enrichRows($rows);
	}

	public static function getByIdAdmin(int $idCancel): ?array
	{
		$rows = DB::execute(
			'SELECT cr.*, o.reference, o.total, o.status AS order_status, o.date_add AS order_date,
				o.payment_method, o.customer_name, o.customer_phone,
				u.user_full_name AS user_name, u.phone AS user_phone, u.email AS user_email
			FROM cancel_requests cr
			INNER JOIN orders o ON o.id_order = cr.id_order
			INNER JOIN users u ON u.id_user = cr.id_user
			WHERE cr.id_cancel = ?
			LIMIT 1',
			[$idCancel]
		);

		if (!$rows || !isset($rows[0])) {
			return null;
		}

		$enriched = self::enrichRows([$rows[0]]);

		return $enriched[0] ?? null;
	}

	public static function getByIdForUser(int $idCancel, int $idUser): ?array
	{
		$rows = DB::execute(
			'SELECT cr.*, o.reference, o.total
			FROM cancel_requests cr
			INNER JOIN orders o ON o.id_order = cr.id_order
			WHERE cr.id_cancel = ? AND cr.id_user = ?
			LIMIT 1',
			[$idCancel, $idUser]
		);

		if (!$rows || !isset($rows[0])) {
			return null;
		}

		$enriched = self::enrichRows([$rows[0]]);

		return $enriched[0] ?? null;
	}

	public static function getForOrder(int $idOrder, int $idUser): ?array
	{
		$rows = DB::execute(
			'SELECT cr.*, o.reference, o.total
			FROM cancel_requests cr
			INNER JOIN orders o ON o.id_order = cr.id_order
			WHERE cr.id_order = ? AND cr.id_user = ?
			ORDER BY cr.id_cancel DESC
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
		if ($idUser <= 0 || $idOrder <= 0) {
			return false;
		}

		$order = Order::getByIdForUser($idOrder, $idUser);

		if (!$order || !Order::canCustomerCancel((int) $order['status'])) {
			return false;
		}

		$existing = DB::getValue(
			'SELECT id_cancel FROM cancel_requests
			WHERE id_order = ? AND status IN (?, ?)
			LIMIT 1',
			[$idOrder, self::STATUS_PENDING, self::STATUS_APPROVED]
		);

		return $existing === false;
	}

	public static function create(int $idOrder, int $idUser, string $message): array
	{
		if ($idUser <= 0) {
			return self::fail(translate('Please log in'));
		}

		if (!self::isOrderEligible($idOrder, $idUser)) {
			return self::fail(translate('This order is not eligible for cancellation'));
		}

		$message = trim($message);

		if (mb_strlen($message) > 5000) {
			return self::fail(translate('Message is too long'));
		}

		$idCancel = DB::insert('cancel_requests', [
			'id_order' => $idOrder,
			'id_user' => $idUser,
			'status' => self::STATUS_PENDING,
			'customer_message' => $message,
			'admin_message' => '',
		]);

		if (!$idCancel) {
			return self::fail(translate('Cancel request could not be created'));
		}

		$idCancel = (int) $idCancel;
		$order = Order::getByIdForUser($idOrder, $idUser);

		if ($order) {
			Notification::cancelRequestSubmitted($idUser, (string) $order['reference'], $idOrder);
			AdminNotification::add(
				'New cancel request',
				'Cancel request created for order #' . $order['reference'] . '.',
				self::adminLink('cancel?id=' . $idCancel),
				'cancel_request'
			);
		}

		return [
			'success' => true,
			'message' => translate('Cancel request submitted'),
			'id_cancel' => $idCancel,
		];
	}

	public static function approve(int $idCancel, string $adminMessage, array $receiptFile = []): array
	{
		return self::resolve($idCancel, self::STATUS_APPROVED, $adminMessage, $receiptFile);
	}

	public static function reject(int $idCancel, string $adminMessage): array
	{
		return self::resolve($idCancel, self::STATUS_REJECTED, $adminMessage, []);
	}

	private static function resolve(int $idCancel, int $newStatus, string $adminMessage, array $receiptFile): array
	{
		$cancel = self::getByIdAdmin($idCancel);

		if (!$cancel) {
			return self::fail('İptal talebi bulunamadı');
		}

		if ((int) $cancel['status'] !== self::STATUS_PENDING) {
			return self::fail('Bu talep zaten işlendi');
		}

		$adminMessage = trim($adminMessage);

		if ($adminMessage === '') {
			return self::fail('Müşteriye bir mesaj yazın');
		}

		$updateData = [
			'status' => $newStatus,
			'admin_message' => $adminMessage,
			'date_resolved' => date('Y-m-d H:i:s'),
		];

		if ($newStatus === self::STATUS_APPROVED && !empty($receiptFile['tmp_name']) && is_uploaded_file($receiptFile['tmp_name'])) {
			$receiptResult = self::storeAdminReceipt($idCancel, $receiptFile, (string) ($cancel['admin_receipt_file'] ?? ''));

			if (!$receiptResult['success']) {
				return $receiptResult;
			}

			$updateData['admin_receipt_file'] = $receiptResult['filename'];
		}

		$updated = DB::update(
			'cancel_requests',
			$updateData,
			'id_cancel = :where_id',
			['where_id' => $idCancel]
		);

		if ($updated === false) {
			return self::fail('İptal talebi güncellenemedi');
		}

		if ($newStatus === self::STATUS_APPROVED) {
			Order::updateStatus((int) $cancel['id_order'], Order::STATUS_CANCELLED);

			Notification::cancelRequestApproved(
				(int) $cancel['id_user'],
				(string) $cancel['reference'],
				$idCancel,
				$adminMessage,
				$updateData['admin_receipt_file'] ?? ''
			);

			return ['success' => true, 'message' => 'İptal talebi onaylandı, sipariş iptal edildi'];
		}

		Notification::cancelRequestRejected(
			(int) $cancel['id_user'],
			(string) $cancel['reference'],
			$idCancel,
			$adminMessage
		);

		return ['success' => true, 'message' => 'İptal talebi reddedildi'];
	}

	public static function getReceiptUrl(string $filename, bool $forAdmin = false): string
	{
		return ReturnRequest::getReceiptUrl($filename, $forAdmin);
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
			$row['order_status_label'] = isset($row['order_status'])
				? Order::getStatusLabel((int) $row['order_status'])
				: '';
			$row['admin_receipt_url'] = self::getReceiptUrl((string) ($row['admin_receipt_file'] ?? ''), $forAdmin);
		}
		unset($row);

		return $rows;
	}

	private static function storeAdminReceipt(int $idCancel, array $file, string $oldFilename = ''): array
	{
		if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
			return self::fail('Dekont yüklenemedi');
		}

		if ((int) ($file['size'] ?? 0) > self::IMAGE_MAX_BYTES) {
			return self::fail('Dekont çok büyük (en fazla 5 MB)');
		}

		$binary = file_get_contents($file['tmp_name']);

		if (!is_string($binary) || $binary === '') {
			return self::fail('Dekont okunamadı');
		}

		$info = @getimagesizefromstring($binary);

		if (!$info) {
			return self::fail('Dekont geçerli bir görsel değil');
		}

		$allowed = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];

		if (!in_array($info[2], $allowed, true)) {
			return self::fail('Sadece JPG, PNG veya WEBP yükleyebilirsiniz');
		}

		$extMap = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
		$ext = $extMap[$info[2]] ?? 'jpg';
		$filename = 'cancel-receipt-' . $idCancel . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
		$path = dirname(__DIR__) . '/img/returns/' . $filename;

		if (@file_put_contents($path, $binary) === false) {
			return self::fail('Dekont kaydedilemedi');
		}

		if ($oldFilename !== '') {
			$oldPath = dirname(__DIR__) . '/img/returns/' . $oldFilename;

			if (is_file($oldPath)) {
				@unlink($oldPath);
			}
		}

		return ['success' => true, 'filename' => $filename];
	}

	private static function fail(string $message): array
	{
		return ['success' => false, 'message' => $message];
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
}
