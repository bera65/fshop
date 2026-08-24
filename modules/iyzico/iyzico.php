<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';

class IyzicoModule extends ModuleBase
{
	public const API_LIVE = 'https://api.iyzipay.com';
	public const API_SANDBOX = 'https://sandbox-api.iyzipay.com';

	/** @var array<int, string> */
	private const CURRENCIES = ['TRY', 'EUR', 'USD', 'GBP', 'RUB', 'CHF', 'NOK'];

	public string $name = 'iyzico';
	public string $title = 'iyzico';
	public string $version = '1.0.0';
	public string $description = 'iyzico Checkout Form ile kredi/banka kartı ödemesi';
	public string $author = 'FShop';

	public bool $isPayment = true;
	public bool $paysBeforeOrder = true;
	public string $paymentMethodId = 'iyzico';
	public string $paymentMethodLabel = 'Kredi / Banka Kartı (iyzico)';

	public array $routes = [
		'iyzico-payment' => 'front/payment.php',
		'iyzico-callback' => 'front/callback.php',
	];

	public array $displayHooks = [
		'order_payment' => 'Checkout ödeme seçeneği',
	];

	public array $defaultDisplayHooks = ['order_payment'];

	public array $frontStylesheets = ['iyzico.css'];

	public array $apiActions = [
		'callback' => 'api/callback.php',
		'webhook' => 'api/webhook.php',
	];

	public function install(): bool
	{
		self::ensureWebhookKey();

		return $this->runSqlFile('install.sql');
	}

	public function boot(): void
	{
		self::ensurePendingStorage();
		self::ensureWebhookKey();
	}

	public function uninstall(): bool
	{
		return $this->runSqlFile('uninstall.sql');
	}

	public function getPaymentPageUrl(): string
	{
		global $domain;

		return rtrim($domain, '/') . '/iyzico-payment';
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken, $domain;

		$flash = '';

		if (Tools::isSubmit('saveIyzico')) {
			$postToken = (string) Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				$apiType = Tools::getValue('api_type') === 'sandbox' ? 'sandbox' : 'live';
				$language = (string) Tools::getValue('language');

				if (!in_array($language, ['auto', 'tr', 'en'], true)) {
					$language = 'auto';
				}

				$formClass = Tools::getValue('form_class') === 'popup' ? 'popup' : 'responsive';

				Settings::set('IYZICO_API_KEY', trim((string) Tools::getValue('api_key')));
				Settings::set('IYZICO_SECRET_KEY', trim((string) Tools::getValue('secret_key')));
				Settings::set('IYZICO_API_TYPE', $apiType);
				Settings::set('IYZICO_LANGUAGE', $language);
				Settings::set('IYZICO_FORM_CLASS', $formClass);
				Settings::set('IYZICO_NO_INSTALLMENT', Tools::getValue('no_installment') ? '1' : '0');
				self::ensureWebhookKey();
				$flash = 'iyzico ayarları kaydedildi';
			} else {
				$flash = 'Geçersiz istek';
			}
		}

		$smarty->assign([
			'iyzicoApiKey' => Settings::get('IYZICO_API_KEY'),
			'iyzicoSecretKey' => Settings::get('IYZICO_SECRET_KEY'),
			'iyzicoApiType' => self::getApiType(),
			'iyzicoLanguage' => Settings::get('IYZICO_LANGUAGE') ?: 'auto',
			'iyzicoFormClass' => self::getFormClass(),
			'iyzicoNoInstallment' => Settings::get('IYZICO_NO_INSTALLMENT') === '1',
			'iyzicoCallbackUrl' => self::callbackUrl(),
			'iyzicoWebhookUrl' => self::webhookUrl(),
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
		return trim((string) Settings::get('IYZICO_API_KEY')) !== ''
			&& trim((string) Settings::get('IYZICO_SECRET_KEY')) !== '';
	}

	public static function getFormClass(): string
	{
		return Settings::get('IYZICO_FORM_CLASS') === 'popup' ? 'popup' : 'responsive';
	}

	public static function getApiType(): string
	{
		return Settings::get('IYZICO_API_TYPE') === 'sandbox' ? 'sandbox' : 'live';
	}

	public static function getApiBaseUrl(): string
	{
		return self::getApiType() === 'sandbox' ? self::API_SANDBOX : self::API_LIVE;
	}

	public static function callbackUrl(): string
	{
		global $domain;

		return rtrim((string) $domain, '/') . '/iyzico-callback';
	}

	public static function webhookUrl(): string
	{
		global $domain;

		self::ensureWebhookKey();

		return rtrim((string) $domain, '/') . '/api/module.php?m=iyzico&action=webhook&key='
			. rawurlencode((string) Settings::get('IYZICO_WEBHOOK_KEY'));
	}

	public static function ensureWebhookKey(): void
	{
		if (trim((string) Settings::get('IYZICO_WEBHOOK_KEY')) !== '') {
			return;
		}

		Settings::set('IYZICO_WEBHOOK_KEY', bin2hex(random_bytes(16)));
	}

	/**
	 * @return array{success: bool, checkoutFormContent?: string, paymentPageUrl?: string, token?: string, message?: string}
	 */
	public function initializeCheckoutForm(array $order, array $pendingData, array $cart): array
	{
		if (!self::isConfigured()) {
			return ['success' => false, 'message' => 'iyzico yapılandırması eksik'];
		}

		$reference = trim((string) ($order['reference'] ?? ''));

		if ($reference === '') {
			return ['success' => false, 'message' => 'Sipariş referansı oluşturulamadı'];
		}

		$currency = strtoupper(class_exists('Currency', false) ? Currency::getShopCurrency() : 'try');

		if (!in_array($currency, self::CURRENCIES, true)) {
			return ['success' => false, 'message' => 'iyzico bu para birimini desteklemiyor: ' . $currency];
		}

		$paidPrice = round((float) ($order['total'] ?? 0), 2);

		if ($paidPrice <= 0) {
			return ['success' => false, 'message' => 'Geçersiz ödeme tutarı'];
		}

		$pendingData['_expected_total'] = $paidPrice;

		$basketItems = self::buildBasketItems($order, $cart, $paidPrice);
		$priceSum = 0.0;

		foreach ($basketItems as $item) {
			$priceSum += (float) $item['price'];
		}

		if ($basketItems === [] || $priceSum <= 0) {
			return ['success' => false, 'message' => 'Sepet iyzico için hazırlanamadı'];
		}

		$locale = self::resolveLocale();
		$buyer = self::buildBuyer($order);
		$address = self::buildAddress($order);

		$request = [
			'locale' => $locale,
			'conversationId' => $reference,
			'price' => self::formatPrice($priceSum),
			'basketId' => $reference,
			'paymentGroup' => 'PRODUCT',
			'buyer' => $buyer,
			'shippingAddress' => $address,
			'billingAddress' => $address,
			'basketItems' => $basketItems,
			'callbackUrl' => self::callbackUrl(),
			'paymentSource' => 'FSHOP|PIE|' . $this->version,
			'currency' => $currency,
			'paidPrice' => self::formatPrice($paidPrice),
		];

		if (Settings::get('IYZICO_NO_INSTALLMENT') === '1') {
			$request['enabledInstallments'] = [1];
		}

		$response = self::apiPost('/payment/iyzipos/checkoutform/initialize/auth/ecom', $request);

		if ($response === null) {
			return ['success' => false, 'message' => 'iyzico bağlantı hatası'];
		}

		if (($response['status'] ?? '') !== 'success') {
			return [
				'success' => false,
				'message' => 'iyzico: ' . (string) ($response['errorMessage'] ?? 'Ödeme formu oluşturulamadı'),
			];
		}

		$token = (string) ($response['token'] ?? '');
		self::persistPendingCheckout($reference, $pendingData, $cart, $token);

		return [
			'success' => true,
			'token' => $token,
			'checkoutFormContent' => (string) ($response['checkoutFormContent'] ?? ''),
			'paymentPageUrl' => (string) ($response['paymentPageUrl'] ?? ''),
		];
	}

	public static function handleCallback(): void
	{
		$token = trim((string) Tools::getValue('token'));

		if ($token === '') {
			$token = trim((string) ($_POST['token'] ?? $_GET['token'] ?? ''));
		}

		try {
			$result = self::completeByToken($token, false);
		} catch (Throwable $e) {
			error_log('iyzico callback: ' . $e->getMessage());
			$result = ['success' => false, 'message' => $e->getMessage()];
		}

		self::redirectCustomer($result);
	}

	public static function handleWebhook(): void
	{
		self::ensureWebhookKey();

		$expected = (string) Settings::get('IYZICO_WEBHOOK_KEY');
		$provided = trim((string) Tools::getValue('key'));

		if ($expected === '' || !hash_equals($expected, $provided)) {
			self::webhookRespond('invalid_webhook_key', 404);
		}

		$raw = (string) file_get_contents('php://input');
		$params = json_decode($raw, true);

		if (!is_array($params)) {
			$params = $_POST;
		}

		$token = trim((string) ($params['token'] ?? ''));
		$eventType = (string) ($params['iyziEventType'] ?? '');
		$paymentId = (string) ($params['iyziPaymentId'] ?? $params['paymentId'] ?? '');
		$conversationId = (string) ($params['paymentConversationId'] ?? '');
		$status = (string) ($params['status'] ?? '');
		$signature = self::requestHeader('x-iyz-signature-v3');

		if ($token === '') {
			self::webhookRespond('invalid_parameters', 404);
		}

		if ($signature === '') {
			self::webhookRespond('signature_not_valid', 404);
		}

		$secret = (string) Settings::get('IYZICO_SECRET_KEY');
		$key = $secret . $eventType . $paymentId . $token . $conversationId . $status;
		$calculated = bin2hex(hash_hmac('sha256', $key, $secret, true));

		if (!hash_equals($calculated, $signature)) {
			self::webhookRespond('signature_not_valid', 404);
		}

		$result = self::completeByToken($token, true);

		if (!empty($result['success'])) {
			self::webhookRespond((string) ($result['message'] ?? 'ok'), 200);
		}

		self::webhookRespond((string) ($result['message'] ?? 'payment_failed'), 404);
	}

	/**
	 * @return array{success: bool, reference?: string, message?: string}
	 */
	public static function completeByToken(string $token, bool $fromWebhook): array
	{
		$token = trim($token);

		if ($token === '') {
			return ['success' => false, 'message' => 'token_not_found'];
		}

		if (!self::isConfigured()) {
			return ['success' => false, 'message' => 'not_configured'];
		}

		$retrieve = self::apiPost('/payment/iyzipos/checkoutform/auth/ecom/detail/', [
			'locale' => self::resolveLocale(),
			'conversationId' => substr(md5($token), 0, 16),
			'token' => $token,
		]);

		if ($retrieve === null) {
			return ['success' => false, 'message' => 'iyzico_connection_error'];
		}

		$apiStatus = (string) ($retrieve['status'] ?? '');
		$paymentStatus = strtoupper((string) ($retrieve['paymentStatus'] ?? ''));
		$reference = trim((string) ($retrieve['basketId'] ?? ''));

		if ($reference === '') {
			$reference = trim((string) ($retrieve['conversationId'] ?? ''));
		}

		$pendingByToken = self::findPendingByToken($token);

		if ($pendingByToken && ($reference === '' || !self::loadPendingCheckout($reference))) {
			$reference = (string) ($pendingByToken['_pending_reference'] ?? $reference);
		}

		$paymentId = (string) ($retrieve['paymentId'] ?? '');
		$paidPrice = round((float) ($retrieve['paidPrice'] ?? 0), 2);
		$installment = max(1, (int) ($retrieve['installment'] ?? 1));

		if ($reference === '') {
			return ['success' => false, 'message' => 'order_not_found'];
		}

		self::lockReference($reference);

		try {
			self::recordIyziPayment($reference, $paymentId, $paidPrice, $installment, $paymentStatus, $token);

			if ($fromWebhook && $apiStatus === 'failure' && !in_array($paymentStatus, ['SUCCESS', 'PENDING_CREDIT', 'INIT_BANK_TRANSFER'], true)) {
				return ['success' => false, 'message' => (string) ($retrieve['errorMessage'] ?? 'payment_failure')];
			}

			$acceptPendingCredit = $paymentStatus === 'PENDING_CREDIT' && $apiStatus === 'success';
			$acceptBankTransfer = $paymentStatus === 'INIT_BANK_TRANSFER' && $apiStatus === 'success';
			$acceptSuccess = $paymentStatus === 'SUCCESS' && $apiStatus === 'success';

			if (!$acceptSuccess && !$acceptPendingCredit && !$acceptBankTransfer) {
				return [
					'success' => false,
					'message' => (string) ($retrieve['errorMessage'] ?? 'payment_not_success'),
				];
			}

			$pending = self::loadPendingCheckout($reference);
			$existing = DB::getRowSafe('orders', 'reference = ?', [$reference]);
			$expected = self::expectedAmount($existing, $pending);

			if ($acceptSuccess && $expected > 0 && ($paidPrice + 0.05) < $expected) {
				if ($paymentId !== '') {
					self::cancelPayment($paymentId, $reference);
				}

				return ['success' => false, 'message' => 'basketItemsNotMatch'];
			}

			$markPaid = $acceptSuccess;

			if ($existing) {
				self::attachPaymentToOrder((int) $existing['id_order'], $paymentId, $reference);

				if ($markPaid) {
					self::markOrderPaid($existing, $paidPrice);
				}

				self::deletePendingCheckout($reference);

				return ['success' => true, 'reference' => $reference, 'message' => 'order_exists'];
			}

			if (!$pending) {
				return ['success' => false, 'message' => 'pending_checkout_not_found'];
			}

			$idOrder = self::placeFromPending($reference, $pending);

			if ($idOrder <= 0) {
				return ['success' => false, 'message' => 'order_create_failed'];
			}

			self::attachPaymentToOrder($idOrder, $paymentId, $reference);
			$created = DB::getRowSafe('orders', 'id_order = ?', [$idOrder]);

			if ($created && $markPaid) {
				self::markOrderPaid($created, $paidPrice);
			}

			self::deletePendingCheckout($reference);

			return [
				'success' => true,
				'reference' => $reference,
				'message' => $fromWebhook ? 'order_created_by_webhook' : 'order_created',
			];
		} finally {
			self::unlockReference($reference);
		}
	}

	public static function ensurePendingStorage(): void
	{
		static $ready = false;

		if ($ready) {
			return;
		}

		$ready = true;

		if (empty(DB::execute("SHOW TABLES LIKE 'iyzico_pending_checkouts'"))) {
			DB::execute(
				"CREATE TABLE `iyzico_pending_checkouts` (
					`reference` varchar(32) NOT NULL,
					`token` varchar(128) NOT NULL DEFAULT '',
					`payload` longtext NOT NULL,
					`date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (`reference`),
					KEY `token` (`token`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		}

		if (empty(DB::execute("SHOW TABLES LIKE 'iyzico_orders'"))) {
			DB::execute(
				"CREATE TABLE `iyzico_orders` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`payment_id` varchar(64) NOT NULL DEFAULT '',
					`id_order` int(11) NOT NULL DEFAULT 0,
					`reference` varchar(32) NOT NULL DEFAULT '',
					`total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
					`installment` int(11) NOT NULL DEFAULT 1,
					`status` varchar(32) NOT NULL DEFAULT '',
					`token` varchar(128) NOT NULL DEFAULT '',
					`date_add` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (`id`),
					KEY `id_order` (`id_order`),
					KEY `reference` (`reference`),
					KEY `payment_id` (`payment_id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
			);
		}
	}

	public static function persistPendingCheckout(string $reference, array $checkoutData, array $cart, string $token = ''): void
	{
		self::ensurePendingStorage();

		$payload = json_encode([
			'checkout' => $checkoutData,
			'cart' => $cart,
			'coupon_code' => (string) ($_SESSION[Coupon::SESSION_KEY] ?? ''),
			'id_user' => Customer::getId(),
			'expected_total' => self::payloadExpectedTotal($checkoutData, $cart),
		], JSON_UNESCAPED_UNICODE);

		$existingToken = (string) DB::getValue(
			'SELECT token FROM iyzico_pending_checkouts WHERE reference = ? LIMIT 1',
			[$reference]
		);

		if ($token === '') {
			$token = $existingToken;
		}

		DB::execute(
			'INSERT INTO iyzico_pending_checkouts (reference, token, payload) VALUES (?, ?, ?)
			 ON DUPLICATE KEY UPDATE token = VALUES(token), payload = VALUES(payload), date_add = CURRENT_TIMESTAMP',
			[$reference, $token, $payload]
		);
	}

	/** @return array{checkout: array, cart: array, coupon_code: string, id_user: int, expected_total?: float}|null */
	public static function loadPendingCheckout(string $reference): ?array
	{
		self::ensurePendingStorage();

		$row = DB::getRowSafe('iyzico_pending_checkouts', 'reference = ?', [$reference]);

		if (!$row || empty($row['payload'])) {
			return null;
		}

		$data = json_decode((string) $row['payload'], true);

		return is_array($data) ? $data : null;
	}

	public static function deletePendingCheckout(string $reference): void
	{
		self::ensurePendingStorage();
		DB::execute('DELETE FROM iyzico_pending_checkouts WHERE reference = ?', [$reference]);
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
		$taxNumber = trim((string) ($pendingData['tax_number'] ?? ''));
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
				$taxNumber = trim((string) ($savedAddress['tax_number'] ?? $taxNumber));
			}
		}

		if ($idUser > 0 && $customerEmail === '') {
			$current = Customer::getCurrent();
			$customerEmail = strtolower(trim((string) ($current['email'] ?? '')));
		}

		$subtotal = (float) $cart['total'];
		$summary = Coupon::getCheckoutSummary($subtotal, $cart);
		$items = [];

		foreach ($cart['items'] as $item) {
			$lineTotal = (float) $item['line_total'];
			$items[] = [
				'id_product' => (int) ($item['id_product'] ?? 0),
				'id_category' => (int) ($item['id_category'] ?? 0),
				'product_name' => (string) $item['product_name'],
				'price' => (float) $item['price'],
				'qty' => (float) $item['qty'],
				'total' => $lineTotal,
				'total_formatted' => Tools::displayPrice($lineTotal),
			];
		}

		$reference = (string) ($pendingData['_iyzico_reference'] ?? '');

		return [
			'id_order' => 0,
			'reference' => $reference,
			'id_user' => $idUser,
			'customer_name' => $name,
			'customer_phone' => $phone,
			'customer_email' => $customerEmail,
			'tax_number' => $taxNumber,
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

	private static function payloadExpectedTotal(array $checkoutData, array $cart): float
	{
		$fromCheckout = (float) ($checkoutData['_expected_total'] ?? 0);

		if ($fromCheckout > 0) {
			return round($fromCheckout, 2);
		}

		if ($cart !== [] && empty($cart['empty'])) {
			$summary = Coupon::getCheckoutSummary((float) ($cart['total'] ?? 0), $cart);

			return round((float) $summary['total'], 2);
		}

		return 0.0;
	}

	private static function placeFromPending(string $reference, array $pending): int
	{
		$checkout = is_array($pending['checkout'] ?? null) ? $pending['checkout'] : [];
		$cart = is_array($pending['cart'] ?? null) ? $pending['cart'] : [];

		if ($cart === [] || !empty($cart['empty'])) {
			error_log('iyzico: empty cart snapshot for ' . $reference);

			return 0;
		}

		$checkout['_payment_done'] = 1;
		$checkout['_reference'] = $reference;
		$checkout['_cart_snapshot'] = $cart;
		$checkout['_stored_id_user'] = (int) ($pending['id_user'] ?? 0);
		$checkout['_stored_coupon_code'] = (string) ($pending['coupon_code'] ?? '');

		$result = Order::place($checkout);

		if (empty($result['success'])) {
			error_log('iyzico: order create failed for ' . $reference . ' — ' . ($result['message'] ?? ''));

			return 0;
		}

		return (int) ($result['id_order'] ?? 0);
	}

	private static function markOrderPaid(array $order, float $paidPrice): void
	{
		$expected = round((float) ($order['total'] ?? 0), 2);

		if ($paidPrice + 0.05 < $expected) {
			error_log('iyzico amount mismatch for order ' . ($order['reference'] ?? '')
				. ' expected=' . $expected . ' paid=' . $paidPrice);

			return;
		}

		if ((int) $order['status'] !== Order::STATUS_PENDING) {
			return;
		}

		Order::updateStatus((int) $order['id_order'], Order::STATUS_PROCESSING);
	}

	private static function expectedAmount(?array $order, ?array $pending): float
	{
		if (is_array($order)) {
			return round((float) ($order['total'] ?? 0), 2);
		}

		if (is_array($pending)) {
			$fromPayload = (float) ($pending['expected_total'] ?? 0);

			if ($fromPayload > 0) {
				return round($fromPayload, 2);
			}

			$checkout = is_array($pending['checkout'] ?? null) ? $pending['checkout'] : [];
			$cart = is_array($pending['cart'] ?? null) ? $pending['cart'] : [];

			if ($cart !== [] && empty($cart['empty'])) {
				$summary = Coupon::getCheckoutSummary((float) ($cart['total'] ?? 0), $cart);

				return round((float) $summary['total'], 2);
			}

			if (isset($checkout['total'])) {
				return round((float) $checkout['total'], 2);
			}
		}

		return 0.0;
	}

	/** @return array<int, array<string, string>> */
	private static function buildBasketItems(array $order, array $cart, float $paidPrice): array
	{
		$items = [];

		foreach ($order['items'] ?? [] as $item) {
			$line = round((float) ($item['total'] ?? $item['line_total'] ?? 0), 2);

			if ($line <= 0) {
				continue;
			}

			$category = self::categoryName((int) ($item['id_category'] ?? 0), $cart);
			$items[] = [
				'id' => (string) max(1, (int) ($item['id_product'] ?? 0)),
				'price' => self::formatPrice($line),
				'name' => mb_substr((string) ($item['product_name'] ?? 'Ürün'), 0, 200),
				'category1' => $category,
				'itemType' => 'PHYSICAL',
			];
		}

		$shipping = round((float) ($order['shipping'] ?? 0), 2);

		if ($shipping > 0) {
			$items[] = [
				'id' => 'shipping',
				'price' => self::formatPrice($shipping),
				'name' => 'Kargo',
				'category1' => 'Kargo',
				'itemType' => 'PHYSICAL',
			];
		}

		$sum = 0.0;

		foreach ($items as $item) {
			$sum += (float) $item['price'];
		}

		if ($items === [] || $sum <= 0) {
			return [[
				'id' => 'order',
				'price' => self::formatPrice($paidPrice),
				'name' => 'Sipariş',
				'category1' => 'Genel',
				'itemType' => 'PHYSICAL',
			]];
		}

		if (abs($sum - $paidPrice) < 0.005) {
			return $items;
		}

		$scale = $paidPrice / $sum;
		$running = 0.0;
		$last = count($items) - 1;

		foreach ($items as $i => &$item) {
			if ($i === $last) {
				$rest = round($paidPrice - $running, 2);
				$item['price'] = self::formatPrice(max(0.01, $rest));
				break;
			}

			$scaled = round((float) $item['price'] * $scale, 2);
			$scaled = max(0.01, $scaled);
			$item['price'] = self::formatPrice($scaled);
			$running += $scaled;
		}
		unset($item);

		return $items;
	}

	private static function categoryName(int $idCategory, array $cart): string
	{
		if ($idCategory <= 0) {
			foreach ($cart['items'] ?? [] as $item) {
				$idCategory = (int) ($item['id_category'] ?? 0);

				if ($idCategory > 0) {
					break;
				}
			}
		}

		if ($idCategory > 0) {
			$name = trim((string) DB::getValue(
				'SELECT category_name FROM categories WHERE id_category = ? LIMIT 1',
				[$idCategory]
			));

			if ($name !== '') {
				return mb_substr($name, 0, 120);
			}
		}

		return 'Genel';
	}

	/** @return array<string, string> */
	private static function buildBuyer(array $order): array
	{
		$fullName = trim((string) ($order['customer_name'] ?? ''));
		$parts = preg_split('/\s+/', $fullName) ?: [];
		$first = (string) ($parts[0] ?? '');
		$last = trim(implode(' ', array_slice($parts, 1)));

		if ($first === '') {
			$first = 'Musteri';
		}

		if ($last === '') {
			$last = 'Soyad';
		}

		$email = self::resolveCustomerEmail($order);
		$identity = preg_replace('/\D+/', '', (string) ($order['tax_number'] ?? '')) ?: '';

		if (strlen($identity) !== 11) {
			$identity = '11111111111';
		}

		$now = date('Y-m-d H:i:s');
		$address = self::formatAddress($order);

		$idUser = (int) ($order['id_user'] ?? 0);
		$buyerId = $idUser > 0 ? (string) $idUser : (string) sprintf('%u', crc32($email));

		return [
			'id' => $buyerId,
			'name' => mb_substr($first, 0, 50),
			'surname' => mb_substr($last, 0, 50),
			'identityNumber' => $identity,
			'email' => $email,
			'gsmNumber' => self::formatGsm((string) ($order['customer_phone'] ?? '')),
			'registrationDate' => $now,
			'lastLoginDate' => $now,
			'registrationAddress' => $address,
			'city' => mb_substr(trim((string) ($order['address_city'] ?? '')) ?: 'Istanbul', 0, 50),
			'country' => 'Turkey',
			'zipCode' => '34000',
			'ip' => self::getClientIp(),
		];
	}

	/** @return array<string, string> */
	private static function buildAddress(array $order): array
	{
		$name = trim((string) ($order['customer_name'] ?? '')) ?: 'Musteri';

		return [
			'address' => self::formatAddress($order),
			'zipCode' => '34000',
			'contactName' => mb_substr($name, 0, 50),
			'city' => mb_substr(trim((string) ($order['address_city'] ?? '')) ?: 'Istanbul', 0, 50),
			'country' => 'Turkey',
		];
	}

	private static function formatAddress(array $order): string
	{
		$parts = array_filter([
			trim((string) ($order['address_text'] ?? '')),
			trim((string) ($order['address_district'] ?? '')),
			trim((string) ($order['address_city'] ?? '')),
		]);

		$address = implode(' ', $parts);

		return $address !== '' ? mb_substr($address, 0, 200) : 'Adres belirtilmedi';
	}

	private static function formatGsm(string $phone): string
	{
		$digits = preg_replace('/\D+/', '', $phone) ?: '';

		if ($digits === '') {
			return '905555555555';
		}

		if (strpos($digits, '00') === 0) {
			$digits = substr($digits, 2);
		}

		if (strlen($digits) === 11 && $digits[0] === '0') {
			$digits = '90' . substr($digits, 1);
		} elseif (strlen($digits) === 10) {
			$digits = '90' . $digits;
		}

		if (strlen($digits) < 10) {
			return '905555555555';
		}

		return $digits;
	}

	private static function resolveCustomerEmail(array $order): string
	{
		$email = strtolower(trim((string) ($order['customer_email'] ?? '')));

		if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return $email;
		}

		$idUser = (int) ($order['id_user'] ?? 0);
		$email = strtolower(trim((string) DB::getValue('SELECT email FROM users WHERE id_user = ? LIMIT 1', [$idUser])));

		if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return $email;
		}

		$host = parse_url((string) Settings::get('DOMAIN'), PHP_URL_HOST) ?: 'fshop.local';

		return 'musteri' . max(0, $idUser) . '@' . $host;
	}

	private static function resolveLocale(): string
	{
		$setting = strtolower(trim((string) Settings::get('IYZICO_LANGUAGE')));

		if ($setting === 'tr' || $setting === 'en') {
			return $setting;
		}

		$current = class_exists('Lang', false) ? strtolower(Lang::current()) : 'tr';

		return $current === 'tr' ? 'tr' : 'en';
	}

	private static function formatPrice($price): string
	{
		$price = (string) $price;

		if (strpos($price, '.') === false) {
			return $price . '.0';
		}

		$subStrIndex = 0;
		$priceReversed = strrev($price);

		for ($i = 0, $len = strlen($priceReversed); $i < $len; $i++) {
			if ($priceReversed[$i] === '0') {
				$subStrIndex = $i + 1;
			} elseif ($priceReversed[$i] === '.') {
				$priceReversed = '0' . $priceReversed;
				break;
			} else {
				break;
			}
		}

		return strrev(substr($priceReversed, $subStrIndex));
	}

	private static function recordIyziPayment(
		string $reference,
		string $paymentId,
		float $paidPrice,
		int $installment,
		string $status,
		string $token
	): void {
		self::ensurePendingStorage();

		DB::insert('iyzico_orders', [
			'payment_id' => mb_substr($paymentId, 0, 64),
			'id_order' => 0,
			'reference' => mb_substr($reference, 0, 32),
			'total_amount' => $paidPrice,
			'installment' => $installment,
			'status' => mb_substr($status, 0, 32),
			'token' => mb_substr($token, 0, 128),
		]);
	}

	private static function attachPaymentToOrder(int $idOrder, string $paymentId, string $reference): void
	{
		self::ensurePendingStorage();

		if ($idOrder <= 0) {
			return;
		}

		DB::execute(
			'UPDATE iyzico_orders SET id_order = ? WHERE reference = ? AND id_order = 0',
			[$idOrder, $reference]
		);

		if ($paymentId !== '') {
			DB::execute(
				'UPDATE iyzico_orders SET id_order = ? WHERE payment_id = ? AND id_order = 0',
				[$idOrder, $paymentId]
			);
		}
	}

	private static function cancelPayment(string $paymentId, string $reference): void
	{
		self::apiPost('/payment/cancel', [
			'locale' => self::resolveLocale(),
			'conversationId' => $reference,
			'paymentId' => $paymentId,
			'ip' => self::getClientIp(),
		]);
	}

	/** @param array<string, mixed> $request */
	private static function apiPost(string $uri, array $request): ?array
	{
		if (!function_exists('curl_init')) {
			return null;
		}

		$json = json_encode($request);
		$rnd = uniqid();
		$apiKey = trim((string) Settings::get('IYZICO_API_KEY'));
		$secretKey = trim((string) Settings::get('IYZICO_SECRET_KEY'));
		$payload = $uri . $json;
		$hmac = bin2hex(hash_hmac('sha256', $rnd . $payload, $secretKey, true));
		$authV2 = 'IYZWSv2 ' . base64_encode('apiKey:' . $apiKey . '&randomKey:' . $rnd . '&signature:' . $hmac);
		$pki = self::toPkiString($request);
		$authV1 = sprintf(
			'IYZWS %s:%s',
			$apiKey,
			base64_encode(sha1($apiKey . $rnd . $secretKey . $pki, true))
		);

		$headers = [
			'Accept: application/json',
			'Content-type: application/json',
			'Authorization: ' . $authV2,
			'AUTHORIZATION_FALLBACK_HEADER: ' . $authV1,
			'x-iyzi-rnd: ' . $rnd,
			'x-iyzi-client-version: fshop-iyzico-1.0.0',
		];

		$ch = curl_init(self::getApiBaseUrl() . $uri);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);

		$cacert = dirname(__DIR__, 2) . '/core/cacert.pem';

		if (is_file($cacert)) {
			curl_setopt($ch, CURLOPT_CAINFO, $cacert);
		}

		$result = curl_exec($ch);

		if (curl_errno($ch)) {
			error_log('iyzico curl error: ' . curl_error($ch));
			curl_close($ch);

			return null;
		}

		curl_close($ch);

		if (!is_string($result) || $result === '') {
			return null;
		}

		$decoded = json_decode($result, true);

		return is_array($decoded) ? $decoded : null;
	}

	/** @param array<string, mixed> $value */
	private static function toPkiString($value): string
	{
		if (!is_array($value)) {
			return (string) $value;
		}

		if (self::isList($value)) {
			$parts = [];

			foreach ($value as $item) {
				$parts[] = is_array($item) ? self::toPkiString($item) : (string) $item;
			}

			return '[' . implode(', ', $parts) . ']';
		}

		$inner = '';

		foreach ($value as $key => $item) {
			$inner .= $key . '=' . (is_array($item) ? self::toPkiString($item) : (string) $item) . ',';
		}

		return '[' . rtrim($inner, ',') . ']';
	}

	/** @param array<mixed> $array */
	private static function isList(array $array): bool
	{
		if ($array === []) {
			return true;
		}

		return array_keys($array) === range(0, count($array) - 1);
	}

	private static function lockReference(string $reference): void
	{
		DB::getValue('SELECT GET_LOCK(?, 10)', ['iyzico_' . $reference]);
	}

	private static function unlockReference(string $reference): void
	{
		DB::getValue('SELECT RELEASE_LOCK(?)', ['iyzico_' . $reference]);
	}

	private static function requestHeader(string $name): string
	{
		$want = strtolower($name);

		foreach ($_SERVER as $key => $value) {
			if (strpos($key, 'HTTP_') !== 0) {
				continue;
			}

			$header = strtolower(str_replace('_', '-', substr($key, 5)));

			if ($header === $want) {
				return trim((string) $value);
			}
		}

		if (function_exists('getallheaders')) {
			$headers = getallheaders();

			if (is_array($headers)) {
				foreach ($headers as $key => $value) {
					if (strtolower((string) $key) === $want) {
						return trim((string) $value);
					}
				}
			}
		}

		return '';
	}

	private static function findPendingByToken(string $token): ?array
	{
		self::ensurePendingStorage();

		$token = trim($token);

		if ($token === '') {
			return null;
		}

		$row = DB::getRowSafe('iyzico_pending_checkouts', 'token = ?', [$token]);

		if (!$row || empty($row['payload'])) {
			return null;
		}

		$data = json_decode((string) $row['payload'], true);

		if (!is_array($data)) {
			return null;
		}

		$data['_pending_reference'] = (string) ($row['reference'] ?? '');

		return $data['_pending_reference'] !== '' ? $data : null;
	}

	private static function shopBaseUrl(): string
	{
		global $domain;

		$base = rtrim((string) $domain, '/');

		if ($base === '') {
			$base = rtrim((string) Settings::get('DOMAIN'), '/');
		}

		return $base;
	}

	/** @param array{success?: bool, reference?: string, message?: string} $result */
	private static function redirectCustomer(array $result): void
	{
		$base = self::shopBaseUrl();
		$ok = !empty($result['success']) && !empty($result['reference']);

		if ($ok) {
			$target = $base . '/checkout-success?ref=' . rawurlencode((string) $result['reference']);
		} else {
			$failPath = Order::hasPendingPayment() ? '/iyzico-payment?fail=1' : '/checkout';
			$target = $base . $failPath;
		}

		while (ob_get_level() > 0) {
			ob_end_clean();
		}

		if (!headers_sent()) {
			header('Cache-Control: no-store, no-cache, must-revalidate');
			header('Location: ' . $target, true, 303);
		}

		$safe = htmlspecialchars($target, ENT_QUOTES, 'UTF-8');
		$js = Security::jsonForHtmlScript($target);

		echo '<!DOCTYPE html><html lang="tr"><head><meta charset="utf-8">'
			. '<meta http-equiv="refresh" content="0;url=' . $safe . '">'
			. '<title>Yönlendiriliyor</title>'
			. '<script>(function(){var u=' . $js . ';'
			. 'try{if(window.top&&window.top!==window){window.top.location.replace(u);return;}}catch(e){}'
			. 'window.location.replace(u);})();</script>'
			. '</head><body style="font-family:sans-serif;text-align:center;padding:3rem">'
			. '<p>Ödemeniz işleniyor, lütfen bekleyin…</p>'
			. '<p><a href="' . $safe . '">Devam et</a></p>'
			. '</body></html>';
		exit;
	}

	private static function webhookRespond(string $message, int $status): void
	{
		http_response_code($status);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(['message' => $message], JSON_UNESCAPED_UNICODE);
		exit;
	}

	private static function getClientIp(): string
	{
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			return (string) $_SERVER['HTTP_CLIENT_IP'];
		}

		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ips = explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']);

			return trim($ips[0]);
		}

		return (string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
	}
}
