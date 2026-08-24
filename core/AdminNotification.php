<?php

class AdminNotification
{
	public static function ensureSchema(): void
	{
		$table = DB::execute("SHOW TABLES LIKE 'admin_notifications'");

		if (empty($table)) {
			DB::execute(
				"CREATE TABLE `admin_notifications` (
					`id_notification` int(11) NOT NULL AUTO_INCREMENT,
					`type` varchar(32) NOT NULL DEFAULT 'info',
					`title` varchar(255) NOT NULL DEFAULT '',
					`message` text NOT NULL,
					`link` varchar(255) NOT NULL DEFAULT '',
					`is_read` tinyint(1) NOT NULL DEFAULT 0,
					`date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (`id_notification`),
					KEY `is_read` (`is_read`),
					KEY `date_add` (`date_add`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		}
	}

	public static function add(string $title, string $message, string $link = '', string $type = 'info'): ?int
	{
		self::ensureSchema();

		$title = trim($title);
		$message = trim($message);

		if ($title === '') {
			return null;
		}

		$id = DB::insert('admin_notifications', [
			'type' => mb_substr($type, 0, 32),
			'title' => mb_substr($title, 0, 255),
			'message' => $message,
			'link' => mb_substr($link, 0, 255),
			'is_read' => 0,
		]);

		return $id ? (int) $id : null;
	}

	public static function countUnread(): int
	{
		self::ensureSchema();

		return (int) DB::getValue(
			'SELECT COUNT(*) FROM admin_notifications WHERE is_read = 0'
		);
	}

	public static function getList(int $limit = 50, bool $unreadOnly = false): array
	{
		self::ensureSchema();

		$where = $unreadOnly ? 'WHERE is_read = 0' : '';
		$rows = DB::execute(
			'SELECT * FROM admin_notifications ' . $where . ' ORDER BY id_notification DESC LIMIT ' . (int) $limit
		) ?: [];

		foreach ($rows as &$row) {
			$row['date_formatted'] = Tools::formatDate3($row['date_add']);
			$row['is_read'] = (int) $row['is_read'];
			$row['title'] = self::localizeTitle((string) ($row['title'] ?? ''));
			$row['message'] = self::localizeMessage((string) ($row['message'] ?? ''));
		}
		unset($row);

		return $rows;
	}

	private static function translate(string $text): string
	{
		if (!class_exists('AdminLang', false)) {
			require_once dirname(__FILE__) . '/AdminLang.php';
		}

		return AdminLang::translate($text);
	}

	private static function localizeTitle(string $title): string
	{
		$legacy = [
			'Yeni iptal talebi' => 'New cancel request',
			'Yeni iade talebi' => 'New return request',
			'Yeni iletişim mesajı' => 'New contact message',
			'Sipariş sorusu' => 'Order question',
			'Ürün stoku bitti' => 'Product out of stock',
		];

		if (isset($legacy[$title])) {
			$title = $legacy[$title];
		}

		return self::translate($title);
	}

	private static function localizeMessage(string $message): string
	{
		if ($message === '') {
			return '';
		}

		$patterns = [
			'/^Sipariş #(.+) için iptal talebi oluşturuldu\.$/u' => 'Cancel request created for order #%s.',
			'/^Sipariş #(.+) için iade talebi oluşturuldu\.$/u' => 'Return request created for order #%s.',
			'/^Cancel request created for order #(.+)\.$/u' => 'Cancel request created for order #%s.',
			'/^Return request created for order #(.+)\.$/u' => 'Return request created for order #%s.',
			'/^(.+) stokta kalmadı\.$/u' => '%s is out of stock.',
			'/^(.+) is out of stock\.$/u' => '%s is out of stock.',
		];

		foreach ($patterns as $pattern => $templateKey) {
			if (preg_match($pattern, $message, $matches)) {
				return sprintf(self::translate($templateKey), $matches[1]);
			}
		}

		if (preg_match('/^(.+) — (Genel|General)$/u', $message, $matches)) {
			return $matches[1] . ' — ' . self::translate('General');
		}

		return self::translate($message);
	}

	public static function markRead(int $idNotification): bool
	{
		self::ensureSchema();

		$updated = DB::update(
			'admin_notifications',
			['is_read' => 1],
			'id_notification = :id_notification',
			['id_notification' => $idNotification]
		);

		return $updated !== false && $updated > 0;
	}

	public static function markAllRead(): void
	{
		self::ensureSchema();

		DB::update(
			'admin_notifications',
			['is_read' => 1],
			'is_read = 0',
			[]
		);
	}
}
