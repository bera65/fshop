<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';
require_once __DIR__ . '/lib/KuveytTurkHelper.php';

class KuveytturkModule extends ModuleBase
{
	public string $name = 'kuveytturk';
	public string $title = 'KuveytTürk Sanal POS';
	public string $version = '1.1.0';
	public string $description = 'KuveytTürk 3D Secure ile güvenli kredi/banka kartı ödemesi kabul edin.';
	public string $author = 'FShop';

	public bool $isPayment = true;
	public bool $paysBeforeOrder = true;
	public string $paymentMethodId = 'kuveytturk';
	public string $paymentMethodLabel = 'Kredi / Banka Kartı (KuveytTürk)';

	public array $routes = [
		'kuveytturk-payment' => 'front/payment.php',
	];

	public array $displayHooks = [
		'order_payment' => 'Checkout ödeme seçeneği',
	];

	public array $defaultDisplayHooks = ['order_payment'];

	public array $frontStylesheets = ['kuveytturk.css'];

	public array $apiActions = [
		'callback' => 'api/callback.php',
	];

	public function install(): bool
	{
		$this->runSqlFile('install.sql');
		self::ensurePendingStorage();

		return true;
	}

	public function boot(): void
	{
		self::ensurePendingStorage();
	}

	public function uninstall(): bool
	{
		$this->runSqlFile('uninstall.sql');

		return true;
	}

	public static function ensurePendingStorage(): void
	{
		DB::execute(
			'CREATE TABLE IF NOT EXISTS `kuveytturk_pending_checkouts` (
			  `id_pending` int(11) NOT NULL AUTO_INCREMENT,
			  `reference` varchar(64) NOT NULL,
			  `cart_summary` longtext NOT NULL,
			  `checkout_data` longtext NOT NULL,
			  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  PRIMARY KEY (`id_pending`),
			  UNIQUE KEY `reference` (`reference`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
		);
	}

	public static function getBaseUrl(): string
	{
		global $domain;

		$url = trim((string) Settings::get('DOMAIN'));

		if ($url === '') {
			$url = is_string($domain) ? trim($domain) : '';
		}

		if ($url === '') {
			$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
			$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
			$script = $_SERVER['SCRIPT_NAME'] ?? '';
			$path = str_replace('/api/module.php', '', $script);
			$path = str_replace('/api', '', $path);
			$url = $scheme . $host . rtrim($path, '/') . '/';
		}

		if (!preg_match('~^https?://~i', $url)) {
			$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
			$url = $scheme . ltrim($url, '/');
		}

		return rtrim($url, '/') . '/';
	}

	public function getPaymentPageUrl(): string
	{
		return self::getBaseUrl() . 'kuveytturk-payment';
	}

	public static function isConfigured(): bool
	{
		return trim((string) Settings::get('KUVEYTTURK_MERCHANT_ID')) !== ''
			&& trim((string) Settings::get('KUVEYTTURK_CUSTOMER_ID')) !== ''
			&& trim((string) Settings::get('KUVEYTTURK_USERNAME')) !== ''
			&& trim((string) Settings::get('KUVEYTTURK_PASSWORD')) !== '';
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken;

		$flash = '';

		if (Tools::isSubmit('saveKuveytturk')) {
			$postToken = (string) Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				Settings::set('KUVEYTTURK_MERCHANT_ID', trim((string) Tools::getValue('merchant_id')));
				Settings::set('KUVEYTTURK_CUSTOMER_ID', trim((string) Tools::getValue('customer_id')));
				Settings::set('KUVEYTTURK_USERNAME', trim((string) Tools::getValue('username')));
				Settings::set('KUVEYTTURK_PASSWORD', trim((string) Tools::getValue('password')));
				Settings::set('KUVEYTTURK_TEST_MODE', Tools::getValue('test_mode') ? '1' : '0');
				$flash = 'KuveytTürk Sanal POS ayarları kaydedildi.';
			} else {
				$flash = 'Geçersiz güvenlik doğrulaması (Token)';
			}
		}

		$helper = new KuveytTurkHelper();
		$logs = $helper->getRecentLogs(50);

		$smarty->assign([
			'kuveytturkMerchantId' => Settings::get('KUVEYTTURK_MERCHANT_ID'),
			'kuveytturkCustomerId' => Settings::get('KUVEYTTURK_CUSTOMER_ID'),
			'kuveytturkUsername' => Settings::get('KUVEYTTURK_USERNAME'),
			'kuveytturkPassword' => Settings::get('KUVEYTTURK_PASSWORD'),
			'kuveytturkTestMode' => Settings::get('KUVEYTTURK_TEST_MODE') !== '0',
			'kuveytturkCallbackUrl' => self::getBaseUrl() . 'api/module.php?m=kuveytturk&action=callback',
			'logs' => $logs,
			'flash' => $flash,
			'adminToken' => $adminToken,
		]);
	}

	public function renderDisplayHook(string $hook, array $context = []): ?string
	{
		if ($hook !== 'order_payment') {
			return null;
		}

		if (!self::isConfigured()) {
			return null;
		}

		return $this->renderFrontTemplate('order_payment', []);
	}

	public static function persistPendingCheckout(string $reference, array $pendingData, array $cartSummary): void
	{
		self::ensurePendingStorage();
		$now = date('Y-m-d H:i:s');
		$cartJson = json_encode($cartSummary, JSON_UNESCAPED_UNICODE);
		$checkoutJson = json_encode($pendingData, JSON_UNESCAPED_UNICODE);

		$exists = DB::execute('SELECT id_pending FROM kuveytturk_pending_checkouts WHERE reference = ? LIMIT 1', [$reference]);

		if (!empty($exists)) {
			DB::execute(
				'UPDATE kuveytturk_pending_checkouts SET cart_summary = ?, checkout_data = ?, created_at = ? WHERE reference = ?',
				[$cartJson, $checkoutJson, $now, $reference]
			);
		} else {
			DB::execute(
				'INSERT INTO kuveytturk_pending_checkouts (reference, cart_summary, checkout_data, created_at) VALUES (?, ?, ?, ?)',
				[$reference, $cartJson, $checkoutJson, $now]
			);
		}
	}

	public static function getPendingCheckout(string $reference): ?array
	{
		self::ensurePendingStorage();
		$rows = DB::execute('SELECT * FROM kuveytturk_pending_checkouts WHERE reference = ? LIMIT 1', [$reference]) ?: [];
		$row = $rows[0] ?? null;

		if (!$row) {
			return null;
		}

		return [
			'cart_summary' => json_decode((string) ($row['cart_summary'] ?? ''), true) ?: [],
			'checkout_data' => json_decode((string) ($row['checkout_data'] ?? ''), true) ?: [],
		];
	}

	public static function removePendingCheckout(string $reference): void
	{
		self::ensurePendingStorage();
		DB::execute('DELETE FROM kuveytturk_pending_checkouts WHERE reference = ?', [$reference]);
	}

	public static function buildPreviewOrder(array $pendingData, array $cart): array
	{
		return [
			'reference' => (string) ($pendingData['_kuveytturk_reference'] ?? $pendingData['reference'] ?? 'ORD-' . time()),
			'total' => (float) ($cart['total'] ?? 0),
			'currency_symbol' => (string) ($cart['currency_symbol'] ?? 'TL'),
			'items' => is_array($cart['items'] ?? null) ? $cart['items'] : [],
			'customer_name' => (string) ($pendingData['customer_name'] ?? ''),
			'customer_email' => (string) ($pendingData['customer_email'] ?? ''),
			'customer_phone' => (string) ($pendingData['customer_phone'] ?? ''),
		];
	}
}
