<?php

class CustomerNotifyService
{
	public static function ensureSchema(): void
	{
		$table = DB::execute("SHOW TABLES LIKE 'customer_notify_broadcasts'");

		if (!empty($table)) {
			return;
		}

		$path = dirname(__DIR__) . '/install.sql';

		if (is_file($path)) {
			$sql = (string) file_get_contents($path);
			$statements = preg_split('/;\s*\n/', $sql) ?: [];

			foreach ($statements as $statement) {
				$statement = trim($statement);

				if ($statement !== '') {
					DB::execute($statement);
				}
			}
		}
	}

	/**
	 * @param array<int, int|string> $selectedUserIds
	 * @return array{success: bool, message: string, sent?: int, id_broadcast?: int}
	 */
	public static function sendBroadcast(array $data): array
	{
		self::ensureSchema();

		$title = trim((string) ($data['title'] ?? ''));
		$message = trim((string) ($data['message'] ?? ''));
		$link = self::normalizeLink((string) ($data['link'] ?? ''));
		$scope = (string) ($data['scope'] ?? 'all');
		$sendEmail = !empty($data['send_email']);
		$selectedUserIds = self::normalizeUserIds($data['user_ids'] ?? []);

		if ($title === '') {
			return self::fail('Başlık zorunludur');
		}

		if ($message === '') {
			return self::fail('Mesaj zorunludur');
		}

		if ($scope === 'selected' && $selectedUserIds === []) {
			return self::fail('En az bir müşteri seçin');
		}

		$userIds = $scope === 'selected'
			? $selectedUserIds
			: self::getAllActiveUserIds();

		if ($userIds === []) {
			return self::fail('Gönderilecek müşteri bulunamadı');
		}

		$idBroadcast = (int) DB::insert('customer_notify_broadcasts', [
			'title' => mb_substr($title, 0, 255),
			'message' => $message,
			'link' => mb_substr($link, 0, 512),
			'scope' => $scope === 'selected' ? 'selected' : 'all',
			'recipient_count' => 0,
			'send_email' => $sendEmail ? 1 : 0,
			'selected_users_json' => $scope === 'selected' ? json_encode($selectedUserIds, JSON_UNESCAPED_UNICODE) : null,
			'created_by' => class_exists('Admin', false) ? Admin::getId() : 0,
		]);

		if ($idBroadcast <= 0) {
			return self::fail('Gönderim kaydı oluşturulamadı');
		}

		$sent = 0;

		foreach ($userIds as $idUser) {
			if (self::deliverToUser((int) $idUser, $title, $message, $link, $sendEmail)) {
				$sent++;
			}
		}

		DB::update(
			'customer_notify_broadcasts',
			['recipient_count' => $sent],
			'id_broadcast = :id_broadcast',
			['id_broadcast' => $idBroadcast]
		);

		return [
			'success' => true,
			'message' => $sent . ' müşteriye bildirim gönderildi',
			'sent' => $sent,
			'id_broadcast' => $idBroadcast,
		];
	}

	public static function deliverToUser(int $idUser, string $title, string $message, string $link, bool $sendEmail): bool
	{
		if ($idUser <= 0) {
			return false;
		}

		$idNotification = Notification::create($idUser, 'admin_broadcast', $title, $message, '');

		if ($idNotification === null) {
			return false;
		}

		$finalLink = $link !== '' ? $link : ('customer-notification?id=' . $idNotification);

		DB::update(
			'user_notifications',
			['link' => mb_substr($finalLink, 0, 255)],
			'id_notification = :id_notification',
			['id_notification' => $idNotification]
		);

		if ($sendEmail) {
			$user = DB::getRowSafe('users', 'id_user = ?', [$idUser]);
			$email = trim((string) ($user['email'] ?? ''));

			if ($email !== '') {
				global $domain;
				$body = '<p>' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</p>';
				$body .= '<p><a href="' . htmlspecialchars(rtrim((string) $domain, '/') . '/' . ltrim($finalLink, '/'), ENT_QUOTES, 'UTF-8') . '">Detayları görüntüle</a></p>';
				Mail::send($email, $title, $body);
			}
		}

		Notification::dispatchWebPush($idUser, $title, $message, $finalLink, [
			'type' => 'admin_broadcast',
		]);

		return true;
	}

	/** @return list<int> */
	public static function getAllActiveUserIds(): array
	{
		$rows = DB::execute('SELECT id_user FROM users WHERE active = 1 ORDER BY id_user ASC') ?: [];
		$ids = [];

		foreach ($rows as $row) {
			$id = (int) ($row['id_user'] ?? 0);

			if ($id > 0) {
				$ids[] = $id;
			}
		}

		return $ids;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function searchCustomers(string $query, int $limit = 20): array
	{
		$query = trim($query);

		if ($query === '') {
			return [];
		}

		$like = '%' . $query . '%';
		$rows = DB::execute(
			'SELECT id_user, user_full_name, email, phone
			 FROM users
			 WHERE active = 1
			   AND (user_full_name LIKE ? OR email LIKE ? OR phone LIKE ?)
			 ORDER BY user_full_name ASC
			 LIMIT ' . max(1, min(50, $limit)),
			[$like, $like, $like]
		) ?: [];

		$items = [];

		foreach ($rows as $row) {
			$items[] = [
				'id_user' => (int) ($row['id_user'] ?? 0),
				'full_name' => (string) ($row['user_full_name'] ?? ''),
				'email' => (string) ($row['email'] ?? ''),
				'phone' => (string) ($row['phone'] ?? ''),
				'label' => trim((string) ($row['user_full_name'] ?? '')) . ' — ' . (string) ($row['phone'] ?? ''),
			];
		}

		return $items;
	}

	/** @return list<array<string, mixed>> */
	public static function getRecentBroadcasts(int $limit = 30): array
	{
		self::ensureSchema();

		return DB::execute(
			'SELECT * FROM customer_notify_broadcasts ORDER BY id_broadcast DESC LIMIT ' . max(1, min(100, $limit))
		) ?: [];
	}

	public static function countActiveCustomers(): int
	{
		return (int) DB::getValue('SELECT COUNT(*) FROM users WHERE active = 1');
	}

	public static function normalizeLink(string $link): string
	{
		$link = trim($link);

		if ($link === '') {
			return '';
		}

		if (preg_match('#^https?://#i', $link)) {
			return $link;
		}

		return ltrim($link, '/');
	}

	/** @param mixed $raw */
	private static function normalizeUserIds($raw): array
	{
		if (!is_array($raw)) {
			return [];
		}

		$ids = [];

		foreach ($raw as $value) {
			$id = (int) $value;

			if ($id > 0) {
				$ids[$id] = $id;
			}
		}

		return array_values($ids);
	}

	/** @return array{success: bool, message: string} */
	private static function fail(string $message): array
	{
		return ['success' => false, 'message' => $message];
	}
}
