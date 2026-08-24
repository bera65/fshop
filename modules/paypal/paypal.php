<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';
require_once __DIR__ . '/lib/PayPalClient.php';

/**
 * PayPal Checkout (Orders API v2) — sipariş öncesi ödeme.
 *
 * Akış: checkout → /paypal-payment → PayPal approve → /paypal-return → capture → sipariş.
 */
class PaypalModule extends ModuleBase
{
	public string $name = 'paypal';
	public string $title = 'PayPal';
	public string $version = '1.0.0';
	public string $description = 'PayPal ile güvenli ödeme (Orders API v2)';
	public string $author = 'FShop';

	public bool $isPayment = true;
	public bool $paysBeforeOrder = true;
	public string $paymentMethodId = 'paypal';
	public string $paymentMethodLabel = 'PayPal';

	public array $routes = [
		'paypal-payment' => 'front/payment.php',
		'paypal-return' => 'front/return.php',
		'paypal-cancel' => 'front/cancel.php',
	];

	public array $displayHooks = [
		'order_payment' => 'Checkout ödeme seçeneği',
	];

	public array $defaultDisplayHooks = ['order_payment'];

	public function install(): bool
	{
		self::ensurePendingStorage();

		return true;
	}

	public function uninstall(): bool
	{
		DB::execute('DROP TABLE IF EXISTS paypal_pending_checkouts');

		return true;
	}

	public function getPaymentPageUrl(): string
	{
		global $domain;

		return rtrim((string) $domain, '/') . '/paypal-payment';
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken, $domain;

		$flash = '';

		if (Tools::isSubmit('savePaypal') && hash_equals($adminToken, (string) Tools::getValue('token'))) {
			Settings::set('PAYPAL_CLIENT_ID', trim((string) Tools::getValue('client_id')));
			Settings::set('PAYPAL_CLIENT_SECRET', trim((string) Tools::getValue('client_secret')));
			Settings::set('PAYPAL_SANDBOX', Tools::getValue('sandbox') ? '1' : '0');
			$currency = strtoupper(trim((string) Tools::getValue('currency', 'USD')));

			if (!preg_match('/^[A-Z]{3}$/', $currency)) {
				$currency = 'USD';
			}

			Settings::set('PAYPAL_CURRENCY', $currency);
			$flash = 'PayPal ayarları kaydedildi';
		}

		$base = rtrim((string) $domain, '/');

		$smarty->assign([
			'paypalClientId' => Settings::get('PAYPAL_CLIENT_ID'),
			'paypalClientSecret' => Settings::get('PAYPAL_CLIENT_SECRET'),
			'paypalSandbox' => Settings::get('PAYPAL_SANDBOX') !== '0',
			'paypalCurrency' => Settings::get('PAYPAL_CURRENCY') ?: 'USD',
			'paypalConfigured' => self::isConfigured(),
			'paypalReturnUrl' => $base . '/paypal-return',
			'paypalCancelUrl' => $base . '/paypal-cancel',
			'flash' => $flash,
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

		$html = $this->renderFrontTemplate('order_payment', []);

		return $html !== '' ? $html : null;
	}

	public static function isConfigured(): bool
	{
		return trim((string) Settings::get('PAYPAL_CLIENT_ID')) !== ''
			&& trim((string) Settings::get('PAYPAL_CLIENT_SECRET')) !== '';
	}

	public static function client(): ?PayPalClient
	{
		if (!self::isConfigured()) {
			return null;
		}

		return new PayPalClient(
			(string) Settings::get('PAYPAL_CLIENT_ID'),
			(string) Settings::get('PAYPAL_CLIENT_SECRET'),
			Settings::get('PAYPAL_SANDBOX') !== '0'
		);
	}

	public static function currency(): string
	{
		$code = strtoupper(trim((string) (Settings::get('PAYPAL_CURRENCY') ?: 'USD')));

		return preg_match('/^[A-Z]{3}$/', $code) ? $code : 'USD';
	}

	public static function ensurePendingStorage(): void
	{
		static $ready = false;

		if ($ready) {
			return;
		}

		$ready = true;

		DB::execute(
			'CREATE TABLE IF NOT EXISTS paypal_pending_checkouts (
				paypal_order_id VARCHAR(64) NOT NULL,
				reference VARCHAR(32) NOT NULL DEFAULT \'\',
				payload LONGTEXT NOT NULL,
				amount DECIMAL(20,2) NOT NULL DEFAULT 0.00,
				currency VARCHAR(3) NOT NULL DEFAULT \'USD\',
				status VARCHAR(32) NOT NULL DEFAULT \'pending\',
				date_add DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				date_upd DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (paypal_order_id),
				KEY reference (reference)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
		);
	}

	public static function persistPending(
		string $paypalOrderId,
		string $reference,
		array $checkoutData,
		array $cart,
		float $amount,
		string $currency
	): void {
		self::ensurePendingStorage();

		$payload = json_encode([
			'checkout' => $checkoutData,
			'cart' => $cart,
			'coupon_code' => (string) ($_SESSION[Coupon::SESSION_KEY] ?? ''),
			'id_user' => Customer::getId(),
			'reference' => $reference,
		], JSON_UNESCAPED_UNICODE);

		DB::execute(
			'INSERT INTO paypal_pending_checkouts
				(paypal_order_id, reference, payload, amount, currency, status)
			 VALUES (?, ?, ?, ?, ?, ?)
			 ON DUPLICATE KEY UPDATE reference = VALUES(reference), payload = VALUES(payload),
			 amount = VALUES(amount), currency = VALUES(currency), status = VALUES(status),
			 date_upd = CURRENT_TIMESTAMP',
			[$paypalOrderId, $reference, $payload, round($amount, 2), $currency, 'created']
		);
	}

	/** @return array<string, mixed>|null */
	public static function loadPending(string $paypalOrderId): ?array
	{
		self::ensurePendingStorage();
		$row = DB::getRowSafe('paypal_pending_checkouts', 'paypal_order_id = ?', [$paypalOrderId]);

		if (!$row || empty($row['payload'])) {
			return null;
		}

		$data = json_decode((string) $row['payload'], true);

		if (!is_array($data)) {
			return null;
		}

		$data['_amount'] = (float) ($row['amount'] ?? 0);
		$data['_currency'] = (string) ($row['currency'] ?? '');
		$data['_status'] = (string) ($row['status'] ?? '');
		$data['_reference'] = (string) ($row['reference'] ?? '');

		return $data;
	}

	public static function markStatus(string $paypalOrderId, string $status): void
	{
		self::ensurePendingStorage();
		DB::execute(
			'UPDATE paypal_pending_checkouts SET status = ?, date_upd = CURRENT_TIMESTAMP WHERE paypal_order_id = ?',
			[$status, $paypalOrderId]
		);
	}

	public static function deletePending(string $paypalOrderId): void
	{
		self::ensurePendingStorage();
		DB::execute('DELETE FROM paypal_pending_checkouts WHERE paypal_order_id = ?', [$paypalOrderId]);
	}

	/**
	 * @return array{ok:bool,approve_url?:string,paypal_order_id?:string,message?:string}
	 */
	public static function startCheckout(array $pendingData, array $cart): array
	{
		$client = self::client();

		if (!$client) {
			return ['ok' => false, 'message' => 'PayPal yapılandırması eksik'];
		}

		$preview = self::buildPreviewOrder($pendingData, $cart);
		$amount = round((float) $preview['total'], 2);

		if ($amount <= 0) {
			return ['ok' => false, 'message' => 'Geçersiz tutar'];
		}

		$currency = self::currency();
		$reference = (string) ($pendingData['_paypal_reference'] ?? '');

		if ($reference === '') {
			$reference = Order::reserveReference();
		}

		global $domain;
		$base = rtrim((string) $domain, '/');
		$siteName = trim((string) Settings::get('SITE_NAME')) ?: 'FShop';

		$body = [
			'intent' => 'CAPTURE',
			'purchase_units' => [[
				'reference_id' => mb_substr($reference, 0, 256),
				'description' => mb_substr($siteName . ' sipariş ' . $reference, 0, 127),
				'custom_id' => mb_substr($reference, 0, 127),
				'amount' => [
					'currency_code' => $currency,
					'value' => number_format($amount, 2, '.', ''),
				],
			]],
			'application_context' => [
				'brand_name' => mb_substr($siteName, 0, 127),
				'landing_page' => 'NO_PREFERENCE',
				'user_action' => 'PAY_NOW',
				'shipping_preference' => 'NO_SHIPPING',
				'return_url' => $base . '/paypal-return',
				'cancel_url' => $base . '/paypal-cancel',
			],
		];

		$created = $client->createOrder($body);

		if (!$created['ok']) {
			return [
				'ok' => false,
				'message' => 'PayPal: ' . ($created['error'] ?? 'sipariş oluşturulamadı'),
			];
		}

		$paypalOrderId = (string) $created['id'];
		self::persistPending($paypalOrderId, $reference, $pendingData, $cart, $amount, $currency);

		return [
			'ok' => true,
			'approve_url' => (string) $created['approve_url'],
			'paypal_order_id' => $paypalOrderId,
		];
	}

	/**
	 * @return array{ok:bool,message:string,id_order?:int,reference?:string}
	 */
	public static function completeReturn(string $paypalOrderId): array
	{
		$paypalOrderId = trim($paypalOrderId);

		if ($paypalOrderId === '') {
			return ['ok' => false, 'message' => 'PayPal sipariş numarası eksik'];
		}

		$client = self::client();

		if (!$client) {
			return ['ok' => false, 'message' => 'PayPal yapılandırması eksik'];
		}

		$pending = self::loadPending($paypalOrderId);

		if (!$pending) {
			return ['ok' => false, 'message' => 'Bekleyen ödeme bulunamadı'];
		}

		if (($pending['_status'] ?? '') === 'paid') {
			$ref = (string) ($pending['_reference'] ?? $pending['reference'] ?? '');
			$existing = $ref !== '' ? DB::getRowSafe('orders', 'reference = ?', [$ref]) : null;

			return [
				'ok' => true,
				'message' => 'Ödeme zaten tamamlanmış',
				'id_order' => (int) ($existing['id_order'] ?? 0),
				'reference' => $ref,
			];
		}

		$capture = $client->captureOrder($paypalOrderId);

		if (!$capture['ok']) {
			// Zaten capture edilmiş olabilir
			$existing = $client->getOrder($paypalOrderId);

			if ($existing['ok'] && (string) ($existing['status'] ?? '') === 'COMPLETED') {
				$capture = [
					'ok' => true,
					'status' => 'COMPLETED',
					'amount' => (float) ($pending['_amount'] ?? 0),
					'currency' => (string) ($pending['_currency'] ?? ''),
					'data' => $existing['data'] ?? [],
				];
			} else {
				self::markStatus($paypalOrderId, 'failed');

				return [
					'ok' => false,
					'message' => 'PayPal: ' . ($capture['error'] ?? 'ödeme alınamadı'),
				];
			}
		}

		$expected = round((float) ($pending['_amount'] ?? 0), 2);
		$paid = round((float) ($capture['amount'] ?? 0), 2);

		if ($paid <= 0 || abs($paid - $expected) > 0.05) {
			error_log('PayPal amount mismatch ' . $paypalOrderId . ' expected=' . $expected . ' paid=' . $paid);
			self::markStatus($paypalOrderId, 'failed');

			return [
				'ok' => false,
				'message' => 'PayPal: tutar uyuşmazlığı',
			];
		}

		self::markStatus($paypalOrderId, 'paid');

		$checkout = is_array($pending['checkout'] ?? null) ? $pending['checkout'] : [];
		$cart = is_array($pending['cart'] ?? null) ? $pending['cart'] : [];
		$reference = (string) ($pending['reference'] ?? $pending['_reference'] ?? '');

		if ($reference === '') {
			$reference = Order::reserveReference();
		}

		$checkout['_payment_done'] = 1;
		$checkout['_reference'] = $reference;
		$checkout['_cart_snapshot'] = $cart;
		$checkout['_stored_id_user'] = (int) ($pending['id_user'] ?? 0);
		$checkout['_stored_coupon_code'] = (string) ($pending['coupon_code'] ?? '');
		$checkout['payment_method'] = 'paypal';

		$_SESSION['pending_order_data'] = $checkout;

		if (!empty($pending['coupon_code'])) {
			$_SESSION[Coupon::SESSION_KEY] = $pending['coupon_code'];
		}

		$result = Order::placePending();

		if (!$result['success']) {
			error_log('PayPal: order create failed for ' . $paypalOrderId . ' — ' . ($result['message'] ?? ''));

			return [
				'ok' => false,
				'message' => $result['message'] ?? 'Sipariş oluşturulamadı (ödeme alındı, destek ile iletişime geçin)',
			];
		}

		$idOrder = (int) ($result['id_order'] ?? 0);

		if ($idOrder > 0) {
			Order::updateStatus($idOrder, Order::STATUS_PROCESSING);
		}

		self::deletePending($paypalOrderId);

		return [
			'ok' => true,
			'message' => 'Ödeme tamamlandı',
			'id_order' => $idOrder,
			'reference' => (string) ($result['reference'] ?? $reference),
		];
	}

	/** @return array<string, mixed> */
	public static function buildPreviewOrder(array $pendingData, array $cart): array
	{
		$name = trim((string) ($pendingData['customer_name'] ?? ''));
		$phone = Customer::normalizePhone((string) ($pendingData['customer_phone'] ?? ''));
		$customerEmail = strtolower(trim((string) ($pendingData['customer_email'] ?? '')));
		$city = trim((string) ($pendingData['address_city'] ?? ''));
		$district = trim((string) ($pendingData['address_district'] ?? ''));
		$address = trim((string) ($pendingData['address_text'] ?? ''));
		$idUser = Customer::getId();
		$idAddress = (int) ($pendingData['id_address'] ?? 0);

		if ($idAddress > 0 && $idUser > 0) {
			$savedAddress = Address::getForUser($idAddress, $idUser);

			if ($savedAddress) {
				$name = $savedAddress['full_name'];
				$phone = $savedAddress['phone'];
				$city = $savedAddress['city'];
				$district = $savedAddress['district'];
				$address = $savedAddress['address_text'];
			}
		}

		if ($idUser > 0 && $customerEmail === '') {
			$current = Customer::getCurrent();
			$customerEmail = strtolower(trim((string) ($current['email'] ?? '')));
		}

		$summary = Coupon::getCheckoutSummary((float) $cart['total']);
		$items = [];

		foreach ($cart['items'] as $item) {
			$lineTotal = (float) $item['line_total'];
			$items[] = [
				'product_name' => (string) $item['product_name'],
				'price' => (float) $item['price'],
				'qty' => (int) $item['qty'],
				'total' => $lineTotal,
				'total_formatted' => Tools::displayPrice($lineTotal),
			];
		}

		return [
			'id_order' => 0,
			'reference' => (string) ($pendingData['_paypal_reference'] ?? ''),
			'id_user' => $idUser,
			'customer_name' => $name,
			'customer_phone' => $phone,
			'customer_email' => $customerEmail,
			'address_text' => $address,
			'address_district' => $district,
			'address_city' => $city,
			'items' => $items,
			'subtotal' => (float) $summary['subtotal'],
			'subtotal_formatted' => (string) $summary['subtotal_formatted'],
			'shipping' => (float) $summary['shipping'],
			'shipping_formatted' => (string) $summary['shipping_formatted'],
			'coupon_discount' => (float) $summary['discount'],
			'total' => (float) $summary['total'],
			'total_formatted' => (string) $summary['total_formatted'],
		];
	}
}
