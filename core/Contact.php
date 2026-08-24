<?php

class Contact
{
	private static bool $schemaReady = false;

	public static function ensureSchema(): void
	{
		if (self::$schemaReady) {
			return;
		}

		self::$schemaReady = true;

		$table = DB::execute("SHOW TABLES LIKE 'contact_messages'");

		if (empty($table)) {
			DB::execute(
				"CREATE TABLE `contact_messages` (
					`id_message` int(11) NOT NULL AUTO_INCREMENT,
					`id_user` int(11) NOT NULL DEFAULT 0,
					`id_order` int(11) NOT NULL DEFAULT 0,
					`full_name` varchar(128) NOT NULL,
					`email` varchar(128) NOT NULL,
					`phone` varchar(20) NOT NULL DEFAULT '',
					`subject` varchar(128) NOT NULL DEFAULT '',
					`message` text NOT NULL,
					`attachment_file` varchar(255) NOT NULL DEFAULT '',
					`ip_address` varchar(45) NOT NULL DEFAULT '',
					`is_read` tinyint(1) NOT NULL DEFAULT 0,
					`date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (`id_message`),
					KEY `id_user` (`id_user`),
					KEY `id_order` (`id_order`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		} else {
			$orderCol = DB::execute("SHOW COLUMNS FROM `contact_messages` LIKE 'id_order'");

			if (empty($orderCol)) {
				DB::execute(
					"ALTER TABLE `contact_messages`
					 ADD COLUMN `id_order` int(11) NOT NULL DEFAULT 0 AFTER `id_user`,
					 ADD KEY `id_order` (`id_order`)"
				);
			}
		}

		$attachCol = DB::execute("SHOW COLUMNS FROM `contact_messages` LIKE 'attachment_file'");

		if (empty($attachCol)) {
			DB::execute(
				"ALTER TABLE `contact_messages`
				 ADD COLUMN `attachment_file` varchar(255) NOT NULL DEFAULT '' AFTER `message`"
			);
		}

		self::ensureAttachmentDir();

		$replies = DB::execute("SHOW TABLES LIKE 'contact_replies'");

		if (empty($replies)) {
			DB::execute(
				"CREATE TABLE `contact_replies` (
					`id_reply` int(11) NOT NULL AUTO_INCREMENT,
					`id_message` int(11) NOT NULL,
					`id_admin` int(11) NOT NULL DEFAULT 0,
					`message` text NOT NULL,
					`date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (`id_reply`),
					KEY `id_message` (`id_message`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		}
	}

	public static function submit(array $data): array
	{
		if (!empty($data['website'])) {
			return self::ok(self::t('Your message has been received. We will get back to you soon.'));
		}

		$name = trim((string) ($data['full_name'] ?? ''));
		$email = trim((string) ($data['email'] ?? ''));
		$phone = trim((string) ($data['phone'] ?? ''));
		$subject = trim((string) ($data['subject'] ?? ''));
		$message = trim((string) ($data['message'] ?? ''));

		if (!Validate::isName($name)) {
			return self::fail(self::t('Please enter a valid full name'));
		}

		if (!Validate::isEmail($email)) {
			return self::fail(self::t('Please enter a valid email address'));
		}

		if ($phone !== '' && !Validate::isPhoneNumber($phone)) {
			return self::fail(self::t('Please enter a valid phone number'));
		}

		if ($subject !== '' && !Validate::isGenericName($subject)) {
			return self::fail(self::t('Subject contains invalid characters'));
		}

		$messageCheck = self::validateMessage($message);

		if (!$messageCheck['success']) {
			return $messageCheck;
		}

		$idUser = Customer::isLoggedIn() ? Customer::getId() : 0;
		$ip = $_SERVER['REMOTE_ADDR'] ?? '';

		if (self::isRateLimited($ip, $email)) {
			return self::fail(self::t('You are sending messages too frequently, please wait'));
		}

		$id = self::createMessage([
			'id_user' => $idUser,
			'id_order' => 0,
			'full_name' => $name,
			'email' => $email,
			'phone' => $phone,
			'subject' => $subject !== '' ? $subject : self::t('General'),
			'message' => $message,
			'ip_address' => $ip,
		]);

		if (!$id) {
			return self::fail(self::t('Could not send message, please try again'));
		}

		self::notifyAdmin($name, $email, $subject, $message, 0, '');

		return self::ok(self::t('Your message has been received. We will get back to you soon.'));
	}

	public static function submitOrder(int $idOrder, int $idUser, string $message, array $extra = []): array
	{
		if (!empty($extra['website'])) {
			return self::ok(self::t('Your message has been received. We will get back to you soon.'));
		}

		if ($idUser <= 0) {
			return self::fail(self::t('Please log in'));
		}

		$order = Order::getByIdForUser($idOrder, $idUser);

		if (!$order) {
			return self::fail(self::t('Order not found'));
		}

		$messageCheck = self::validateMessage($message);

		if (!$messageCheck['success']) {
			return $messageCheck;
		}

		$user = Customer::getCurrent();
		$name = trim((string) ($order['customer_name'] ?? ($user['user_full_name'] ?? '')));
		$email = trim((string) ($order['customer_email'] ?? ($user['email'] ?? '')));
		$phone = trim((string) ($order['customer_phone'] ?? ($user['phone'] ?? '')));

		if ($name === '') {
			$name = trim((string) ($user['user_full_name'] ?? ''));
		}

		if ($email === '' || !Validate::isEmail($email)) {
			$email = trim((string) ($user['email'] ?? ''));
		}

		if ($email === '' || !Validate::isEmail($email)) {
			return self::fail(self::t('Please enter a valid email address'));
		}

		if (!Validate::isName($name)) {
			return self::fail(self::t('Please enter a valid full name'));
		}

		$ip = $_SERVER['REMOTE_ADDR'] ?? '';

		if (self::isRateLimited($ip, $email)) {
			return self::fail(self::t('You are sending messages too frequently, please wait'));
		}

		$attachmentFile = '';
		$file = $extra['attachment'] ?? null;

		if (is_array($file) && !empty($file['tmp_name'])) {
			$stored = self::storeAttachment($file);

			if (!$stored['success']) {
				return $stored;
			}

			$attachmentFile = (string) ($stored['filename'] ?? '');
		}

		$reference = (string) ($order['reference'] ?? '');
		$subject = 'Sipariş #' . $reference;

		$id = self::createMessage([
			'id_user' => $idUser,
			'id_order' => $idOrder,
			'full_name' => $name,
			'email' => $email,
			'phone' => $phone,
			'subject' => $subject,
			'message' => trim($message),
			'attachment_file' => $attachmentFile,
			'ip_address' => $ip,
		]);

		if (!$id) {
			return self::fail(self::t('Could not send message, please try again'));
		}

		self::notifyAdmin($name, $email, $subject, trim($message), $idOrder, $reference);

		return self::ok(self::t('Your message has been received. We will get back to you soon.'));
	}

	public static function replyFromAdmin(int $idMessage, string $replyMessage, int $idAdmin = 0): array
	{
		$replyMessage = trim($replyMessage);

		if (Tools::strlen($replyMessage) < 5) {
			return self::fail(self::t('The response must be at least 5 characters long'));
		}

		if (!Validate::isCleanHtml($replyMessage)) {
			return self::fail(self::t('Invalid message'));
		}

		$message = self::getById($idMessage);

		if (!$message) {
			return self::fail(self::t('Message not found'));
		}

		if ($idAdmin <= 0) {
			$idAdmin = Admin::getId();
		}

		$idReply = DB::insert('contact_replies', [
			'id_message' => $idMessage,
			'id_admin' => $idAdmin,
			'message' => $replyMessage,
		]);

		if (!$idReply) {
			return self::fail(self::t('The response could not be saved'));
		}

		$idUser = (int) ($message['id_user'] ?? 0);
		$idOrder = (int) ($message['id_order'] ?? 0);
		$reference = '';

		if ($idOrder > 0) {
			$reference = (string) DB::getValue('SELECT reference FROM orders WHERE id_order = ? LIMIT 1', [$idOrder]);
		}

		if ($idUser > 0) {
			Notification::contactReply($idUser, $reference, $idOrder, $idMessage, $replyMessage);
		} else {
			self::notifyCustomerByEmail(
				(string) $message['email'],
				$reference !== '' ? self::t('Order #') . $reference . ' — '.self::t('your response') : self::t('Reply to your message'),
				$replyMessage,
				$idOrder
			);
		}

		return self::ok(self::t('Message sent successfully'));
	}

	public static function getOrderThread(int $idOrder, int $idUser): array
	{
		if ($idOrder <= 0 || $idUser <= 0) {
			return [];
		}

		$rows = DB::execute(
			'SELECT * FROM contact_messages
			 WHERE id_order = ? AND id_user = ?
			 ORDER BY date_add ASC',
			[$idOrder, $idUser]
		) ?: [];

		return self::enrichWithReplies($rows);
	}

	public static function getByIdWithReplies(int $id): ?array
	{
		$row = self::getById($id);

		if (!$row) {
			return null;
		}

		$enriched = self::enrichWithReplies([$row]);

		return $enriched[0] ?? null;
	}

	public static function getReplies(int $idMessage): array
	{
		$rows = DB::execute(
			'SELECT cr.*, a.full_name AS admin_name
			 FROM contact_replies cr
			 LEFT JOIN admins a ON a.id_admin = cr.id_admin
			 WHERE cr.id_message = ?
			 ORDER BY cr.date_add ASC',
			[$idMessage]
		) ?: [];

		foreach ($rows as &$row) {
			$row['date_formatted'] = Tools::formatDate3($row['date_add']);
		}
		unset($row);

		return $rows;
	}

	private static function enrichWithReplies(array $rows): array
	{
		foreach ($rows as &$row) {
			$row['date_formatted'] = Tools::formatDate3($row['date_add']);
			$row['attachment_file'] = (string) ($row['attachment_file'] ?? '');
			$row['attachment_url'] = self::getAttachmentUrl($row['attachment_file']);
			$row['replies'] = self::getReplies((int) $row['id_message']);
		}
		unset($row);

		return $rows;
	}

	private static function createMessage(array $data): int
	{
		self::ensureSchema();

		$id = DB::insert('contact_messages', [
			'id_user' => (int) ($data['id_user'] ?? 0),
			'id_order' => (int) ($data['id_order'] ?? 0),
			'full_name' => (string) ($data['full_name'] ?? ''),
			'email' => (string) ($data['email'] ?? ''),
			'phone' => (string) ($data['phone'] ?? ''),
			'subject' => (string) ($data['subject'] ?? ''),
			'message' => (string) ($data['message'] ?? ''),
			'attachment_file' => (string) ($data['attachment_file'] ?? ''),
			'ip_address' => (string) ($data['ip_address'] ?? ''),
		]);

		return $id ? (int) $id : 0;
	}

	private static function ensureAttachmentDir(): void
	{
		$dir = self::attachmentDir();

		if (!is_dir($dir)) {
			@mkdir($dir, 0755, true);
		}

		$htaccess = $dir . '/.htaccess';

		if (!is_file($htaccess)) {
			@file_put_contents(
				$htaccess,
				"<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n"
				. "<IfModule !mod_authz_core.c>\n\tOrder deny,allow\n\tDeny from all\n</IfModule>\n"
			);
		}

		$index = $dir . '/index.php';

		if (!is_file($index)) {
			@file_put_contents($index, "<?php\nhttp_response_code(403);\n");
		}
	}

	public static function attachmentDir(): string
	{
		return dirname(__DIR__) . '/img/contact';
	}

	public static function getAttachmentUrl(string $filename): string
	{
		$filename = trim($filename);

		if ($filename === '') {
			return '';
		}

		global $domain;

		return rtrim($domain, '/') . '/api/contact-file.php?file=' . rawurlencode($filename);
	}

	public static function isAllowedAttachmentFilename(string $filename): bool
	{
		return (bool) preg_match('/^contact-[a-f0-9]+\.(jpg|jpeg|png|webp|pdf)$/i', $filename);
	}

	public static function canAccessAttachment(string $filename, int $idUser, bool $isAdmin): bool
	{
		if (!self::isAllowedAttachmentFilename($filename)) {
			return false;
		}

		if ($isAdmin) {
			return true;
		}

		if ($idUser <= 0) {
			return false;
		}

		$owner = (int) DB::getValue(
			'SELECT id_user FROM contact_messages WHERE attachment_file = ? LIMIT 1',
			[$filename]
		);

		return $owner === $idUser;
	}

	public static function serveAttachment(string $filename): void
	{
		if (!self::isAllowedAttachmentFilename($filename)) {
			http_response_code(404);
			exit;
		}

		$path = self::attachmentDir() . '/' . $filename;

		if (!is_file($path)) {
			http_response_code(404);
			exit;
		}

		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		$types = [
			'jpg' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'png' => 'image/png',
			'webp' => 'image/webp',
			'pdf' => 'application/pdf',
		];

		header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
		header('Content-Length: ' . (string) filesize($path));
		header('Content-Disposition: inline; filename="' . $filename . '"');
		header('X-Content-Type-Options: nosniff');
		readfile($path);
		exit;
	}

	/**
	 * @param array<string, mixed> $file
	 * @return array{success:bool,message?:string,filename?:string}
	 */
	private static function storeAttachment(array $file): array
	{
		self::ensureAttachmentDir();

		if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
			return ['success' => true, 'filename' => ''];
		}

		if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
			return self::fail(self::t('Could not upload attachment'));
		}

		if ((int) ($file['size'] ?? 0) > 5242880) {
			return self::fail(self::t('Attachment too large'));
		}

		$tmp = (string) ($file['tmp_name'] ?? '');

		if ($tmp === '' || !is_uploaded_file($tmp)) {
			return self::fail(self::t('Could not upload attachment'));
		}

		$binary = file_get_contents($tmp);

		if (!is_string($binary) || $binary === '') {
			return self::fail(self::t('Could not upload attachment'));
		}

		$ext = '';
		$info = @getimagesizefromstring($binary);

		if ($info && in_array((int) $info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
			$map = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
			$ext = $map[(int) $info[2]] ?? '';
		} elseif (strncmp($binary, '%PDF', 4) === 0) {
			$ext = 'pdf';
		}

		if ($ext === '') {
			return self::fail(self::t('Invalid attachment type'));
		}

		$filename = 'contact-' . bin2hex(random_bytes(12)) . '.' . $ext;
		$path = self::attachmentDir() . '/' . $filename;

		if (@file_put_contents($path, $binary) === false) {
			return self::fail(self::t('Could not upload attachment'));
		}

		return ['success' => true, 'filename' => $filename];
	}

	private static function validateMessage(string $message): array
	{
		$message = trim($message);

		if (Tools::strlen($message) < 10) {
			return self::fail(self::t('Message must be at least 10 characters'));
		}

		if (!Validate::isCleanHtml($message)) {
			return self::fail(self::t('Message contains invalid content'));
		}

		return self::ok('');
	}

	private static function isRateLimited(string $ip, string $email): bool
	{
		self::ensureSchema();

		$since = date('Y-m-d H:i:s', time() - 300);

		$count = (int) DB::getValue(
			'SELECT COUNT(*) FROM contact_messages
			WHERE date_add >= ? AND (ip_address = ? OR email = ?)',
			[$since, $ip, $email]
		);

		return $count >= 3;
	}

	private static function notifyAdmin(
		string $name,
		string $email,
		string $subject,
		string $message,
		int $idOrder = 0,
		string $orderReference = ''
	): void {
		$to = Settings::get('CONTACT_EMAIL');

		if ($to === '' || !Validate::isEmail($to)) {
			return;
		}

		$subjectLine = '[FShop] ' . ($subject !== '' ? $subject : 'Contact form');
		$bodyHtml = '<p><strong>Name:</strong> ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</p>'
			. '<p><strong>Email:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</p>'
			. '<p><strong>Subject:</strong> ' . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . '</p>';

		if ($idOrder > 0) {
			$bodyHtml .= '<p><strong>Order:</strong> #'
				. htmlspecialchars($orderReference !== '' ? $orderReference : (string) $idOrder, ENT_QUOTES, 'UTF-8')
				. '</p>';
		}

		$bodyHtml .= '<hr><p>' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</p>';

		Mail::send($to, $subjectLine, $bodyHtml);

		if (class_exists('AdminNotification', false) || file_exists(dirname(__FILE__) . '/AdminNotification.php')) {
			if (!class_exists('AdminNotification', false)) {
				require_once dirname(__FILE__) . '/AdminNotification.php';
			}

			$adminLink = $idOrder > 0
				? Admin::url('message') . '?order=' . $idOrder
				: Admin::url('messages');

			$title = $idOrder > 0 ? 'Order question' : 'New contact message';
			$summary = $name . ' — ' . ($subject !== '' ? $subject : 'General');

			AdminNotification::add($title, $summary, $adminLink, 'contact');
		}
	}

	private static function notifyCustomerByEmail(string $email, string $title, string $message, int $idOrder = 0): void
	{
		if ($email === '' || !Validate::isEmail($email)) {
			return;
		}

		global $domain;
		$body = '<p>' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</p>';

		if ($idOrder > 0) {
			$body .= '<p><a href="' . htmlspecialchars(rtrim($domain, '/') . '/my-account?order=' . $idOrder, ENT_QUOTES, 'UTF-8') . '">Sipariş detayını görüntüle</a></p>';
		}

		Mail::send($email, $title, $body);
	}

	private static function ok(string $message): array
	{
		return [
			'success' => true,
			'message' => $message,
		];
	}

	private static function fail(string $message): array
	{
		return [
			'success' => false,
			'message' => $message,
		];
	}

	private static function t(string $message): string
	{
		return function_exists('translate') ? translate($message) : $message;
	}

	public static function countUnread(): int
	{
		self::ensureSchema();

		return (int) DB::getValue('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0');
	}

	public static function countAdminThreads(?int $readFilter = null): int
	{
		self::ensureSchema();

		$having = '';

		if ($readFilter === 0) {
			$having = ' HAVING SUM(IF(cm.is_read = 0, 1, 0)) > 0';
		} elseif ($readFilter === 1) {
			$having = ' HAVING SUM(IF(cm.is_read = 0, 1, 0)) = 0';
		}

		$orderCount = (int) DB::getValue(
			'SELECT COUNT(*) FROM (
				SELECT cm.id_order
				FROM contact_messages cm
				WHERE cm.id_order > 0
				GROUP BY cm.id_order' . $having . '
			) grouped_orders'
		);

		$generalSql = 'SELECT COUNT(*) FROM contact_messages WHERE id_order = 0';
		$params = [];

		if ($readFilter === 0) {
			$generalSql .= ' AND is_read = 0';
		} elseif ($readFilter === 1) {
			$generalSql .= ' AND is_read = 1';
		}

		$generalCount = (int) DB::getValue($generalSql, $params);

		return $orderCount + $generalCount;
	}

	public static function getAdminThreadList(int $limit = 30, int $offset = 0, ?int $readFilter = null): array
	{
		self::ensureSchema();

		$threads = [];
		$having = '';

		if ($readFilter === 0) {
			$having = ' HAVING unread_count > 0';
		} elseif ($readFilter === 1) {
			$having = ' HAVING unread_count = 0';
		}

		$orderRows = DB::execute(
			'SELECT cm.id_order,
				MAX(o.reference) AS order_reference,
				COUNT(*) AS message_count,
				SUM(IF(cm.is_read = 0, 1, 0)) AS unread_count,
				(
					SELECT COUNT(*)
					FROM contact_replies cr
					INNER JOIN contact_messages cm2 ON cm2.id_message = cr.id_message
					WHERE cm2.id_order = cm.id_order
				) AS reply_count,
				MAX(cm.date_add) AS last_date,
				(
					SELECT cm3.id_message
					FROM contact_messages cm3
					WHERE cm3.id_order = cm.id_order
					ORDER BY cm3.date_add DESC, cm3.id_message DESC
					LIMIT 1
				) AS latest_message_id
			FROM contact_messages cm
			LEFT JOIN orders o ON o.id_order = cm.id_order
			WHERE cm.id_order > 0
			GROUP BY cm.id_order' . $having
		) ?: [];

		foreach ($orderRows as $row) {
			$latest = self::getById((int) $row['latest_message_id']);
			$threads[] = self::formatAdminThreadRow($row, $latest, true);
		}

		$generalSql = 'SELECT cm.*,
				1 AS message_count,
				IF(cm.is_read = 0, 1, 0) AS unread_count,
				(SELECT COUNT(*) FROM contact_replies cr WHERE cr.id_message = cm.id_message) AS reply_count,
				cm.date_add AS last_date,
				cm.id_message AS latest_message_id
			FROM contact_messages cm
			WHERE cm.id_order = 0';
		$params = [];

		if ($readFilter === 0) {
			$generalSql .= ' AND cm.is_read = 0';
		} elseif ($readFilter === 1) {
			$generalSql .= ' AND cm.is_read = 1';
		}

		$generalSql .= ' ORDER BY cm.date_add DESC';

		$generalRows = DB::execute($generalSql, $params) ?: [];

		foreach ($generalRows as $row) {
			$threads[] = self::formatAdminThreadRow($row, $row, false);
		}

		usort($threads, static function (array $a, array $b): int {
			return strcmp($b['last_date'], $a['last_date']);
		});

		return array_slice($threads, $offset, $limit);
	}

	public static function getAdminOrderThread(int $idOrder): ?array
	{
		if ($idOrder <= 0) {
			return null;
		}

		self::ensureSchema();

		$messages = DB::execute(
			'SELECT cm.*, o.reference AS order_reference
			 FROM contact_messages cm
			 LEFT JOIN orders o ON o.id_order = cm.id_order
			 WHERE cm.id_order = ?
			 ORDER BY cm.date_add ASC, cm.id_message ASC',
			[$idOrder]
		) ?: [];

		if ($messages === []) {
			return null;
		}

		$messages = self::enrichWithReplies($messages);
		$latest = $messages[count($messages) - 1];

		return [
			'is_order_thread' => true,
			'id_order' => $idOrder,
			'order_reference' => (string) ($latest['order_reference'] ?? ''),
			'full_name' => (string) ($latest['full_name'] ?? ''),
			'email' => (string) ($latest['email'] ?? ''),
			'phone' => (string) ($latest['phone'] ?? ''),
			'subject' => self::t('Order #') . ($latest['order_reference'] ?? $idOrder),
			'messages' => $messages,
			'timeline' => self::buildTimeline($messages),
			'message_count' => count($messages),
			'reply_count' => self::countRepliesForMessages($messages),
			'reply_to_message_id' => (int) $latest['id_message'],
		];
	}

	public static function getAdminGeneralThread(int $idMessage): ?array
	{
		$message = self::getByIdWithReplies($idMessage);

		if (!$message || (int) ($message['id_order'] ?? 0) > 0) {
			return null;
		}

		return [
			'is_order_thread' => false,
			'id_order' => 0,
			'order_reference' => '',
			'full_name' => (string) ($message['full_name'] ?? ''),
			'email' => (string) ($message['email'] ?? ''),
			'phone' => (string) ($message['phone'] ?? ''),
			'subject' => (string) ($message['subject'] ?? ''),
			'messages' => [$message],
			'timeline' => self::buildTimeline([$message]),
			'message_count' => 1,
			'reply_count' => count($message['replies'] ?? []),
			'reply_to_message_id' => $idMessage,
		];
	}

	public static function markOrderThreadRead(int $idOrder): void
	{
		if ($idOrder <= 0) {
			return;
		}

		DB::execute(
			'UPDATE contact_messages SET is_read = 1 WHERE id_order = ? AND is_read = 0',
			[$idOrder]
		);
	}

	public static function getAdminList(int $limit = 30, int $offset = 0, ?int $readFilter = null): array
	{
		self::ensureSchema();

		$sql = 'SELECT cm.*, o.reference AS order_reference,
			(SELECT COUNT(*) FROM contact_replies cr WHERE cr.id_message = cm.id_message) AS reply_count
			FROM contact_messages cm
			LEFT JOIN orders o ON o.id_order = cm.id_order
			WHERE 1=1';
		$params = [];

		if ($readFilter === 0) {
			$sql .= ' AND cm.is_read = 0';
		} elseif ($readFilter === 1) {
			$sql .= ' AND cm.is_read = 1';
		}

		$sql .= ' ORDER BY cm.date_add DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

		return DB::execute($sql, $params) ?: [];
	}

	public static function countAdmin(?int $readFilter = null): int
	{
		self::ensureSchema();

		$sql = 'SELECT COUNT(*) FROM contact_messages WHERE 1=1';
		$params = [];

		if ($readFilter === 0) {
			$sql .= ' AND is_read = 0';
		} elseif ($readFilter === 1) {
			$sql .= ' AND is_read = 1';
		}

		return (int) DB::getValue($sql, $params);
	}

	public static function getById(int $id): ?array
	{
		self::ensureSchema();

		$rows = DB::execute(
			'SELECT cm.*, o.reference AS order_reference
			 FROM contact_messages cm
			 LEFT JOIN orders o ON o.id_order = cm.id_order
			 WHERE cm.id_message = ?
			 LIMIT 1',
			[$id]
		);

		return !empty($rows[0]) ? $rows[0] : null;
	}

	public static function markRead(int $id): bool
	{
		return DB::update(
			'contact_messages',
			['is_read' => 1],
			'id_message = :id_message',
			['id_message' => $id]
		) !== false;
	}

	private static function formatAdminThreadRow(array $group, ?array $latest, bool $isOrderThread): array
	{
		$messageText = trim((string) ($latest['message'] ?? $group['message'] ?? ''));
		$preview = $messageText;

		if (mb_strlen($preview, 'UTF-8') > 120) {
			$preview = mb_substr($preview, 0, 120, 'UTF-8') . '…';
		}

		$unreadCount = (int) ($group['unread_count'] ?? 0);
		$replyCount = (int) ($group['reply_count'] ?? 0);

		return [
			'is_order_thread' => $isOrderThread,
			'id_order' => (int) ($group['id_order'] ?? 0),
			'id_message' => (int) ($group['latest_message_id'] ?? $group['id_message'] ?? 0),
			'order_reference' => (string) ($group['order_reference'] ?? ''),
			'message_count' => (int) ($group['message_count'] ?? 1),
			'unread_count' => $unreadCount,
			'reply_count' => $replyCount,
			'last_date' => (string) ($group['last_date'] ?? ''),
			'last_date_formatted' => Tools::formatDate3($group['last_date'] ?? ''),
			'full_name' => (string) ($latest['full_name'] ?? $group['full_name'] ?? ''),
			'email' => (string) ($latest['email'] ?? $group['email'] ?? ''),
			'subject' => (string) ($latest['subject'] ?? $group['subject'] ?? ''),
			'last_message_preview' => $preview,
			'status_label' => self::getThreadStatusLabel($unreadCount, $replyCount),
		];
	}

	private static function getThreadStatusLabel(int $unreadCount, int $replyCount): string
	{
		if ($unreadCount > 0) {
			return self::t('New');
		}

		if ($replyCount > 0) {
			return self::t('Answered');
		}

		return self::t('Read');
	}

	private static function buildTimeline(array $messages): array
	{
		$timeline = [];

		foreach ($messages as $msg) {
			$timeline[] = [
				'type' => 'customer',
				'id_message' => (int) $msg['id_message'],
				'author' => (string) $msg['full_name'],
				'message' => (string) $msg['message'],
				'attachment_file' => (string) ($msg['attachment_file'] ?? ''),
				'attachment_url' => (string) ($msg['attachment_url'] ?? self::getAttachmentUrl((string) ($msg['attachment_file'] ?? ''))),
				'date_formatted' => $msg['date_formatted'],
				'date_add' => (string) $msg['date_add'],
			];

			foreach ($msg['replies'] as $reply) {
				$timeline[] = [
					'type' => 'admin',
					'id_message' => (int) $msg['id_message'],
					'author' => (string) ($reply['admin_name'] ?: 'Admin'),
					'message' => (string) $reply['message'],
					'attachment_file' => '',
					'attachment_url' => '',
					'date_formatted' => $reply['date_formatted'],
					'date_add' => (string) $reply['date_add'],
				];
			}
		}

		usort($timeline, static function (array $a, array $b): int {
			return strcmp($a['date_add'], $b['date_add']);
		});

		return $timeline;
	}

	private static function countRepliesForMessages(array $messages): int
	{
		$count = 0;

		foreach ($messages as $message) {
			$count += count($message['replies'] ?? []);
		}

		return $count;
	}
}
