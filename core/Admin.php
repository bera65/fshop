<?php

class Admin
{
	public static function ensureSchema(): void
	{
		static $ready = false;

		if ($ready) {
			return;
		}

		$ready = true;

		$resetToken = DB::execute("SHOW COLUMNS FROM `admins` LIKE 'reset_token'");

		if (empty($resetToken)) {
			DB::execute(
				"ALTER TABLE `admins`
				 ADD COLUMN `reset_token` varchar(64) NOT NULL DEFAULT '' AFTER `password`,
				 ADD COLUMN `reset_expires` datetime DEFAULT NULL AFTER `reset_token`"
			);
		}
	}

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

	public static function requestPasswordReset(string $email): array
	{
		self::ensureSchema();

		if (self::isDemoMode()) {
			return self::fail(adminT('Demo mode: some edits are not allowed'));
		}

		$email = trim(strtolower($email));
		$identifier = RateLimit::loginIdentifier($email);

		if (RateLimit::isLimited(RateLimit::SCOPE_ADMIN_PASSWORD_RESET, $identifier, 5, 3600)) {
			return self::fail(adminT('Too many password reset requests. Please try again later.'));
		}

		if (!Validate::isEmail($email)) {
			return self::fail(adminT('Please enter a valid email'));
		}

		RateLimit::record(RateLimit::SCOPE_ADMIN_PASSWORD_RESET, $identifier);

		$row = DB::getRowSafe('admins', 'email = ? AND active = 1', [$email]);

		if ($row) {
			$rawToken = bin2hex(random_bytes(32));
			$tokenHash = hash('sha256', $rawToken);
			$expires = date('Y-m-d H:i:s', time() + 3600);

			DB::update(
				'admins',
				[
					'reset_token' => $tokenHash,
					'reset_expires' => $expires,
				],
				'id_admin = :id_admin',
				['id_admin' => (int) $row['id_admin']]
			);

			$resetUrl = self::url('reset-password') . '?rt=' . $rawToken;
			Mail::sendAdminPasswordReset($email, (string) $row['full_name'], $resetUrl);
		}

		return self::ok(adminT('If an account exists for this email, a reset link has been sent.'));
	}

	public static function findValidPasswordReset(string $token): ?array
	{
		self::ensureSchema();

		$token = trim($token);

		if ($token === '' || !preg_match('/^[a-f0-9]{64}$/i', $token)) {
			return null;
		}

		$tokenHash = hash('sha256', $token);

		$row = DB::getRowSafe(
			'admins',
			'reset_token = ? AND active = 1 AND reset_expires IS NOT NULL AND reset_expires > NOW()',
			[$tokenHash]
		);

		if (!$row) {
			return null;
		}

		unset($row['password'], $row['reset_token']);

		return $row;
	}

	public static function resetPassword(string $token, string $password, string $confirmPassword): array
	{
		self::ensureSchema();

		if (self::isDemoMode()) {
			return self::fail(adminT('Demo mode: some edits are not allowed'));
		}

		if ($password !== $confirmPassword) {
			return self::fail(adminT('Passwords do not match'));
		}

		if (strlen($password) < 8) {
			return self::fail(adminT('Password must be at least 8 characters'));
		}

		$row = self::findValidPasswordReset($token);

		if (!$row) {
			return self::fail(adminT('Invalid or expired reset link'));
		}

		$ok = DB::update(
			'admins',
			[
				'password' => password_hash($password, PASSWORD_DEFAULT),
				'reset_token' => '',
				'reset_expires' => null,
			],
			'id_admin = :id_admin',
			['id_admin' => (int) $row['id_admin']]
		);

		if ($ok === false) {
			return self::fail(adminT('Could not update password'));
		}

		return self::ok(adminT('Password updated. You can sign in now.'));
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

	/**
	 * Period analytics for the redesigned admin dashboard.
	 *
	 * @return array<string, mixed>
	 */
	public static function getDashboardAnalytics(string $period = 'month'): array
	{
		$period = self::normalizeDashboardPeriod($period);
		[$from, $to, $periodLabel] = self::dashboardPeriodBounds($period);

		MarketplaceTables::ensureSchema();

		$cancelled = Order::STATUS_CANCELLED;
		$returned = Order::STATUS_RETURNED;
		$returnPending = Order::STATUS_RETURN_PENDING;
		$pending = Order::STATUS_PENDING;
		$processing = Order::STATUS_PROCESSING;
		$shipped = Order::STATUS_SHIPPED;

		$dateSql = 'o.date_add >= ? AND o.date_add < ?';
		$dateParams = [$from, $to];

		$inventoryValue = (float) DB::getValue(
			'SELECT COALESCE(SUM(stock * cost), 0) FROM products WHERE active = 1'
		);

		$listingsTotal = 0;
		foreach (['trendyol_products', 'hepsiburada_products', 'n11_products'] as $table) {
			$exists = DB::execute("SHOW TABLES LIKE '" . str_replace("'", "''", $table) . "'");
			if (!empty($exists)) {
				$listingsTotal += (int) DB::getValue('SELECT COUNT(*) FROM `' . $table . '`');
			}
		}

		$ordersTotalAll = (int) DB::getValue('SELECT COUNT(*) FROM orders');
		$productsTotal = (int) DB::getValue('SELECT COUNT(*) FROM products WHERE active = 1');

		$periodOrderCount = (int) DB::getValue(
			"SELECT COUNT(*) FROM orders o WHERE {$dateSql} AND o.status NOT IN (?, ?)",
			array_merge($dateParams, [$cancelled, $returned])
		);

		$gross = (float) DB::getValue(
			"SELECT COALESCE(SUM(o.subtotal), 0) FROM orders o WHERE {$dateSql}",
			$dateParams
		);

		$revenue = (float) DB::getValue(
			"SELECT COALESCE(SUM(o.total), 0) FROM orders o WHERE {$dateSql} AND o.status NOT IN (?, ?, ?)",
			array_merge($dateParams, [$cancelled, $returned, $returnPending])
		);

		$cancelCount = (int) DB::getValue(
			"SELECT COUNT(*) FROM orders o WHERE {$dateSql} AND o.status = ?",
			array_merge($dateParams, [$cancelled])
		);
		$cancelAmount = (float) DB::getValue(
			"SELECT COALESCE(SUM(o.subtotal), 0) FROM orders o WHERE {$dateSql} AND o.status = ?",
			array_merge($dateParams, [$cancelled])
		);

		$returnCount = (int) DB::getValue(
			"SELECT COUNT(*) FROM orders o WHERE {$dateSql} AND o.status IN (?, ?)",
			array_merge($dateParams, [$returned, $returnPending])
		);
		$returnAmount = (float) DB::getValue(
			"SELECT COALESCE(SUM(o.subtotal), 0) FROM orders o WHERE {$dateSql} AND o.status IN (?, ?)",
			array_merge($dateParams, [$returned, $returnPending])
		);

		$returnTable = DB::execute("SHOW TABLES LIKE 'return_requests'");
		if (!empty($returnTable)) {
			$rrCount = (int) DB::getValue(
				'SELECT COUNT(*) FROM return_requests WHERE date_add >= ? AND date_add < ?',
				$dateParams
			);
			if ($rrCount > $returnCount) {
				$returnCount = $rrCount;
			}
		}

		$shipping = (float) DB::getValue(
			"SELECT COALESCE(SUM(o.shipping), 0) FROM orders o
			 WHERE {$dateSql} AND o.status NOT IN (?, ?, ?)",
			array_merge($dateParams, [$cancelled, $returned, $returnPending])
		);

		$campaign = (float) DB::getValue(
			"SELECT COALESCE(SUM(
				o.coupon_discount + o.promotion_discount + o.payment_discount + o.manual_discount
			), 0) FROM orders o
			 WHERE {$dateSql} AND o.status NOT IN (?, ?, ?)",
			array_merge($dateParams, [$cancelled, $returned, $returnPending])
		);

		$cogs = (float) DB::getValue(
			"SELECT COALESCE(SUM(od.qty * COALESCE(p.cost, 0)), 0)
			 FROM order_detail od
			 INNER JOIN orders o ON o.id_order = od.id_order
			 LEFT JOIN products p ON p.id_product = od.id_product
			 WHERE {$dateSql} AND o.status NOT IN (?, ?, ?)",
			array_merge($dateParams, [$cancelled, $returned, $returnPending])
		);

		$defaultVat = 20.0;
		if (class_exists('Tax', false)) {
			try {
				$defaultVat = (float) Tax::getDefaultRate();
			} catch (Throwable $e) {
				$defaultVat = 20.0;
			}
		}

		$vat = (float) DB::getValue(
			"SELECT COALESCE(SUM(
				od.total * (COALESCE(NULLIF(p.vat, 0), ?) / (100 + COALESCE(NULLIF(p.vat, 0), ?)))
			), 0)
			 FROM order_detail od
			 INNER JOIN orders o ON o.id_order = od.id_order
			 LEFT JOIN products p ON p.id_product = od.id_product
			 WHERE {$dateSql} AND o.status NOT IN (?, ?, ?)",
			array_merge(
				[$defaultVat, $defaultVat],
				$dateParams,
				[$cancelled, $returned, $returnPending]
			)
		);

		$commission = 0.0;
		$cancelReturn = $cancelAmount + $returnAmount;
		$net = $gross - $cancelReturn - $shipping - $commission - $vat - $cogs - $campaign;
		if ($net < 0) {
			$net = 0.0;
		}

		$profitRate = $gross > 0 ? round(($net / $gross) * 100, 2) : 0.0;
		$cancelRate = $gross > 0 ? round(($cancelAmount / $gross) * 100, 2) : 0.0;
		$returnRate = $gross > 0 ? round(($returnAmount / $gross) * 100, 2) : 0.0;

		$soldQty = (float) DB::getValue(
			"SELECT COALESCE(SUM(od.qty), 0)
			 FROM order_detail od
			 INNER JOIN orders o ON o.id_order = od.id_order
			 WHERE {$dateSql} AND o.status NOT IN (?, ?, ?)",
			array_merge($dateParams, [$cancelled, $returned, $returnPending])
		);

		$awaitingShipment = (int) DB::getValue(
			'SELECT COUNT(*) FROM orders WHERE status IN (?, ?)',
			[$pending, $processing]
		);
		$processingCount = (int) DB::getValue(
			'SELECT COUNT(*) FROM orders WHERE status = ?',
			[$processing]
		);
		$shippedOpen = (int) DB::getValue(
			'SELECT COUNT(*) FROM orders WHERE status = ?',
			[$shipped]
		);

		$avgBasket = $periodOrderCount > 0 ? round($revenue / $periodOrderCount, 2) : 0.0;
		$avgCost = $soldQty > 0 ? round($cogs / $soldQty, 2) : 0.0;

		$waterfall = self::buildWaterfallSteps([
			'gross' => $gross,
			'cancel_return' => $cancelReturn,
			'shipping' => $shipping,
			'commission' => $commission,
			'vat' => $vat,
			'cogs' => $cogs,
			'campaign' => $campaign,
			'net' => $net,
		]);

		$platformBars = self::dashboardPlatformRevenue($from, $to);
		$questions = self::dashboardQuestionStats();
		$productPerformance = self::dashboardProductPerformance($from, $to, 8);

		return [
			'period' => $period,
			'period_label' => $periodLabel,
			'period_from' => $from,
			'period_to' => $to,
			'period_range_label' => date('Y-m-d', strtotime($from)) . ' - ' . date('Y-m-d', strtotime($to . ' -1 second')),
			'kpi' => [
				'inventory_value' => $inventoryValue,
				'inventory_value_formatted' => Tools::displayPrice($inventoryValue),
				'orders_total' => $ordersTotalAll,
				'products_total' => $productsTotal,
				'listings_total' => $listingsTotal,
				'customers_total' => (int) DB::getValue('SELECT COUNT(*) FROM users WHERE active = 1'),
			],
			'period_stats' => [
				'orders' => $periodOrderCount,
				'awaiting_shipment' => $awaitingShipment,
				'sold_qty' => round($soldQty, 2),
				'shipped' => $shippedOpen,
				'processing' => $processingCount,
				'revenue' => $revenue,
				'revenue_formatted' => Tools::displayPrice($revenue),
				'profit' => $net,
				'profit_formatted' => Tools::displayPrice($net),
				'profit_rate' => $profitRate,
				'cancel_count' => $cancelCount,
				'cancel_amount' => $cancelAmount,
				'cancel_amount_formatted' => Tools::displayPrice($cancelAmount),
				'cancel_rate' => $cancelRate,
				'return_count' => $returnCount,
				'return_amount' => $returnAmount,
				'return_amount_formatted' => Tools::displayPrice($returnAmount),
				'return_rate' => $returnRate,
				'avg_basket' => $avgBasket,
				'avg_basket_formatted' => Tools::displayPrice($avgBasket),
				'avg_cost' => $avgCost,
				'avg_cost_formatted' => Tools::displayPrice($avgCost),
				'products_added' => $productsTotal,
				'listings_added' => $listingsTotal,
			],
			'waterfall' => $waterfall,
			'waterfall_chart' => self::waterfallChartPayload($waterfall),
			'daily_sales_chart' => self::dashboardDailySalesChart($from, $to),
			'platform_bars' => $platformBars,
			'mp_sales_chart' => self::dashboardMpSalesChart($platformBars),
			'questions' => $questions,
			'product_performance' => $productPerformance,
		];
	}

	public static function normalizeDashboardPeriod(string $period): string
	{
		$period = strtolower(trim($period));
		$allowed = ['month', '7', '15', '30'];

		return in_array($period, $allowed, true) ? $period : 'month';
	}

	/**
	 * @return array{0:string,1:string,2:string} from, to (exclusive), label
	 */
	private static function dashboardPeriodBounds(string $period): array
	{
		$to = date('Y-m-d', strtotime('+1 day'));

		if ($period === '7') {
			$from = date('Y-m-d', strtotime('-6 days'));
			$label = date('d.m.Y', strtotime($from)) . ' – ' . date('d.m.Y');
		} elseif ($period === '15') {
			$from = date('Y-m-d', strtotime('-14 days'));
			$label = date('d.m.Y', strtotime($from)) . ' – ' . date('d.m.Y');
		} elseif ($period === '30') {
			$from = date('Y-m-d', strtotime('-29 days'));
			$label = date('d.m.Y', strtotime($from)) . ' – ' . date('d.m.Y');
		} else {
			$from = date('Y-m-01');
			$label = date('F Y');
			if (class_exists('AdminLang', false)) {
				$monthsTr = [
					'January' => 'Ocak', 'February' => 'Şubat', 'March' => 'Mart', 'April' => 'Nisan',
					'May' => 'Mayıs', 'June' => 'Haziran', 'July' => 'Temmuz', 'August' => 'Ağustos',
					'September' => 'Eylül', 'October' => 'Ekim', 'November' => 'Kasım', 'December' => 'Aralık',
				];
				if (strtolower(AdminLang::current()) === 'tr') {
					$en = date('F');
					$label = ($monthsTr[$en] ?? $en) . ' ' . date('Y');
				}
			}
		}

		return [$from . ' 00:00:00', $to . ' 00:00:00', $label];
	}

	/**
	 * @param array<string, float> $amounts
	 * @return list<array<string, mixed>>
	 */
	private static function buildWaterfallSteps(array $amounts): array
	{
		$defs = [
			['key' => 'gross', 'type' => 'total'],
			['key' => 'cancel_return', 'type' => 'cost'],
			['key' => 'shipping', 'type' => 'cost'],
			['key' => 'commission', 'type' => 'cost'],
			['key' => 'vat', 'type' => 'cost'],
			['key' => 'cogs', 'type' => 'cost'],
			['key' => 'campaign', 'type' => 'cost'],
			['key' => 'net', 'type' => 'net'],
		];

		$labels = [
			'gross' => 'Gross sales',
			'cancel_return' => 'Cancel / Return',
			'shipping' => 'Shipping',
			'commission' => 'Commission',
			'vat' => 'VAT',
			'cogs' => 'Cost of goods',
			'campaign' => 'Campaign',
			'net' => 'Net profit',
		];

		$steps = [];
		foreach ($defs as $def) {
			$key = $def['key'];
			$value = round((float) ($amounts[$key] ?? 0), 2);
			if ($def['type'] === 'cost' && $value <= 0) {
				continue;
			}
			$steps[] = [
				'key' => $key,
				'label' => $labels[$key],
				'value' => $value,
				'value_formatted' => Tools::displayPrice($value),
				'type' => $def['type'],
			];
		}

		return $steps;
	}

	/**
	 * Daily store + marketplace revenue bars for the selected period.
	 *
	 * @return array{
	 *   labels: list<string>,
	 *   data: list<float>,
	 *   marketplace_data: list<float>,
	 *   colors: list<string>,
	 *   marketplace_color: string,
	 *   store_label: string,
	 *   marketplace_label: string
	 * }
	 */
	private static function dashboardDailySalesChart(string $from, string $to): array
	{
		$cancelled = Order::STATUS_CANCELLED;
		$returned = Order::STATUS_RETURNED;
		$returnPending = Order::STATUS_RETURN_PENDING;

		$rows = DB::execute(
			'SELECT DATE(date_add) AS d, COALESCE(SUM(total), 0) AS revenue
			 FROM orders
			 WHERE date_add >= ? AND date_add < ?
			   AND status NOT IN (?, ?, ?)
			 GROUP BY DATE(date_add)',
			[$from, $to, $cancelled, $returned, $returnPending]
		) ?: [];

		$byDay = [];
		foreach ($rows as $row) {
			$day = (string) ($row['d'] ?? '');
			if ($day !== '') {
				$byDay[$day] = (float) ($row['revenue'] ?? 0);
			}
		}

		$mpByDay = [];
		$mpExists = DB::execute("SHOW TABLES LIKE 'marketplace_orders'");
		if (!empty($mpExists)) {
			$mpRows = DB::execute(
				'SELECT DATE(COALESCE(order_date, last_sync_at)) AS d,
					COALESCE(SUM(total_price), 0) AS revenue
				 FROM marketplace_orders
				 WHERE COALESCE(order_date, last_sync_at) >= ?
				   AND COALESCE(order_date, last_sync_at) < ?
				 GROUP BY DATE(COALESCE(order_date, last_sync_at))',
				[$from, $to]
			) ?: [];

			foreach ($mpRows as $row) {
				$day = (string) ($row['d'] ?? '');
				if ($day !== '') {
					$mpByDay[$day] = (float) ($row['revenue'] ?? 0);
				}
			}
		}

		$labels = [];
		$data = [];
		$mpData = [];
		$colors = [];

		try {
			$start = new DateTimeImmutable(substr($from, 0, 10));
			$endExclusive = new DateTimeImmutable(substr($to, 0, 10));
		} catch (Throwable $e) {
			return [
				'labels' => [],
				'data' => [],
				'marketplace_data' => [],
				'colors' => [],
				'marketplace_color' => '#F97316',
				'store_label' => 'Store sales',
				'marketplace_label' => 'Marketplace sales',
			];
		}

		for ($cursor = $start; $cursor < $endExclusive; $cursor = $cursor->modify('+1 day')) {
			$day = $cursor->format('Y-m-d');
			$labels[] = $cursor->format('d.m');
			$data[] = round($byDay[$day] ?? 0.0, 2);
			$mpData[] = round($mpByDay[$day] ?? 0.0, 2);
			$colors[] = '#0D9488';
		}

		return [
			'labels' => $labels,
			'data' => $data,
			'marketplace_data' => $mpData,
			'colors' => $colors,
			'marketplace_color' => '#F97316',
			'store_label' => 'Store sales',
			'marketplace_label' => 'Marketplace sales',
		];
	}

	/**
	 * Two-letter initials from an admin display name.
	 */
	public static function initialsFromName(string $name): string
	{
		$name = trim($name);
		if ($name === '') {
			return 'A';
		}

		$parts = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
		if (count($parts) >= 2) {
			$a = mb_substr($parts[0], 0, 1, 'UTF-8');
			$b = mb_substr($parts[1], 0, 1, 'UTF-8');

			return mb_strtoupper($a . $b, 'UTF-8');
		}

		return mb_strtoupper(mb_substr($name, 0, 2, 'UTF-8'), 'UTF-8');
	}

	/**
	 * Chart.js bar payload for profit / cost breakdown (absolute values, not waterfall).
	 *
	 * @param list<array<string, mixed>> $steps
	 * @return array{labels: list<string>, data: list<float>, colors: list<string>, types: list<string>}
	 */
	private static function waterfallChartPayload(array $steps): array
	{
		$labels = [];
		$data = [];
		$colors = [];
		$types = [];

		foreach ($steps as $step) {
			$type = (string) ($step['type'] ?? 'cost');
			$value = max(0.0, (float) ($step['value'] ?? 0));
			$labels[] = (string) ($step['label'] ?? '');
			$types[] = $type;
			$data[] = $value;

			if ($type === 'net') {
				$colors[] = '#10B981';
			} elseif ($type === 'total') {
				$colors[] = '#3B82F6';
			} else {
				$colors[] = '#F97316';
			}
		}

		return [
			'labels' => $labels,
			'data' => $data,
			'colors' => $colors,
			'types' => $types,
		];
	}

	/**
	 * @return array{icon_url: string, icon_file: string}
	 */
	private static function dashboardPlatformIcon(string $key): array
	{
		global $domain;

		$key = strtolower(trim($key));
		$base = rtrim((string) ($domain ?? ''), '/') . '/';

		if ($key === 'frisay') {
			return [
				'icon_url' => $base . 'img/faviconAdmin.ico',
				'icon_file' => 'faviconAdmin.ico',
			];
		}

		if (in_array($key, ['trendyol', 'hepsiburada', 'n11'], true)) {
			return [
				'icon_url' => $base . 'templates/admin/img/icons/' . $key . '.png',
				'icon_file' => $key . '.png',
			];
		}

		return [
			'icon_url' => '',
			'icon_file' => '',
		];
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private static function dashboardPlatformRevenue(string $from, string $to): array
	{
		$cancelled = Order::STATUS_CANCELLED;
		$returned = Order::STATUS_RETURNED;
		$returnPending = Order::STATUS_RETURN_PENDING;
		$exclude = [$from, $to, $cancelled, $returned, $returnPending];

		$siteName = trim((string) Settings::get('SITE_NAME', 'Frisay'));
		if ($siteName === '') {
			$siteName = 'Frisay';
		}

		$platforms = [
			'trendyol' => ['label' => 'Trendyol', 'color' => '#F27A1A'],
			'hepsiburada' => ['label' => 'Hepsiburada', 'color' => '#FF6000'],
			'n11' => ['label' => 'N11', 'color' => '#7C3AED'],
			'store' => ['label' => $siteName, 'color' => '#475569'],
			'pos' => ['label' => 'POS', 'color' => '#0F766E'],
		];

		$mpExists = DB::execute("SHOW TABLES LIKE 'marketplace_orders'");
		$mpByPlatform = [];

		if (!empty($mpExists)) {
			$mpRows = DB::execute(
				'SELECT platform, COUNT(*) AS order_count, COALESCE(SUM(total_price), 0) AS revenue
				 FROM marketplace_orders
				 WHERE order_date >= ? AND order_date < ?
				 GROUP BY platform',
				[$from, $to]
			) ?: [];

			foreach ($mpRows as $r) {
				$key = strtolower((string) ($r['platform'] ?? ''));
				$mpByPlatform[$key] = [
					'orders' => (int) ($r['order_count'] ?? 0),
					'revenue' => (float) ($r['revenue'] ?? 0),
				];
			}
		}

		$storeOrders = (int) DB::getValue(
			"SELECT COUNT(*) FROM orders
			 WHERE date_add >= ? AND date_add < ? AND status NOT IN (?, ?, ?)
			   AND payment_method NOT LIKE 'pos\\_%' AND reference NOT LIKE 'POS-%'",
			$exclude
		);
		$storeRevenue = (float) DB::getValue(
			"SELECT COALESCE(SUM(total), 0) FROM orders
			 WHERE date_add >= ? AND date_add < ? AND status NOT IN (?, ?, ?)
			   AND payment_method NOT LIKE 'pos\\_%' AND reference NOT LIKE 'POS-%'",
			$exclude
		);
		$posOrders = (int) DB::getValue(
			"SELECT COUNT(*) FROM orders
			 WHERE date_add >= ? AND date_add < ? AND status NOT IN (?, ?, ?)
			   AND (payment_method LIKE 'pos\\_%' OR reference LIKE 'POS-%')",
			$exclude
		);
		$posRevenue = (float) DB::getValue(
			"SELECT COALESCE(SUM(total), 0) FROM orders
			 WHERE date_add >= ? AND date_add < ? AND status NOT IN (?, ?, ?)
			   AND (payment_method LIKE 'pos\\_%' OR reference LIKE 'POS-%')",
			$exclude
		);

		$rows = [];
		$totalOrders = 0;
		$totalRevenue = 0.0;

		foreach ($platforms as $key => $meta) {
			if ($key === 'store') {
				$orders = $storeOrders;
				$revenue = $storeRevenue;
			} elseif ($key === 'pos') {
				$orders = $posOrders;
				$revenue = $posRevenue;
			} else {
				$orders = (int) ($mpByPlatform[$key]['orders'] ?? 0);
				$revenue = (float) ($mpByPlatform[$key]['revenue'] ?? 0);
			}

			if ($orders <= 0 && $revenue <= 0) {
				continue;
			}

			$totalOrders += $orders;
			$totalRevenue += $revenue;
			$icon = self::dashboardPlatformIcon($key === 'store' || $key === 'pos' ? 'frisay' : $key);
			$rows[] = [
				'key' => $key,
				'label' => $meta['label'],
				'color' => $meta['color'],
				'orders' => $orders,
				'revenue' => round($revenue, 2),
				'revenue_formatted' => Tools::displayPrice($revenue),
				'icon_url' => $icon['icon_url'],
				'icon_file' => $icon['icon_file'],
			];
		}

		usort($rows, static function (array $a, array $b): int {
			return $b['revenue'] <=> $a['revenue'];
		});

		$max = 0.0;
		foreach ($rows as $row) {
			$max = max($max, (float) $row['revenue']);
		}

		foreach ($rows as &$row) {
			$row['pct'] = $max > 0 ? round(($row['revenue'] / $max) * 100, 1) : 0;
			$row['is_total'] = false;
		}
		unset($row);

		$rows[] = [
			'key' => 'total',
			'label' => 'Total',
			'color' => '#94A3B8',
			'orders' => $totalOrders,
			'revenue' => round($totalRevenue, 2),
			'revenue_formatted' => Tools::displayPrice($totalRevenue),
			'pct' => 100,
			'is_total' => true,
			'icon_url' => '',
			'icon_file' => '',
		];

		return $rows;
	}

	/**
	 * Chart.js floating horizontal bar payload (Orion-style waterfall).
	 *
	 * @param list<array<string, mixed>> $platformBars
	 * @return array{items: list<array<string, mixed>>, total: float}
	 */
	private static function dashboardMpSalesChart(array $platformBars): array
	{
		$items = [];
		$total = 0.0;

		foreach ($platformBars as $bar) {
			if (!empty($bar['is_total'])) {
				$total = (float) ($bar['revenue'] ?? 0);
				continue;
			}

			$revenue = (float) ($bar['revenue'] ?? 0);
			$items[] = [
				'key' => (string) ($bar['key'] ?? ''),
				'name' => (string) ($bar['label'] ?? ''),
				'orders' => (int) ($bar['orders'] ?? 0),
				'revenue' => $revenue,
				'color' => (string) ($bar['color'] ?? '#64748b'),
			];
		}

		if ($total <= 0) {
			foreach ($items as $item) {
				$total += (float) $item['revenue'];
			}
		}

		return [
			'items' => $items,
			'total' => round($total, 2),
		];
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private static function dashboardQuestionStats(): array
	{
		$out = [];
		$platforms = [
			'trendyol' => 'Trendyol',
			'hepsiburada' => 'Hepsiburada',
			'n11' => 'N11',
			'frisay' => 'Frisay',
		];

		$mqExists = DB::execute("SHOW TABLES LIKE 'marketplace_questions'");

		foreach ($platforms as $key => $label) {
			$icon = self::dashboardPlatformIcon($key);

			if ($key === 'frisay') {
				$unread = Contact::countUnread();
				$total = Contact::countAdmin(null);
				$out[] = [
					'key' => $key,
					'label' => $label,
					'unanswered' => $unread,
					'total' => $total,
					'url' => Admin::url('messages'),
					'icon_url' => $icon['icon_url'],
					'icon_file' => $icon['icon_file'],
				];
				continue;
			}

			$unanswered = 0;
			$total = 0;
			if (!empty($mqExists)) {
				$total = (int) DB::getValue(
					'SELECT COUNT(*) FROM marketplace_questions WHERE platform = ?',
					[$key]
				);
				$unanswered = (int) DB::getValue(
					'SELECT COUNT(*) FROM marketplace_questions WHERE platform = ? AND answered = 0',
					[$key]
				);
			}

			$out[] = [
				'key' => $key,
				'label' => $label,
				'unanswered' => $unanswered,
				'total' => $total,
				'url' => Admin::url('marketplace-questions?platform=' . rawurlencode($key)),
				'icon_url' => $icon['icon_url'],
				'icon_file' => $icon['icon_file'],
			];
		}

		return $out;
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private static function dashboardProductPerformance(string $from, string $to, int $limit = 8): array
	{
		$cancelled = Order::STATUS_CANCELLED;
		$returned = Order::STATUS_RETURNED;
		$returnPending = Order::STATUS_RETURN_PENDING;
		$limit = max(1, min(16, $limit));

		$rows = DB::execute(
			"SELECT od.product_name, od.id_product,
				SUM(od.qty) AS sold_qty,
				COALESCE(SUM(od.total), 0) AS revenue,
				MAX(p.stock) AS stock
			 FROM order_detail od
			 INNER JOIN orders o ON o.id_order = od.id_order
			 LEFT JOIN products p ON p.id_product = od.id_product
			 WHERE o.date_add >= ? AND o.date_add < ?
			   AND o.status NOT IN (?, ?, ?)
			 GROUP BY od.id_product, od.product_name
			 ORDER BY revenue DESC
			 LIMIT " . (int) $limit,
			[$from, $to, $cancelled, $returned, $returnPending]
		) ?: [];

		foreach ($rows as &$row) {
			$id = (int) ($row['id_product'] ?? 0);
			$row['id_product'] = $id;
			$row['sold_qty'] = round((float) ($row['sold_qty'] ?? 0), 2);
			$row['revenue'] = (float) ($row['revenue'] ?? 0);
			$row['revenue_formatted'] = Tools::displayPrice($row['revenue']);
			$row['stock'] = round((float) ($row['stock'] ?? 0), 2);
			$row['stock_formatted'] = Tools::displayStock($row['stock']);
			$coverId = (int) DB::getValue(
				'SELECT id_image FROM images WHERE id_product = ? AND cover = 1 LIMIT 1',
				[$id]
			);
			$row['image_url'] = Product::getImageUrl($coverId > 0 ? $coverId : null);
			$row['edit_url'] = Admin::url('product?id=' . $id);
		}
		unset($row);

		return $rows;
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
			$row['stock'] = round((float) ($row['stock'] ?? 0), 2);
			$row['stock_formatted'] = Tools::displayStock($row['stock']);
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
