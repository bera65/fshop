<?php

class Admin
{
	public static function login(string $email, string $password): array
	{
		$email = trim(strtolower($email));
		$identifier = RateLimit::loginIdentifier($email);

		if (RateLimit::isLimited(RateLimit::SCOPE_ADMIN_LOGIN, $identifier, 8, 900)) {
			return self::fail('Çok fazla başarısız giriş denemesi. Lütfen 15 dakika sonra tekrar deneyin.');
		}

		if (!Validate::isEmail($email)) {
			RateLimit::record(RateLimit::SCOPE_ADMIN_LOGIN, $identifier);

			return self::fail('E-posta veya şifre hatalı');
		}

		$row = DB::getRowSafe('admins', 'email = ? AND active = 1', [$email]);

		if (!$row || !password_verify($password, $row['password'])) {
			RateLimit::record(RateLimit::SCOPE_ADMIN_LOGIN, $identifier);

			return self::fail('E-posta veya şifre hatalı');
		}

		RateLimit::clear(RateLimit::SCOPE_ADMIN_LOGIN, $identifier);

		session_regenerate_id(true);
		$_SESSION['id_admin'] = (int) $row['id_admin'];

		return self::ok('Giriş başarılı');
	}

	public static function logout(): void
	{
		unset($_SESSION['id_admin']);
		session_regenerate_id(true);
	}

	public static function isLoggedIn(): bool
	{
		return !empty($_SESSION['id_admin']);
	}

	public static function getId(): int
	{
		return (int) ($_SESSION['id_admin'] ?? 0);
	}

	public static function getCurrent(): ?array
	{
		if (!self::isLoggedIn()) {
			return null;
		}

		$row = DB::getRowSafe('admins', 'id_admin = ? AND active = 1', [self::getId()]);

		if (!$row) {
			self::logout();

			return null;
		}

		unset($row['password']);

		return $row;
	}

	public static function requireLogin(): void
	{
		if (!self::isLoggedIn()) {
			header('Location: ' . self::url('login'));
			exit;
		}
	}

	/** Public admin path slug from env (filesystem folder stays `admin/`). */
	public static function uri(): string
	{
		$raw = trim((string) App::env('ADMIN_URI', 'admin'), "/ \t\n\r\0\x0B");
		$uri = preg_replace('/[^a-zA-Z0-9_-]/', '', $raw);

		return ($uri !== null && $uri !== '') ? $uri : 'admin';
	}

	public static function baseUrl(): string
	{
		global $adminUrl, $domain;

		if (!empty($adminUrl) && is_string($adminUrl)) {
			return rtrim($adminUrl, '/') . '/';
		}

		$domainBase = '';

		if (!empty($domain) && is_string($domain)) {
			$domainBase = rtrim($domain, '/');
		} elseif (class_exists('Settings', false)) {
			$domainBase = rtrim((string) Settings::get('DOMAIN'), '/');
		}

		return $domainBase . '/' . self::uri() . '/';
	}

	public static function url(string $path = ''): string
	{
		return self::baseUrl() . ltrim($path, '/');
	}

	/**
	 * Syncs public ADMIN_URI rewrite rules into root .htaccess.
	 * Physical directory remains `admin/`; only the public URL changes.
	 */
	public static function syncHtaccessRewrite(): bool
	{
		$htaccess = dirname(__DIR__) . '/.htaccess';

		if (!is_file($htaccess) || !is_writable($htaccess)) {
			return false;
		}

		$content = (string) file_get_contents($htaccess);
		$uri = self::uri();
		$quoted = preg_quote($uri, '/');
		$block = "# BEGIN FSHOP_ADMIN\n"
			. 'RewriteRule ^' . $quoted . '/?$ admin/index.php [L,QSA]' . "\n"
			. 'RewriteRule ^' . $quoted . '/([a-zA-Z0-9_-]+)/?$ admin/index.php?container=$1 [L,QSA]' . "\n";

		if ($uri !== 'admin') {
			// Sadece public /{base}/admin yolunu engelle — templates/admin/css gibi yolları değil.
			// THE_REQUEST kullan: internal rewrite (admin/index.php) ikinci geçişte yakalanmasın.
			$base = trim((string) App::env('REWRITE_BASE', '/'), '/');
			$adminPath = $base !== '' ? '/' . $base . '/admin' : '/admin';
			$block .= 'RewriteCond %{THE_REQUEST} ^[A-Z]{3,}\\s+' . preg_quote($adminPath, '/') . '(?:/|\\?|\\s) [NC]' . "\n";
			$block .= "RewriteRule ^ - [R=404,L]\n";
		}

		$block .= "# END FSHOP_ADMIN";

		$next = self::replaceHtaccessAdminBlock($content, $block);

		if ($next === null) {
			return false;
		}

		if ($next === $content) {
			return true;
		}

		return file_put_contents($htaccess, $next) !== false;
	}

	/** preg_replace $1 yemesin diye blok string ile doğrudan yer değiştirir. */
	private static function replaceHtaccessAdminBlock(string $content, string $block): ?string
	{
		$markerRe = '/# BEGIN FSHOP_ADMIN\r?\n.*?# END FSHOP_ADMIN/s';

		if (preg_match($markerRe, $content, $m, PREG_OFFSET_CAPTURE)) {
			$start = (int) $m[0][1];
			$len = strlen($m[0][0]);

			return substr($content, 0, $start) . $block . substr($content, $start + $len);
		}

		$legacyRe = '/# 5c\.\s*Admin paneli\r?\n'
			. 'RewriteRule \^admin\/\?\$ admin\/index\.php \[L,QSA\]\r?\n'
			. 'RewriteRule \^admin\/\(\[a-zA-Z0-9_-\]\+\)\/\?\$ admin\/index\.php\?container=\$1 \[L,QSA\]/';

		if (preg_match($legacyRe, $content, $m, PREG_OFFSET_CAPTURE)) {
			$start = (int) $m[0][1];
			$len = strlen($m[0][0]);

			return substr($content, 0, $start) . $block . substr($content, $start + $len);
		}

		if (!preg_match('/RewriteBase\s+.+\r?\n/', $content, $m, PREG_OFFSET_CAPTURE)) {
			return null;
		}

		$start = (int) $m[0][1];
		$len = strlen($m[0][0]);

		return substr($content, 0, $start + $len) . "\n" . $block . "\n\n" . substr($content, $start + $len);
	}

	public static function addNotification(string $title, string $message = '', string $link = '', string $type = 'info'): ?int
	{
		if (!class_exists('AdminNotification', false)) {
			require_once dirname(__FILE__) . '/AdminNotification.php';
		}

		return AdminNotification::add($title, $message !== '' ? $message : $title, $link, $type);
	}

	/**
	 * Module JSON API: admin session + CSRF (`token` = admin_csrf_token).
	 * Call at the top of state-changing / admin-only module endpoints.
	 */
	public static function requireModuleApiAuth(): void
	{
		if (!headers_sent()) {
			header('Content-Type: application/json; charset=utf-8');
		}

		if (!self::isLoggedIn()) {
			http_response_code(403);
			echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim'], JSON_UNESCAPED_UNICODE);
			exit;
		}

		$token = (string) Tools::getValue('token');
		$sessionToken = (string) ($_SESSION['admin_csrf_token'] ?? '');

		if ($sessionToken === '' || !hash_equals($sessionToken, $token)) {
			http_response_code(403);
			echo json_encode(['success' => false, 'message' => 'Geçersiz istek'], JSON_UNESCAPED_UNICODE);
			exit;
		}
	}

	/**
	 * Cron: SHOP_TOKEN (query `token` or header X-Cron-Token) OR admin+CSRF.
	 * Prefer header for cron; query token still supported for scheduler URLs.
	 */
	public static function requireCronTokenOrAdminAuth(): void
	{
		$token = trim((string) Tools::getValue('token', ''));

		if ($token === '') {
			$token = trim((string) ($_SERVER['HTTP_X_CRON_TOKEN'] ?? ''));
		}

		$shopToken = (string) Settings::get('SHOP_TOKEN');

		if ($shopToken !== '' && $token !== '' && hash_equals($shopToken, $token)) {
			return;
		}

		self::requireModuleApiAuth();
	}

	public static function getDashboardStats(): array
	{
		$cancelled = Order::STATUS_CANCELLED;

		$ordersToday = (int) DB::getValue('SELECT COUNT(*) FROM orders WHERE DATE(date_add) = CURDATE()');
		$ordersYesterday = (int) DB::getValue(
			'SELECT COUNT(*) FROM orders WHERE DATE(date_add) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)'
		);
		$revenueToday = (float) DB::getValue(
			'SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != ? AND DATE(date_add) = CURDATE()',
			[$cancelled]
		);
		$revenueYesterday = (float) DB::getValue(
			'SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != ? AND DATE(date_add) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)',
			[$cancelled]
		);
		$revenuePrevWeek = (float) DB::getValue(
			'SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != ?
			 AND DATE(date_add) >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
			 AND DATE(date_add) < DATE_SUB(CURDATE(), INTERVAL 6 DAY)',
			[$cancelled]
		);

		$pendingReviews = 0;
		$reviewTable = DB::execute("SHOW TABLES LIKE 'product_reviews'");
		if (!empty($reviewTable)) {
			$pendingReviews = (int) DB::getValue('SELECT COUNT(*) FROM product_reviews WHERE active = 0');
		}

		return [
			'orders_total' => (int) DB::getValue('SELECT COUNT(*) FROM orders'),
			'orders_pending' => (int) DB::getValue('SELECT COUNT(*) FROM orders WHERE DATE(date_add) > DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND status = ?', [Order::STATUS_PENDING]),
			'orders_processing' => (int) DB::getValue('SELECT COUNT(*) FROM orders WHERE DATE(date_add) > DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND status = ?', [Order::STATUS_PROCESSING]),
			'orders_cargo' => (int) DB::getValue('SELECT COUNT(*) FROM orders WHERE DATE(date_add) > DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND status = ?', [Order::STATUS_SHIPPED]),
			'orders_awaiting_shipment' => (int) DB::getValue(
				'SELECT COUNT(*) FROM orders WHERE status IN (?, ?)',
				[Order::STATUS_PROCESSING, Order::STATUS_PENDING]
			),
			'orders_today' => $ordersToday,
			'orders_yesterday' => $ordersYesterday,
			'products_total' => (int) DB::getValue('SELECT COUNT(*) FROM products WHERE active = 1'),
			'products_low_stock' => (int) DB::getValue('SELECT COUNT(*) FROM products WHERE active = 1 AND stock <= 5'),
			'users_total' => (int) DB::getValue('SELECT COUNT(*) FROM users WHERE active = 1'),
			'users_today' => (int) DB::getValue('SELECT COUNT(*) FROM users WHERE DATE(date_add) = CURDATE()'),
			'messages_unread' => Contact::countUnread(),
			'pending_reviews' => $pendingReviews,
			'revenue_total' => (float) DB::getValue(
				'SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != ?',
				[$cancelled]
			),
			'revenue_month' => (float) DB::getValue(
				'SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != ? AND date_add >= DATE_SUB(NOW(), INTERVAL 30 DAY)',
				[$cancelled]
			),
			'revenue_today' => $revenueToday,
			'revenue_yesterday' => $revenueYesterday,
			'revenue_prev_week' => $revenuePrevWeek,
			'revenue_today_formatted' => Tools::displayPrice($revenueToday),
			'revenue_yesterday_formatted' => Tools::displayPrice($revenueYesterday),
			'revenue_prev_week_formatted' => Tools::displayPrice($revenuePrevWeek),
		];
	}

	public static function getDashboardCharts(): array
	{
		$cancelled = Order::STATUS_CANCELLED;
		$daily = [];

		for ($i = 13; $i >= 0; $i--) {
			$date = date('Y-m-d', strtotime('-' . $i . ' days'));
			$prevDate = date('Y-m-d', strtotime('-' . $i . ' days -7 days'));
			$daily[] = [
				'label' => date('Y-m-d', strtotime($date)),
				'label_short' => date('d.m', strtotime($date)),
				'orders' => (int) DB::getValue(
					'SELECT COUNT(*) FROM orders WHERE DATE(date_add) = ?',
					[$date]
				),
				'revenue' => (float) DB::getValue(
					'SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != ? AND DATE(date_add) = ?',
					[$cancelled, $date]
				),
				'revenue_prev' => (float) DB::getValue(
					'SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != ? AND DATE(date_add) = ?',
					[$cancelled, $prevDate]
				),
			];
		}

		$status = [];

		foreach (Order::getStatusOptions() as $statusId => $label) {
			$status[] = [
				'label' => $label,
				'count' => (int) DB::getValue('SELECT COUNT(*) FROM orders WHERE status = ?', [$statusId]),
			];
		}

		$topProducts = DB::execute(
			'SELECT od.product_name, od.id_product, SUM(od.qty) AS sold_qty,
				COALESCE(SUM(od.total), 0) AS revenue
			 FROM order_detail od
			 INNER JOIN orders o ON o.id_order = od.id_order
			 WHERE o.status != ?
			 GROUP BY od.id_product, od.product_name
			 ORDER BY sold_qty DESC
			 LIMIT 5',
			[$cancelled]
		) ?: [];

		foreach ($topProducts as &$tp) {
			$tp['sold_qty'] = (int) ($tp['sold_qty'] ?? 0);
			$tp['revenue'] = (float) ($tp['revenue'] ?? 0);
			$tp['revenue_formatted'] = Tools::displayPrice($tp['revenue']);
			$coverId = (int) DB::getValue(
				'SELECT id_image FROM images WHERE id_product = ? AND cover = 1 LIMIT 1',
				[(int) ($tp['id_product'] ?? 0)]
			);
			$tp['image_url'] = Product::getImageUrl($coverId > 0 ? $coverId : null);
		}
		unset($tp);

		return [
			'daily' => $daily,
			'status' => $status,
			'top_products' => $topProducts,
		];
	}

	public static function getLowStockProducts(int $limit = 6): array
	{
		$limit = max(1, min(20, $limit));
		$rows = DB::execute(
			'SELECT p.id_product, p.product_name, p.stock, i.id_image
			 FROM products p
			 LEFT JOIN images i ON p.id_product = i.id_product AND i.cover = 1
			 WHERE p.active = 1 AND p.stock <= 5
			 ORDER BY p.stock ASC, p.id_product DESC
			 LIMIT ' . $limit
		) ?: [];

		foreach ($rows as &$row) {
			$row['id_product'] = (int) $row['id_product'];
			$row['stock'] = (int) $row['stock'];
			$row['image_url'] = Product::getImageUrl(!empty($row['id_image']) ? (int) $row['id_image'] : null);
		}
		unset($row);

		return $rows;
	}

	public static function isDemoMode(): bool
	{
		if (!class_exists('Settings', false)) {
			return false;
		}

		return (string) Settings::get('DEMO_MODE') === '1'
			|| (int) Settings::get('DEMO_MODE') === 1;
	}

	public static function getList(): array
	{
		$rows = DB::execute(
			'SELECT id_admin, full_name, email, active
			 FROM admins
			 ORDER BY id_admin ASC'
		) ?: [];

		foreach ($rows as &$row) {
			$row['id_admin'] = (int) $row['id_admin'];
			$row['active'] = (int) $row['active'];
			$row['is_self'] = $row['id_admin'] === self::getId();
		}
		unset($row);

		return $rows;
	}

	public static function updateProfile(int $idAdmin, string $fullName, string $email): array
	{
		if (self::isDemoMode()) {
			return self::fail(adminT('Demo mode: some edits are not allowed'));
		}

		$fullName = trim($fullName);
		$email = trim(strtolower($email));

		if ($idAdmin <= 0 || $idAdmin !== self::getId()) {
			return self::fail(adminT('Invalid request'));
		}

		if (!Validate::isName($fullName)) {
			return self::fail(adminT('Please enter a valid full name'));
		}

		if (!Validate::isEmail($email)) {
			return self::fail(adminT('Please enter a valid email'));
		}

		$exists = (int) DB::getValue(
			'SELECT id_admin FROM admins WHERE email = ? AND id_admin != ?',
			[$email, $idAdmin]
		);

		if ($exists > 0) {
			return self::fail(adminT('This email is already used by another admin'));
		}

		$ok = DB::update('admins', [
			'full_name' => mb_substr($fullName, 0, 128),
			'email' => mb_substr($email, 0, 128),
		], 'id_admin = :where_id', ['where_id' => $idAdmin]);

		if ($ok === false) {
			return self::fail(adminT('Could not save profile'));
		}

		return self::ok(adminT('Profile updated'));
	}

	public static function changePassword(int $idAdmin, string $currentPassword, string $newPassword, string $confirmPassword): array
	{
		if (self::isDemoMode()) {
			return self::fail(adminT('Demo mode: some edits are not allowed'));
		}

		if ($idAdmin <= 0 || $idAdmin !== self::getId()) {
			return self::fail(adminT('Invalid request'));
		}

		if ($newPassword !== $confirmPassword) {
			return self::fail(adminT('Passwords do not match'));
		}

		if (strlen($newPassword) < 8) {
			return self::fail(adminT('Password must be at least 8 characters'));
		}

		$row = DB::getRowSafe('admins', 'id_admin = ? AND active = 1', [$idAdmin]);

		if (!$row || !password_verify($currentPassword, $row['password'])) {
			return self::fail(adminT('Current password is incorrect'));
		}

		$ok = DB::update('admins', [
			'password' => password_hash($newPassword, PASSWORD_DEFAULT),
		], 'id_admin = :where_id', ['where_id' => $idAdmin]);

		if ($ok === false) {
			return self::fail(adminT('Could not update password'));
		}

		return self::ok(adminT('Password updated'));
	}

	public static function createAdmin(string $fullName, string $email, string $password, string $confirmPassword): array
	{
		if (self::isDemoMode()) {
			return self::fail(adminT('Demo mode: some edits are not allowed'));
		}

		$fullName = trim($fullName);
		$email = trim(strtolower($email));

		if (!Validate::isName($fullName)) {
			return self::fail(adminT('Please enter a valid full name'));
		}

		if (!Validate::isEmail($email)) {
			return self::fail(adminT('Please enter a valid email'));
		}

		if ($password !== $confirmPassword) {
			return self::fail(adminT('Passwords do not match'));
		}

		if (strlen($password) < 8) {
			return self::fail(adminT('Password must be at least 8 characters'));
		}

		$exists = (int) DB::getValue('SELECT id_admin FROM admins WHERE email = ?', [$email]);

		if ($exists > 0) {
			return self::fail(adminT('This email is already used by another admin'));
		}

		$id = DB::insert('admins', [
			'full_name' => mb_substr($fullName, 0, 128),
			'email' => mb_substr($email, 0, 128),
			'password' => password_hash($password, PASSWORD_DEFAULT),
			'active' => 1,
		]);

		if (!$id) {
			return self::fail(adminT('Could not create admin'));
		}

		return self::ok(adminT('Admin account created'));
	}

	public static function setActive(int $idAdmin, bool $active): array
	{
		if (self::isDemoMode()) {
			return self::fail(adminT('Demo mode: some edits are not allowed'));
		}

		if ($idAdmin <= 0) {
			return self::fail(adminT('Invalid request'));
		}

		if ($idAdmin === self::getId()) {
			return self::fail(adminT('You cannot deactivate your own account'));
		}

		$row = DB::getRowSafe('admins', 'id_admin = ?', [$idAdmin]);

		if (!$row) {
			return self::fail(adminT('Admin not found'));
		}

		$ok = DB::update('admins', [
			'active' => $active ? 1 : 0,
		], 'id_admin = :where_id', ['where_id' => $idAdmin]);

		if ($ok === false) {
			return self::fail(adminT('Could not update admin'));
		}

		return self::ok($active
			? adminT('Admin activated')
			: adminT('Admin deactivated')
		);
	}

	public static function deleteAdmin(int $idAdmin): array
	{
		if (self::isDemoMode()) {
			return self::fail(adminT('Demo mode: some edits are not allowed'));
		}

		if ($idAdmin <= 0) {
			return self::fail(adminT('Invalid request'));
		}

		if ($idAdmin === self::getId()) {
			return self::fail(adminT('You cannot delete your own account'));
		}

		$count = (int) DB::getValue('SELECT COUNT(*) FROM admins');

		if ($count <= 1) {
			return self::fail(adminT('At least one admin account is required'));
		}

		$row = DB::getRowSafe('admins', 'id_admin = ?', [$idAdmin]);

		if (!$row) {
			return self::fail(adminT('Admin not found'));
		}

		$ok = DB::execute(
			'DELETE FROM admins WHERE id_admin = ?',
			[$idAdmin]
		);

		if ($ok === false) {
			return self::fail(adminT('Could not delete admin'));
		}

		return self::ok(adminT('Admin deleted'));
	}

	private static function ok(string $message): array
	{
		return ['success' => true, 'message' => $message];
	}

	private static function fail(string $message): array
	{
		return ['success' => false, 'message' => $message];
	}
}
