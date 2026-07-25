<?php

class AlertService
{
	private const SETTING_ORDER_EMAIL = 'ALERT_ORDER_EMAIL_ENABLED';
	private const SETTING_CRITICAL_STOCK = 'ALERT_CRITICAL_STOCK_ENABLED';
	private const SETTING_CRITICAL_THRESHOLD = 'ALERT_CRITICAL_STOCK_THRESHOLD';
	private const SETTING_BACK_IN_STOCK = 'ALERT_BACK_IN_STOCK_ENABLED';
	private const SETTING_ADMIN_EMAILS = 'ALERT_ADMIN_EMAILS';

	public static function ensureSchema(): void
	{
		static $ready = false;

		if ($ready) {
			return;
		}

		$ready = true;

		if (!empty(DB::execute("SHOW TABLES LIKE 'alert_stock_subscriptions'"))) {
			return;
		}

		$sqlFile = dirname(__DIR__) . '/install.sql';

		if (!is_file($sqlFile)) {
			return;
		}

		$sql = (string) file_get_contents($sqlFile);

		foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
			if ($statement !== '') {
				DB::execute($statement);
			}
		}
	}

	public static function ensureDefaultSettings(): void
	{
		if (Settings::get(self::SETTING_ORDER_EMAIL) === '') {
			Settings::set(self::SETTING_ORDER_EMAIL, '1');
		}

		if (Settings::get(self::SETTING_CRITICAL_STOCK) === '') {
			Settings::set(self::SETTING_CRITICAL_STOCK, '1');
		}

		if (Settings::get(self::SETTING_CRITICAL_THRESHOLD) === '') {
			Settings::set(self::SETTING_CRITICAL_THRESHOLD, '5');
		}

		if (Settings::get(self::SETTING_BACK_IN_STOCK) === '') {
			Settings::set(self::SETTING_BACK_IN_STOCK, '1');
		}
	}

	public static function isOrderEmailEnabled(): bool
	{
		return Settings::get(self::SETTING_ORDER_EMAIL) === '1';
	}

	public static function isCriticalStockEnabled(): bool
	{
		return Settings::get(self::SETTING_CRITICAL_STOCK) === '1';
	}

	public static function isBackInStockEnabled(): bool
	{
		return Settings::get(self::SETTING_BACK_IN_STOCK) === '1';
	}

	public static function getCriticalThreshold(): int
	{
		return max(0, (int) Settings::get(self::SETTING_CRITICAL_THRESHOLD));
	}

	/** @return array<int, string> */
	public static function getAdminEmails(): array
	{
		$emails = [];
		$raw = trim((string) Settings::get(self::SETTING_ADMIN_EMAILS));

		if ($raw !== '') {
			foreach (preg_split('/[\s,;]+/', $raw) ?: [] as $part) {
				$part = trim(strtolower($part));

				if ($part !== '' && filter_var($part, FILTER_VALIDATE_EMAIL)) {
					$emails[$part] = $part;
				}
			}
		}

		self::collectEmail($emails, (string) Settings::get('CONTACT_EMAIL'));
		self::collectEmail($emails, (string) Settings::get('SMTP_FROM_EMAIL'));
		self::collectEmail($emails, (string) Settings::get('SMTP_USER'));

		if ($emails === []) {
			$adminRows = DB::execute('SELECT email FROM admins WHERE active = 1 ORDER BY id_admin ASC') ?: [];

			foreach ($adminRows as $adminRow) {
				self::collectEmail($emails, (string) ($adminRow['email'] ?? ''));
			}
		}

		return array_values($emails);
	}

	private static function collectEmail(array &$emails, string $email): void
	{
		$email = trim(strtolower($email));

		if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$emails[$email] = $email;
		}
	}

	public static function saveSettings(array $data): array
	{
		Settings::set(self::SETTING_ORDER_EMAIL, !empty($data['order_email']) ? '1' : '0');
		Settings::set(self::SETTING_CRITICAL_STOCK, !empty($data['critical_stock']) ? '1' : '0');
		Settings::set(self::SETTING_CRITICAL_THRESHOLD, (string) max(0, (int) ($data['critical_threshold'] ?? 0)));
		Settings::set(self::SETTING_BACK_IN_STOCK, !empty($data['back_in_stock']) ? '1' : '0');
		Settings::set(self::SETTING_ADMIN_EMAILS, trim((string) ($data['admin_emails'] ?? '')));

		return ['success' => true, 'message' => 'Ayarlar kaydedildi'];
	}

	/** @return array<string, mixed> */
	public static function getSettingsForAdmin(): array
	{
		return [
			'order_email' => self::isOrderEmailEnabled(),
			'critical_stock' => self::isCriticalStockEnabled(),
			'critical_threshold' => self::getCriticalThreshold(),
			'back_in_stock' => self::isBackInStockEnabled(),
			'admin_emails' => (string) Settings::get(self::SETTING_ADMIN_EMAILS),
			'resolved_admin_emails' => self::getAdminEmails(),
		];
	}

	/** @return array{success:bool,message:string,sent?:int,failed?:int} */
	public static function sendTestAdminEmail(): array
	{
		$emails = self::getAdminEmails();

		if ($emails === []) {
			return [
				'success' => false,
				'message' => 'Admin e-posta adresi bulunamadı. Üstteki alana e-posta yazın veya Ayarlar → E-posta / admin hesaplarını kontrol edin.',
			];
		}

		$siteName = Settings::get('SITE_NAME') ?: 'FShop';
		$body = '<p>Bu bir test e-postasıdır. Alert modülü admin bildirimleri bu adrese gönderilecek:</p>'
			. '<p><strong>' . htmlspecialchars(implode(', ', $emails), ENT_QUOTES, 'UTF-8') . '</strong></p>';
		$sent = Mail::send($emails[0], $siteName . ' — Alert modülü test', $body);

		if (!$sent) {
			return [
				'success' => false,
				'message' => 'Test e-postası gönderilemedi: ' . Mail::getLastError(),
			];
		}

		return [
			'success' => true,
			'message' => 'Test e-postası gönderildi: ' . $emails[0],
		];
	}

	/** @return array{success:bool,message:string,sent:int,failed:int,skipped:int} */
	public static function processAllPendingBackInStockAlerts(): array
	{
		self::ensureSchema();

		if (!self::isBackInStockEnabled()) {
			return [
				'success' => false,
				'message' => 'Stoğa girince haber ver özelliği kapalı.',
				'sent' => 0,
				'failed' => 0,
				'skipped' => 0,
			];
		}

		$productIds = DB::execute(
			'SELECT DISTINCT id_product FROM alert_stock_subscriptions WHERE is_sent = 0 ORDER BY id_product ASC'
		) ?: [];

		$sent = 0;
		$failed = 0;
		$skipped = 0;

		foreach ($productIds as $row) {
			$idProduct = (int) ($row['id_product'] ?? 0);

			if ($idProduct <= 0) {
				continue;
			}

			$pendingBefore = self::countPendingForProduct($idProduct);
			$product = Product::getByIdAdmin($idProduct);

			if (!$product) {
				$skipped += $pendingBefore;
				continue;
			}

			if (!Product::isInStock($product)) {
				$skipped += $pendingBefore;
				continue;
			}

			self::notifyBackInStockSubscribers($idProduct, 0, $product);
			$pendingAfter = self::countPendingForProduct($idProduct);
			$processed = max(0, $pendingBefore - $pendingAfter);
			$sent += $processed;

			if ($processed < $pendingBefore) {
				$failed += ($pendingBefore - $processed - $pendingAfter);
			}
		}

		$message = $sent . ' bildirim gönderildi';

		if ($failed > 0) {
			$message .= ', ' . $failed . ' gönderilemedi';

			if (Mail::getLastError() !== '') {
				$message .= ' (' . Mail::getLastError() . ')';
			}
		}

		if ($skipped > 0) {
			$message .= ', ' . $skipped . ' bekliyor (ürün hâlâ stokta değil)';
		}

		return [
			'success' => $sent > 0 || ($failed === 0 && $skipped === 0),
			'message' => $message,
			'sent' => $sent,
			'failed' => $failed,
			'skipped' => $skipped,
		];
	}

	public static function handleOrderPlaced(array $order): void
	{
		self::ensureSchema();

		if (self::isOrderEmailEnabled()) {
			self::notifyAdminNewOrder($order);
		}

		if (!self::isCriticalStockEnabled()) {
			return;
		}

		$idOrder = (int) ($order['id_order'] ?? 0);

		if ($idOrder <= 0) {
			return;
		}

		$items = DB::execute(
			'SELECT id_product, id_variation, qty, product_name FROM order_detail WHERE id_order = ?',
			[$idOrder]
		) ?: [];

		foreach ($items as $item) {
			$idProduct = (int) ($item['id_product'] ?? 0);
			$idVariation = (int) ($item['id_variation'] ?? 0);
			$qty = max(1, (int) ($item['qty'] ?? 1));

			if ($idProduct <= 0) {
				continue;
			}

			$product = Product::getByIdAdmin($idProduct);

			if (!$product || Product::isPackProduct($product)) {
				continue;
			}

			$newStock = Product::getStock($product, $idVariation);
			$oldStock = $newStock + $qty;

			self::handleStockChange($idProduct, $idVariation, $oldStock, $newStock, $product);
			self::saveSnapshot($idProduct, $idVariation, $newStock);
		}
	}

	public static function handleProductUpdated(int $idProduct, array $product): void
	{
		self::ensureSchema();

		if ($idProduct <= 0 || !$product) {
			return;
		}

		if (Product::isPackProduct($product)) {
			return;
		}

		$oldStock = self::getSnapshot($idProduct, 0);
		$newStock = Product::getStock($product, 0);

		if ($oldStock !== null) {
			self::handleStockChange($idProduct, 0, $oldStock, $newStock, $product);
		}

		self::processBackInStockAlerts($idProduct, $product);
		self::saveSnapshot($idProduct, 0, $newStock);
	}

	public static function processBackInStockAlerts(int $idProduct, array $product): void
	{
		if (!self::isBackInStockEnabled() || $idProduct <= 0) {
			return;
		}

		if (!self::hasPendingSubscriptions($idProduct)) {
			return;
		}

		$product = Product::getByIdAdmin($idProduct) ?: $product;

		if (!Product::isInStock($product)) {
			return;
		}

		self::notifyBackInStockSubscribers($idProduct, 0, $product);
	}

	public static function handleStockChange(
		int $idProduct,
		int $idVariation,
		int $oldStock,
		int $newStock,
		array $product
	): void {
		if (self::isCriticalStockEnabled()) {
			self::maybeNotifyCriticalStock($idProduct, $idVariation, $oldStock, $newStock, $product);
		}

		if (self::isBackInStockEnabled() && $oldStock <= 0 && $newStock > 0) {
			self::notifyBackInStockSubscribers($idProduct, $idVariation, $product);
		}
	}

	private static function maybeNotifyCriticalStock(
		int $idProduct,
		int $idVariation,
		int $oldStock,
		int $newStock,
		array $product
	): void {
		$threshold = self::getCriticalThreshold();

		if ($newStock > $threshold) {
			return;
		}

		if ($oldStock <= $threshold) {
			return;
		}

		self::notifyAdminCriticalStock($idProduct, $idVariation, $newStock, $product);
	}

	public static function subscribeStockAlert(int $idProduct, string $email, int $idVariation = 0, ?int $idUser = null): array
	{
		self::ensureSchema();

		$idProduct = (int) $idProduct;
		$idVariation = max(0, (int) $idVariation);
		$email = trim(strtolower($email));

		if (!self::isBackInStockEnabled()) {
			return ['success' => false, 'message' => translate('This feature is currently unavailable.')];
		}

		if ($idProduct <= 0) {
			return ['success' => false, 'message' => translate('Invalid Product')];
		}

		$product = Product::getById($idProduct);

		if (!$product) {
			return ['success' => false, 'message' => translate('Product Not Found')];
		}

		if (!Validate::isEmail($email)) {
			return ['success' => false, 'message' => translate('Invalid E-Mail')];
		}

		if (Product::isInStock($product, 1, $idVariation)) {
			return ['success' => false, 'message' => translate('This product is already in stock.')];
		}

		$existing = DB::getRowSafe(
			'alert_stock_subscriptions',
			'id_product = ? AND id_variation = ? AND email = ? AND is_sent = 0',
			[$idProduct, $idVariation, $email]
		);

		if ($existing) {
			return ['success' => false, 'message' => translate('You have a pending request.')];
		}

		$productUrl = $product['url'] ?? Product::getLink($product);
		$id = DB::insert('alert_stock_subscriptions', [
			'id_product' => $idProduct,
			'id_variation' => $idVariation,
			'id_user' => $idUser ?? 0,
			'email' => $email,
			'product_name' => mb_substr((string) ($product['product_name'] ?? ''), 0, 255),
			'product_url' => mb_substr((string) $productUrl, 0, 500),
			'is_sent' => 0,
			'date_add' => date('Y-m-d H:i:s'),
		]);

		if (!$id) {
			return ['success' => false, 'message' => translate('Data could not be added.')];
		}

		self::saveSnapshot($idProduct, $idVariation, Product::getStock($product, $idVariation));

		return [
			'success' => true,
			'message' => translate('We will notify you when this product is back in stock.'),
		];
	}

	private static function notifyAdminNewOrder(array $order): void
	{
		$emails = self::getAdminEmails();

		if ($emails === []) {
			return;
		}
		$customer = htmlspecialchars((string) ($order['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8');
		$total = Tools::displayPrice((float) ($order['total'] ?? 0));
		$idOrder = (int) ($order['id_order'] ?? 0);
		$adminUrl = htmlspecialchars(self::adminPanelUrl('order?id=' . $idOrder), ENT_QUOTES, 'UTF-8');
		$siteName = htmlspecialchars(Settings::get('SITE_NAME') ?: 'FShop', ENT_QUOTES, 'UTF-8');

		$body = '<h2 style="margin:0 0 16px;">Yeni sipariş</h2>'
			. '<p><strong>Sipariş no:</strong> ' . $reference . '</p>'
			. '<p><strong>Müşteri:</strong> ' . ($customer !== '' ? $customer : 'Misafir') . '</p>'
			. '<p><strong>Toplam:</strong> ' . htmlspecialchars($total, ENT_QUOTES, 'UTF-8') . '</p>'
			. '<p style="margin:24px 0;"><a href="' . $adminUrl . '" style="display:inline-block;padding:12px 24px;background:#1a1a1a;color:#fff;text-decoration:none;border-radius:6px;">Siparişi görüntüle</a></p>';

		$subject = $siteName . ' — Yeni sipariş #' . (string) ($order['reference'] ?? '');

		foreach ($emails as $email) {
			Mail::send($email, $subject, $body);
		}

		if ($idOrder > 0 && class_exists('AdminNotification', false)) {
			AdminNotification::add(
				'Yeni sipariş #' . (string) ($order['reference'] ?? ''),
				($customer !== '' ? $customer : 'Misafir') . ' — ' . $total,
				self::adminPanelUrl('order?id=' . $idOrder),
				'order'
			);
		}
	}

	private static function notifyAdminCriticalStock(
		int $idProduct,
		int $idVariation,
		int $newStock,
		array $product
	): void {
		$emails = self::getAdminEmails();

		if ($emails === []) {
			return;
		}

		$name = htmlspecialchars((string) ($product['product_name'] ?? 'Ürün'), ENT_QUOTES, 'UTF-8');
		$stockCode = htmlspecialchars((string) ($product['stock_code'] ?? ''), ENT_QUOTES, 'UTF-8');
		$threshold = self::getCriticalThreshold();
		$adminUrl = htmlspecialchars(self::adminPanelUrl('product?id=' . $idProduct), ENT_QUOTES, 'UTF-8');
		$siteName = htmlspecialchars(Settings::get('SITE_NAME') ?: 'FShop', ENT_QUOTES, 'UTF-8');
		$variationNote = '';

		if ($idVariation > 0) {
			$variation = ProductVariation::getById($idVariation);
			$label = trim((string) ($variation['label'] ?? ''));

			if ($label !== '') {
				$variationNote = ' (' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . ')';
			}
		}

		$body = '<h2 style="margin:0 0 16px;">Kritik stok uyarısı</h2>'
			. '<p><strong>Ürün:</strong> ' . $name . $variationNote . '</p>'
			. ($stockCode !== '' ? '<p><strong>Stok kodu:</strong> ' . $stockCode . '</p>' : '')
			. '<p><strong>Güncel stok:</strong> ' . (int) $newStock . '</p>'
			. '<p><strong>Eşik:</strong> ' . $threshold . ' ve altı</p>'
			. '<p style="margin:24px 0;"><a href="' . $adminUrl . '" style="display:inline-block;padding:12px 24px;background:#dc3545;color:#fff;text-decoration:none;border-radius:6px;">Ürünü düzenle</a></p>';

		$subject = $siteName . ' — Kritik stok: ' . (string) ($product['product_name'] ?? 'Ürün');

		foreach ($emails as $email) {
			Mail::send($email, $subject, $body);
		}

		if (class_exists('AdminNotification', false)) {
			AdminNotification::add(
				'Kritik stok: ' . (string) ($product['product_name'] ?? 'Ürün'),
				'Güncel stok: ' . (int) $newStock . ' (eşik: ' . $threshold . ')',
				self::adminPanelUrl('product?id=' . $idProduct),
				'stock'
			);
		}
	}

	private static function notifyBackInStockSubscribers(int $idProduct, int $idVariation, array $product): void
	{
		$params = [$idProduct];
		$sql = 'SELECT * FROM alert_stock_subscriptions WHERE id_product = ? AND is_sent = 0';

		if ($idVariation > 0) {
			$sql .= ' AND (id_variation = 0 OR id_variation = ?)';
			$params[] = $idVariation;
		}

		$rows = DB::execute($sql, $params) ?: [];
		$productName = (string) ($product['product_name'] ?? 'Ürün');
		$siteName = Settings::get('SITE_NAME') ?: 'FShop';

		foreach ($rows as $row) {
			$rowVariation = (int) ($row['id_variation'] ?? 0);

			if ($rowVariation > 0 && $rowVariation !== $idVariation) {
				continue;
			}

			if ($rowVariation > 0 && !Product::isInStock($product, 1, $rowVariation)) {
				continue;
			}

			$productUrl = (string) ($row['product_url'] ?? '');

			if ($productUrl === '') {
				$productUrl = Product::getLink($product);
			}

			$url = htmlspecialchars($productUrl, ENT_QUOTES, 'UTF-8');
			$name = htmlspecialchars($productName, ENT_QUOTES, 'UTF-8');
			$body = '<h2 style="margin:0 0 16px;">' . self::storefrontT('Back in stock') . '</h2>'
				. '<p>' . self::storefrontT('The product you requested is back in stock:') . '</p>'
				. '<p><strong>' . $name . '</strong></p>'
				. '<p style="margin:24px 0;"><a href="' . $url . '" style="display:inline-block;padding:12px 24px;background:#28a745;color:#fff;text-decoration:none;border-radius:6px;">'
				. self::storefrontT('View product') . '</a></p>';

			$sent = Mail::send(
				(string) $row['email'],
				$siteName . ' — ' . self::storefrontT('Back in stock') . ': ' . $productName,
				$body
			);

			if ($sent) {
				DB::update(
					'alert_stock_subscriptions',
					[
						'is_sent' => 1,
						'sent_at' => date('Y-m-d H:i:s'),
					],
					'id_subscription = :id',
					['id' => (int) $row['id_subscription']]
				);
			}
		}
	}

	public static function getSnapshot(int $idProduct, int $idVariation = 0): ?int
	{
		self::ensureSchema();

		$value = DB::getValue(
			'SELECT last_stock FROM alert_stock_snapshots WHERE id_product = ? AND id_variation = ? LIMIT 1',
			[$idProduct, max(0, $idVariation)]
		);

		return $value === false ? null : (int) $value;
	}

	public static function saveSnapshot(int $idProduct, int $idVariation, int $stock): void
	{
		self::ensureSchema();

		$idVariation = max(0, $idVariation);
		$existing = DB::getValue(
			'SELECT id_snapshot FROM alert_stock_snapshots WHERE id_product = ? AND id_variation = ? LIMIT 1',
			[$idProduct, $idVariation]
		);

		if ($existing) {
			DB::update(
				'alert_stock_snapshots',
				['last_stock' => $stock],
				'id_snapshot = :id',
				['id' => (int) $existing]
			);

			return;
		}

		DB::insert('alert_stock_snapshots', [
			'id_product' => $idProduct,
			'id_variation' => $idVariation,
			'last_stock' => $stock,
		]);
	}

	/** @return array<int, array<string, mixed>> */
	public static function getSubscriptionsForAdmin(int $limit, int $offset, string $filter = 'pending'): array
	{
		self::ensureSchema();

		$sql = 'SELECT * FROM alert_stock_subscriptions WHERE 1=1';
		$params = [];

		if ($filter === 'pending') {
			$sql .= ' AND is_sent = 0';
		} elseif ($filter === 'sent') {
			$sql .= ' AND is_sent = 1';
		}

		$sql .= ' ORDER BY id_subscription DESC LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

		$rows = DB::execute($sql, $params) ?: [];

		foreach ($rows as &$row) {
			$row['date_formatted'] = Tools::formatDate3($row['date_add']);
			$row['sent_at_formatted'] = !empty($row['sent_at']) ? Tools::formatDate3($row['sent_at']) : '-';
		}
		unset($row);

		return $rows;
	}

	public static function countSubscriptions(string $filter = 'pending'): int
	{
		self::ensureSchema();

		$sql = 'SELECT COUNT(*) FROM alert_stock_subscriptions WHERE 1=1';

		if ($filter === 'pending') {
			$sql .= ' AND is_sent = 0';
		} elseif ($filter === 'sent') {
			$sql .= ' AND is_sent = 1';
		}

		return (int) DB::getValue($sql);
	}

	private static function hasPendingSubscriptions(int $idProduct): bool
	{
		self::ensureSchema();

		return (int) DB::getValue(
			'SELECT COUNT(*) FROM alert_stock_subscriptions WHERE id_product = ? AND is_sent = 0',
			[$idProduct]
		) > 0;
	}

	private static function adminPanelUrl(string $path = ''): string
	{
		if (class_exists('Admin', false)) {
			return Admin::url($path);
		}

		$domain = rtrim((string) Settings::get('DOMAIN'), '/');
		$uri = 'admin';

		if (class_exists('App', false)) {
			$raw = trim((string) App::env('ADMIN_URI', 'admin'), "/ \t\n\r\0\x0B");
			$sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '', $raw);

			if ($sanitized !== null && $sanitized !== '') {
				$uri = $sanitized;
			}
		}

		return $domain . '/' . $uri . '/' . ltrim($path, '/');
	}

	private static function storefrontT(string $text): string
	{
		if (class_exists('Lang', false)) {
			return Lang::translateFor($text, Lang::getDefault());
		}

		return $text;
	}
}
