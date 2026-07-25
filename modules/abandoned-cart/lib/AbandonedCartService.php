<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

class AbandonedCartService
{
	public static function ensureSchema(): void
	{
		$module = dirname(__DIR__);
		$sql = $module . '/install.sql';

		if (is_file($sql)) {
			$raw = (string) file_get_contents($sql);
			$parts = preg_split('/;\s*[\r\n]+/', $raw) ?: [];

			foreach ($parts as $part) {
				$part = trim($part);

				if ($part !== '') {
					DB::execute($part);
				}
			}
		}
	}

	public static function getIdleHours(): int
	{
		$hours = (int) Settings::get('ABANDONED_CART_IDLE_HOURS');

		return $hours > 0 ? $hours : 2;
	}

	public static function isAutoRemindEnabled(): bool
	{
		return Settings::get('ABANDONED_CART_AUTO_REMIND') === '1';
	}

	public static function getAutoRemindHours(): int
	{
		$hours = (int) Settings::get('ABANDONED_CART_REMIND_HOURS');

		return $hours > 0 ? $hours : 24;
	}

	public static function syncFromCartSummary(array $cart): void
	{
		self::ensureSchema();

		if (session_status() !== PHP_SESSION_ACTIVE) {
			return;
		}

		$sessionId = session_id();
		$idUser = class_exists('Customer', false) ? Customer::getId() : 0;
		$row = self::findOpenRow($sessionId, $idUser);

		if (!empty($cart['empty'])) {
			if ($row && ($row['status'] ?? '') === 'active') {
				DB::update('abandoned_carts', [
					'status' => 'abandoned',
					'cart_json' => '[]',
					'item_count' => 0,
					'subtotal' => 0,
				], 'id_cart = :where_id', ['where_id' => (int) $row['id_cart']]);
			}

			return;
		}

		$customerEmail = '';
		$customerName = '';

		if ($idUser > 0) {
			$user = DB::getRowSafe('users', 'id_user = ?', [$idUser]);

			if ($user) {
				$customerEmail = trim((string) ($user['email'] ?? ''));
				$customerName = trim((string) ($user['user_full_name'] ?? ''));
			}
		}

		$payload = [
			'session_id' => $sessionId,
			'id_user' => $idUser,
			'customer_email' => $customerEmail,
			'customer_name' => $customerName,
			'cart_json' => json_encode($cart, JSON_UNESCAPED_UNICODE),
			'item_count' => (int) ($cart['count'] ?? 0),
			'subtotal' => (float) ($cart['subtotal'] ?? 0),
			'status' => 'active',
		];

		if ($row) {
			DB::update('abandoned_carts', $payload, 'id_cart = :where_id', ['where_id' => (int) $row['id_cart']]);

			return;
		}

		DB::insert('abandoned_carts', $payload);
	}

	public static function markConverted(array $order): void
	{
		self::ensureSchema();

		if (session_status() !== PHP_SESSION_ACTIVE) {
			return;
		}

		$sessionId = session_id();
		$idUser = (int) ($order['id_user'] ?? 0);
		$idOrder = (int) ($order['id_order'] ?? 0);
		$row = self::findOpenRow($sessionId, $idUser);

		if (!$row) {
			return;
		}

		DB::update('abandoned_carts', [
			'status' => 'converted',
			'id_order' => $idOrder,
		], 'id_cart = :where_id', ['where_id' => (int) $row['id_cart']]);
	}

	public static function refreshAbandonedStatuses(): int
	{
		self::ensureSchema();
		$hours = self::getIdleHours();

		return (int) DB::execute(
			'UPDATE abandoned_carts
			 SET status = ?
			 WHERE status = ?
			   AND item_count > 0
			   AND date_upd < DATE_SUB(NOW(), INTERVAL ? HOUR)',
			['abandoned', 'active', $hours]
		);
	}

	/** @return array<int, array<string, mixed>> */
	public static function getAdminList(string $status = '', int $limit = 100, int $offset = 0): array
	{
		self::ensureSchema();
		self::refreshAbandonedStatuses();

		$sql = 'SELECT ac.*, o.reference AS order_reference
			FROM abandoned_carts ac
			LEFT JOIN orders o ON o.id_order = ac.id_order
			WHERE ac.item_count > 0 OR ac.status = ?';
		$params = ['converted'];

		if ($status !== '' && in_array($status, ['active', 'abandoned', 'converted'], true)) {
			$sql .= ' AND ac.status = ?';
			$params[] = $status;
		}

		$sql .= ' ORDER BY ac.date_upd DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

		$rows = DB::execute($sql, $params) ?: [];

		foreach ($rows as &$row) {
			$row = self::enrichRow($row);
		}
		unset($row);

		return $rows;
	}

	public static function countAdmin(string $status = ''): int
	{
		self::ensureSchema();
		self::refreshAbandonedStatuses();

		$sql = 'SELECT COUNT(*) FROM abandoned_carts WHERE item_count > 0 OR status = ?';
		$params = ['converted'];

		if ($status !== '' && in_array($status, ['active', 'abandoned', 'converted'], true)) {
			$sql .= ' AND status = ?';
			$params[] = $status;
		}

		return (int) DB::getValue($sql, $params);
	}

	/** @param array<string, mixed> $row */
	private static function enrichRow(array $row): array
	{
		$items = json_decode((string) ($row['cart_json'] ?? ''), true);
		$row['items'] = is_array($items['items'] ?? null) ? $items['items'] : [];
		$row['subtotal_formatted'] = Tools::displayPrice((float) ($row['subtotal'] ?? 0));
		$row['can_remind'] = (int) ($row['id_user'] ?? 0) > 0
			&& trim((string) ($row['customer_email'] ?? '')) !== ''
			&& ($row['status'] ?? '') === 'abandoned';
		$row['status_label'] = self::statusLabel((string) ($row['status'] ?? ''));
		$row['status_class'] = self::statusClass((string) ($row['status'] ?? ''));

		return $row;
	}

	private static function statusLabel(string $status): string
	{
		switch ($status) {
			case 'converted':
				return 'Sipariş oluşturdu';
			case 'abandoned':
				return 'Terk edildi';
			default:
				return 'Aktif';
		}
	}

	private static function statusClass(string $status): string
	{
		switch ($status) {
			case 'converted':
				return 'success';
			case 'abandoned':
				return 'warning';
			default:
				return 'secondary';
		}
	}

	public static function sendReminder(int $idCart, string $message, string $couponCode = '', bool $createCoupon = false, array $couponData = []): array
	{
		self::ensureSchema();

		$row = DB::getRowSafe('abandoned_carts', 'id_cart = ?', [$idCart]);

		if (!$row) {
			return self::fail('Sepet kaydı bulunamadı');
		}

		$idUser = (int) ($row['id_user'] ?? 0);
		$email = trim((string) ($row['customer_email'] ?? ''));

		if ($idUser <= 0 || $email === '') {
			return self::fail('Hatırlatma yalnızca giriş yapmış müşterilere gönderilebilir');
		}

		if (($row['status'] ?? '') === 'converted') {
			return self::fail('Bu sepet zaten siparişe dönüştü');
		}

		$finalCoupon = trim($couponCode);

		if ($createCoupon) {
			$result = Coupon::createPersonal([
				'id_user' => $idUser,
				'prefix' => (string) ($couponData['prefix'] ?? 'SEP'),
				'discount_type' => (string) ($couponData['discount_type'] ?? 'percent'),
				'discount_value' => $couponData['discount_value'] ?? 10,
				'min_cart' => $couponData['min_cart'] ?? 0,
				'max_uses' => 1,
				'date_to' => date('Y-m-d H:i:s', strtotime('+7 days')),
			]);

			if (!$result['success']) {
				return $result;
			}

			$finalCoupon = (string) ($result['code'] ?? '');
		}

		$siteName = Settings::get('SITE_NAME') ?: 'FShop';
		$cartUrl = rtrim((string) ($GLOBALS['domain'] ?? ''), '/') . '/cart';
		$body = '<p>' . nl2br(htmlspecialchars($message !== '' ? $message : 'Sepetinizde ürünler bekliyor.', ENT_QUOTES, 'UTF-8')) . '</p>';
		$body .= '<p><a href="' . htmlspecialchars($cartUrl, ENT_QUOTES, 'UTF-8') . '">Sepetime dön</a></p>';

		if ($finalCoupon !== '') {
			$body .= '<p><strong>Kupon kodunuz:</strong> ' . htmlspecialchars($finalCoupon, ENT_QUOTES, 'UTF-8') . '</p>';
		}

		if (!Mail::send($email, $siteName . ' — Sepetiniz sizi bekliyor', $body)) {
			return self::fail(Mail::getLastError() ?: 'E-posta gönderilemedi');
		}

		DB::update('abandoned_carts', [
			'reminder_count' => (int) ($row['reminder_count'] ?? 0) + 1,
			'last_reminder_at' => date('Y-m-d H:i:s'),
			'coupon_code' => $finalCoupon,
		], 'id_cart = :where_id', ['where_id' => $idCart]);

		return self::ok('Hatırlatma e-postası gönderildi');
	}

	public static function runAutoReminders(): array
	{
		if (!self::isAutoRemindEnabled()) {
			return ['sent' => 0, 'skipped' => 0];
		}

		self::refreshAbandonedStatuses();
		$hours = self::getAutoRemindHours();
		$rows = DB::execute(
			'SELECT * FROM abandoned_carts
			 WHERE status = ?
			   AND id_user > 0
			   AND customer_email != \'\'
			   AND item_count > 0
			   AND date_upd <= DATE_SUB(NOW(), INTERVAL ? HOUR)
			   AND (last_reminder_at IS NULL OR last_reminder_at <= DATE_SUB(NOW(), INTERVAL ? HOUR))',
			['abandoned', $hours, $hours]
		) ?: [];

		$sent = 0;
		$skipped = 0;
		$defaultMessage = Settings::get('ABANDONED_CART_AUTO_MESSAGE')
			?: 'Sepetinizde bıraktığınız ürünler hâlâ sizi bekliyor. Alışverişinizi tamamlamak ister misiniz?';

		foreach ($rows as $row) {
			$result = self::sendReminder((int) $row['id_cart'], $defaultMessage);

			if ($result['success']) {
				++$sent;
			} else {
				++$skipped;
			}
		}

		return ['sent' => $sent, 'skipped' => $skipped];
	}

	private static function findOpenRow(string $sessionId, int $idUser): ?array
	{
		if ($idUser > 0) {
			$rows = DB::execute(
				'SELECT * FROM abandoned_carts
				 WHERE id_user = ? AND status IN (?, ?)
				 ORDER BY date_upd DESC LIMIT 1',
				[$idUser, 'active', 'abandoned']
			);

			if (!empty($rows[0])) {
				return $rows[0];
			}
		}

		if ($sessionId === '') {
			return null;
		}

		$rows = DB::execute(
			'SELECT * FROM abandoned_carts
			 WHERE session_id = ? AND status IN (?, ?)
			 ORDER BY date_upd DESC LIMIT 1',
			[$sessionId, 'active', 'abandoned']
		);

		return !empty($rows[0]) ? $rows[0] : null;
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
