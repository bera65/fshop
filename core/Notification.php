<?php

class Notification
{
	public static function create(int $idUser, string $type, string $title, string $message, string $link = ''): ?int
	{
		if ($idUser <= 0) {
			return null;
		}

		$id = DB::insert('user_notifications', [
			'id_user' => $idUser,
			'type' => $type,
			'title' => $title,
			'message' => $message,
			'link' => $link,
			'is_read' => 0,
		]);

		return $id ? (int) $id : null;
	}

	public static function notifyUser(int $idUser, string $type, string $title, string $message, string $link = ''): void
	{
		self::create($idUser, $type, $title, $message, $link);

		$user = DB::getRowSafe('users', 'id_user = ?', [$idUser]);
		$email = trim((string) ($user['email'] ?? ''));

		if ($email !== '') {
			global $domain;
			$body = '<p>' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</p>';

			if ($link !== '') {
				$body .= '<p><a href="' . htmlspecialchars($domain . ltrim($link, '/'), ENT_QUOTES, 'UTF-8') . '">Detayları görüntüle</a></p>';
			}

			Mail::send($email, $title, $body);
		}

		self::dispatchWebPush($idUser, $title, $message, $link);
	}

	public static function dispatchWebPush(int $idUser, string $title, string $message, string $link = ''): void
	{
		if ($idUser <= 0 || !Module::isEnabled('mobil-app')) {
			return;
		}

		$servicePath = dirname(__DIR__) . '/modules/mobil-app/lib/WebPushService.php';

		if (!is_file($servicePath)) {
			return;
		}

		require_once $servicePath;

		if (!WebPushService::isAvailable()) {
			return;
		}

		WebPushService::sendToUser($idUser, $title, $message, self::buildNotificationUrl($link));
	}

	public static function welcome(int $idUser, string $fullName): void
	{
		$siteName = trim((string) Settings::get('SITE_NAME')) ?: 'Mağazamız';
		$title = 'Hoş geldiniz!';
		$message = 'Merhaba ' . $fullName . ",\n\n"
			. $siteName . "'a kayıt olduğunuz için teşekkür ederiz. Hesabınızdan siparişlerinizi takip edebilirsiniz.";

		self::notifyUser($idUser, 'welcome', $title, $message, 'my-account');
	}

	public static function getByIdForUser(int $idNotification, int $idUser): ?array
	{
		if ($idNotification <= 0 || $idUser <= 0) {
			return null;
		}

		$row = DB::getRow(
			'SELECT * FROM user_notifications WHERE id_notification = ? AND id_user = ? LIMIT 1',
			[$idNotification, $idUser]
		);

		if (!$row) {
			return null;
		}

		$row['date_formatted'] = Tools::formatDate3($row['date_add']);
		$row['is_read'] = (int) $row['is_read'];
		$row['url'] = self::buildNotificationUrl((string) ($row['link'] ?? ''));

		return $row;
	}

	public static function buildNotificationUrl(string $link): string
	{
		global $domain;
		$link = trim($link);
		$base = rtrim((string) $domain, '/');

		if ($link === '') {
			return $base . '/my-account#notifications';
		}

		if (preg_match('#^https?://#i', $link)) {
			return $link;
		}

		return $base . '/' . ltrim($link, '/');
	}

	public static function orderPlaced(int $idUser, string $reference, float $total, int $idOrder = 0): void
	{
		$title = 'Siparişiniz alındı';
		$message = 'Siparişiniz başarıyla oluşturuldu.' . "\n\n"
			. 'Sipariş No: ' . $reference . "\n"
			. 'Toplam: ' . Tools::displayPrice($total);

		$link = $idOrder > 0 ? 'my-account?order=' . $idOrder : 'my-account';
		self::notifyUser($idUser, 'order_placed', $title, $message, $link);
	}

	public static function orderStatusChanged(array $order, int $oldStatus, int $newStatus): void
	{
		$idUser = (int) ($order['id_user'] ?? 0);
		$reference = (string) ($order['reference'] ?? '');
		$payment = (string) ($order['payment_method'] ?? '');

		if ($idUser <= 0 || $oldStatus === $newStatus) {
			return;
		}

		$title = 'Sipariş durumu güncellendi';
		$message = self::buildStatusMessage($reference, $oldStatus, $newStatus, $payment);
		$idOrder = (int) ($order['id_order'] ?? 0);
		$link = $idOrder > 0 ? 'my-account?order=' . $idOrder : 'my-account';

		self::notifyUser($idUser, 'order_status', $title, $message, $link);
	}

	private static function buildStatusMessage(string $reference, int $oldStatus, int $newStatus, string $payment): string
	{
		$refLine = 'Sipariş No: ' . $reference . "\n\n";

		if ($newStatus === Order::STATUS_PROCESSING && $oldStatus === Order::STATUS_PENDING) {
			if ($payment === 'bank_transfer') {
				return $refLine . 'Havale ödemeniz onaylandı. Siparişiniz hazırlanmaya başlandı.';
			}

			return $refLine . 'Siparişiniz onaylandı ve hazırlanmaya başlandı.';
		}

		if ($newStatus === Order::STATUS_SHIPPED) {
			return $refLine . 'Siparişiniz kargoya verildi.';
		}

		if ($newStatus === Order::STATUS_DELIVERED) {
			return $refLine . 'Siparişiniz teslim edildi. Bizi tercih ettiğiniz için teşekkürler!';
		}

		if ($newStatus === Order::STATUS_CANCELLED) {
			return $refLine . 'Siparişiniz iptal edildi.';
		}

		if ($newStatus === Order::STATUS_RETURN_PENDING) {
			return $refLine . 'Siparişiniz iade bekleniyor olarak işaretlendi.';
		}

		if ($newStatus === Order::STATUS_RETURNED) {
			return $refLine . 'Siparişiniz iade edildi olarak işaretlendi.';
		}

		return $refLine . 'Yeni durum: ' . Order::getStatusLabel($newStatus);
	}

	public static function getUnreadCount(int $idUser): int
	{
		return (int) DB::getValue(
			'SELECT COUNT(*) FROM user_notifications WHERE id_user = ? AND is_read = 0',
			[$idUser]
		);
	}

	public static function getListForUser(int $idUser, int $limit = 50): array
	{
		$rows = DB::execute(
			'SELECT * FROM user_notifications WHERE id_user = ? ORDER BY date_add DESC LIMIT ' . (int) $limit,
			[$idUser]
		) ?: [];

		foreach ($rows as &$row) {
			$row['date_formatted'] = Tools::formatDate3($row['date_add']);
			$row['is_read'] = (int) $row['is_read'];
			$row['url'] = self::buildNotificationUrl((string) ($row['link'] ?? ''));
		}
		unset($row);

		return $rows;
	}

	public static function markRead(int $idNotification, int $idUser): bool
	{
		$updated = DB::update(
			'user_notifications',
			['is_read' => 1],
			'id_notification = :id_notification AND id_user = :id_user',
			['id_notification' => $idNotification, 'id_user' => $idUser]
		);

		return $updated !== false && $updated > 0;
	}

	public static function markAllRead(int $idUser): void
	{
		DB::update(
			'user_notifications',
			['is_read' => 1],
			'id_user = :id_user AND is_read = 0',
			['id_user' => $idUser]
		);
	}

	public static function returnRequestSubmitted(int $idUser, string $reference, int $idReturn): void
	{
		$title = 'İade talebiniz alındı';
		$message = 'Sipariş #' . $reference . " için iade talebiniz başarıyla oluşturuldu.\n\n"
			. 'Talebiniz incelendikten sonra size bilgi verilecektir.';

		self::notifyUser($idUser, 'return_submitted', $title, $message, 'returns?id=' . $idReturn);
	}

	public static function returnRequestApproved(int $idUser, string $reference, int $idReturn, string $adminMessage): void
	{
		$title = 'İade talebiniz onaylandı';
		$message = 'Sipariş #' . $reference . " için iade talebiniz onaylandı.\n\n"
			. "Mağaza mesajı:\n" . $adminMessage . "\n\n"
			. 'Sipariş durumu: İade bekleniyor.';

		self::notifyUser($idUser, 'return_approved', $title, $message, 'returns?id=' . $idReturn);
	}

	public static function returnRequestRejected(int $idUser, string $reference, int $idReturn, string $adminMessage): void
	{
		$title = 'İade talebiniz reddedildi';
		$message = 'Sipariş #' . $reference . " için iade talebiniz reddedildi.\n\n"
			. "Mağaza mesajı:\n" . $adminMessage;

		self::notifyUser($idUser, 'return_rejected', $title, $message, 'returns?id=' . $idReturn);
	}

	public static function returnRequestCompleted(int $idUser, string $reference, int $idReturn, string $adminMessage, string $receiptFile = ''): void
	{
		$title = 'İade işlemi tamamlandı';
		$message = 'Sipariş #' . $reference . " için iade işlemi tamamlandı.";

		if (trim($adminMessage) !== '') {
			$message .= "\n\nMağaza mesajı:\n" . $adminMessage;
		}

		if ($receiptFile !== '') {
			$message .= "\n\nİade dekontunuz hesabınızdaki iade detayında görüntülenebilir.";
		}

		self::notifyUser($idUser, 'return_completed', $title, $message, 'returns?id=' . $idReturn);
	}

	public static function cancelRequestSubmitted(int $idUser, string $reference, int $idOrder): void
	{
		$title = 'İptal talebiniz alındı';
		$message = 'Sipariş #' . $reference . " için iptal talebiniz oluşturuldu.\n\n"
			. 'Talebiniz incelendikten sonra size bilgi verilecektir.';

		self::notifyUser($idUser, 'cancel_submitted', $title, $message, 'my-account?order=' . $idOrder);
	}

	public static function cancelRequestApproved(int $idUser, string $reference, int $idCancel, string $adminMessage, string $receiptFile = ''): void
	{
		$title = 'İptal talebiniz onaylandı';
		$message = 'Sipariş #' . $reference . " iptal edildi.\n\nMağaza mesajı:\n" . $adminMessage;

		if ($receiptFile !== '') {
			$message .= "\n\nİptal dekontunuz hesabınızdaki sipariş detayında görüntülenebilir.";
		}

		self::notifyUser($idUser, 'cancel_approved', $title, $message, 'my-account');
	}

	public static function cancelRequestRejected(int $idUser, string $reference, int $idCancel, string $adminMessage): void
	{
		$title = 'İptal talebiniz reddedildi';
		$message = 'Sipariş #' . $reference . " için iptal talebiniz reddedildi.\n\nMağaza mesajı:\n" . $adminMessage;

		self::notifyUser($idUser, 'cancel_rejected', $title, $message, 'my-account');
	}

	public static function contactReply(int $idUser, string $reference, int $idOrder, int $idMessage, string $adminReply): void
	{
		$title = $reference !== ''
			? 'Sipariş #' . $reference . ' — yanıtınız'
			: 'Mesajınıza yanıt';
		$message = $reference !== ''
			? 'Sipariş #' . $reference . " ile ilgili sorunuza yanıt verildi:\n\n" . $adminReply
			: "Mesajınıza yanıt verildi:\n\n" . $adminReply;

		$link = $idOrder > 0
			? 'my-account?order=' . $idOrder . '#order-contact'
			: 'my-account';

		self::notifyUser($idUser, 'contact_reply', $title, $message, $link);
	}
}
