<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';

class EsnekposModule extends ModuleBase
{
	public string $name = 'esnekpos';
	public string $title = 'EsnekPOS';
	public string $version = '1.0.0';
	public string $description = 'EsnekPOS 3D Secure ile kredi/banka kartı ödemesi';
	public string $author = 'FShop';

	public bool $isPayment = true;
	public bool $paysBeforeOrder = true;
	public string $paymentMethodId = 'esnekpos';
	public string $paymentMethodLabel = 'Kredi / Banka Kartı (EsnekPOS)';

	public array $routes = [
		'esnekpos-payment' => 'front/payment.php',
	];

	public array $displayHooks = [
		'order_payment' => 'Checkout ödeme seçeneği',
	];

	public array $defaultDisplayHooks = ['order_payment'];

	public array $frontStylesheets = ['esnekpos.css'];
	public array $frontScripts = ['esnekpos-payment.js'];

	public array $apiActions = [
		'callback' => 'api/callback.php',
	];

	public function install(): bool
	{
		self::ensurePendingStorage();

		return true;
	}

	public function boot(): void
	{
		self::ensurePendingStorage();
	}

	public function uninstall(): bool
	{
		DB::execute('DROP TABLE IF EXISTS esnekpos_pending_checkouts');

		return true;
	}

	public function getPaymentPageUrl(): string
	{
		global $domain;

		return rtrim($domain, '/') . '/esnekpos-payment';
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken, $domain;

		$flash = '';

		if (Tools::isSubmit('saveEsnekpos')) {
			$postToken = (string) Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				Settings::set('ESNEKPOS_MERCHANT', trim((string) Tools::getValue('merchant')));
				Settings::set('ESNEKPOS_MERCHANT_KEY', trim((string) Tools::getValue('merchant_key')));
				Settings::set('ESNEKPOS_API_URL', rtrim(trim((string) Tools::getValue('api_url')), '/'));
				Settings::set('ESNEKPOS_TEST_MODE', Tools::getValue('test_mode') ? '1' : '0');
				$flash = 'EsnekPOS ayarları kaydedildi';
			} else {
				$flash = 'Geçersiz istek';
			}
		}

		$smarty->assign([
			'esnekposMerchant' => Settings::get('ESNEKPOS_MERCHANT'),
			'esnekposMerchantKey' => Settings::get('ESNEKPOS_MERCHANT_KEY'),
			'esnekposApiUrl' => Settings::get('ESNEKPOS_API_URL') ?: 'https://posservicetest.esnekpos.com',
			'esnekposTestMode' => Settings::get('ESNEKPOS_TEST_MODE') !== '0',
			'esnekposCallbackUrl' => rtrim($domain, '/') . '/api/module.php?m=esnekpos&action=callback',
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
		return trim((string) Settings::get('ESNEKPOS_MERCHANT')) !== ''
			&& trim((string) Settings::get('ESNEKPOS_MERCHANT_KEY')) !== '';
	}

	public static function getApiBaseUrl(): string
	{
		$url = trim((string) Settings::get('ESNEKPOS_API_URL'));

		if ($url === '') {
			$url = Settings::get('ESNEKPOS_TEST_MODE') !== '0'
				? 'https://posservicetest.esnekpos.com'
				: 'https://posservice.esnekpos.com';
		}

		return rtrim($url, '/');
	}

	public static function getCallbackUrl(): string
	{
		global $domain;

		return rtrim($domain, '/') . '/api/module.php?m=esnekpos&action=callback';
	}

	/** @return array{success: bool, url_3ds?: string, message?: string} */
	public function initiate3DPayment(array $order, array $card): array
	{
		if (!self::isConfigured()) {
			return ['success' => false, 'message' => 'EsnekPOS yapılandırması eksik'];
		}

		$merchant = trim((string) Settings::get('ESNEKPOS_MERCHANT'));
		$merchantKey = trim((string) Settings::get('ESNEKPOS_MERCHANT_KEY'));
		$reference = (string) $order['reference'];
		$amount = self::formatAmount((float) $order['total']);
		$installment = max(1, (int) ($card['installment'] ?? 1));
		$expYear = (int) ($card['exp_year'] ?? 0);

		if ($expYear < 100) {
			$expYear += 2000;
		}

		[$firstName, $lastName] = self::splitName((string) $order['customer_name']);

		$payload = [
			'Config' => [
				'MERCHANT' => $merchant,
				'MERCHANT_KEY' => $merchantKey,
				'BACK_URL' => self::getCallbackUrl(),
				'PRICES_CURRENCY' => 'TRY',
				'ORDER_REF_NUMBER' => $reference,
				'ORDER_AMOUNT' => $amount,
			],
			'CreditCard' => [
				'CC_NUMBER' => (string) $card['number'],
				'EXP_MONTH' => str_pad((string) (int) ($card['exp_month'] ?? 0), 2, '0', STR_PAD_LEFT),
				'EXP_YEAR' => (string) $expYear,
				'CC_CVV' => (string) $card['cvv'],
				'CC_OWNER' => (string) $card['holder'],
				'INSTALLMENT_NUMBER' => (string) $installment,
			],
			'Customer' => [
				'FIRST_NAME' => $firstName,
				'LAST_NAME' => $lastName,
				'MAIL' => self::resolveCustomerEmail($order),
				'PHONE' => self::normalizePhone((string) $order['customer_phone']),
				'CITY' => (string) ($order['address_city'] ?? 'Istanbul'),
				'STATE' => (string) ($order['address_district'] ?? 'Merkez'),
				'ADDRESS' => self::formatAddress($order),
				'CLIENT_IP' => self::getClientIp(),
			],
			'Product' => self::buildProducts($order),
		];

		$response = self::httpPostJson(self::getApiBaseUrl() . '/api/pay/EYV3DPay', $payload);

		if ($response === null) {
			return ['success' => false, 'message' => 'EsnekPOS bağlantı hatası'];
		}

		if (self::isSuccessResponse($response) && !empty($response['URL_3DS'])) {
			return [
				'success' => true,
				'url_3ds' => (string) $response['URL_3DS'],
			];
		}

		return [
			'success' => false,
			'message' => self::formatErrorMessage($response),
		];
	}

	/** @return array<string, mixed>|null */
	public static function processQuery(string $orderRefNumber): ?array
	{
		if (!self::isConfigured() || $orderRefNumber === '') {
			return null;
		}

		$payload = [
			'MERCHANT' => trim((string) Settings::get('ESNEKPOS_MERCHANT')),
			'MERCHANT_KEY' => trim((string) Settings::get('ESNEKPOS_MERCHANT_KEY')),
			'ORDER_REF_NUMBER' => $orderRefNumber,
		];

		return self::httpPostJson(self::getApiBaseUrl() . '/api/services/ProcessQuery', $payload);
	}

	public static function handleNotification(array $post): void
	{
		global $domain;

		$orderRef = trim((string) ($post['ORDER_REF_NUMBER'] ?? ''));

		if ($orderRef === '') {
			self::redirectAfterPayment(false, 'Sipariş referansı alınamadı');

			return;
		}

		$query = self::processQuery($orderRef);

		if ($query === null) {
			self::redirectAfterPayment(false, 'Ödeme doğrulaması yapılamadı', $orderRef);

			return;
		}

		if (!self::isPaymentSuccessful($query)) {
			$message = self::formatErrorMessage($query);
			self::redirectAfterPayment(false, $message, $orderRef);

			return;
		}

		$amount = self::parseAmount((string) ($query['AMOUNT'] ?? ($post['AMOUNT'] ?? '0')));
		self::completeOrderAfterPayment($orderRef, $amount);
		Order::clearPendingPayment();
		self::redirectAfterPayment(true, '', $orderRef);
	}

	public static function ensurePendingStorage(): void
	{
		static $ready = false;

		if ($ready) {
			return;
		}

		$ready = true;

		DB::execute(
			'CREATE TABLE IF NOT EXISTS esnekpos_pending_checkouts (
				reference VARCHAR(24) NOT NULL,
				payload LONGTEXT NOT NULL,
				date_add DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (reference)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
		);
	}

	public static function persistPendingCheckout(string $reference, array $checkoutData, array $cart): void
	{
		self::ensurePendingStorage();

		$payload = json_encode([
			'checkout' => $checkoutData,
			'cart' => $cart,
			'coupon_code' => (string) ($_SESSION[Coupon::SESSION_KEY] ?? ''),
			'id_user' => Customer::getId(),
		], JSON_UNESCAPED_UNICODE);

		DB::execute(
			'INSERT INTO esnekpos_pending_checkouts (reference, payload) VALUES (?, ?)
			 ON DUPLICATE KEY UPDATE payload = VALUES(payload), date_add = CURRENT_TIMESTAMP',
			[$reference, $payload]
		);
	}

	/** @return array{checkout: array, cart: array, coupon_code: string, id_user: int}|null */
	public static function loadPendingCheckout(string $reference): ?array
	{
		self::ensurePendingStorage();

		$row = DB::getRowSafe('esnekpos_pending_checkouts', 'reference = ?', [$reference]);

		if (!$row || empty($row['payload'])) {
			return null;
		}

		$data = json_decode((string) $row['payload'], true);

		return is_array($data) ? $data : null;
	}

	public static function deletePendingCheckout(string $reference): void
	{
		self::ensurePendingStorage();
		DB::execute('DELETE FROM esnekpos_pending_checkouts WHERE reference = ?', [$reference]);
	}

	public static function completeOrderAfterPayment(string $reference, float $paidAmount): void
	{
		$order = DB::getRowSafe('orders', 'reference = ?', [$reference]);

		if ($order) {
			self::markOrderPaid($order, $paidAmount);

			return;
		}

		$pending = self::loadPendingCheckout($reference);

		if (!$pending) {
			error_log('EsnekPOS: pending checkout not found for ' . $reference);

			return;
		}

		$checkout = is_array($pending['checkout'] ?? null) ? $pending['checkout'] : [];
		$cart = is_array($pending['cart'] ?? null) ? $pending['cart'] : [];

		if ($cart === [] || !empty($cart['empty'])) {
			error_log('EsnekPOS: empty cart snapshot for ' . $reference);

			return;
		}

		$checkout['_payment_done'] = 1;
		$checkout['_reference'] = $reference;
		$checkout['_cart_snapshot'] = $cart;
		$checkout['_stored_id_user'] = (int) ($pending['id_user'] ?? 0);
		$checkout['_stored_coupon_code'] = (string) ($pending['coupon_code'] ?? '');

		$result = Order::place($checkout);

		if (!$result['success']) {
			error_log('EsnekPOS: order create failed for ' . $reference . ' — ' . ($result['message'] ?? ''));

			return;
		}

		$idOrder = (int) ($result['id_order'] ?? 0);

		if ($idOrder > 0) {
			$created = DB::getRowSafe('orders', 'id_order = ?', [$idOrder]);

			if ($created) {
				self::markOrderPaid($created, $paidAmount);
			}
		}

		self::deletePendingCheckout($reference);
	}

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

		$subtotal = (float) $cart['total'];
		$summary = Coupon::getCheckoutSummary($subtotal);
		$items = [];

		foreach ($cart['items'] as $item) {
			$lineTotal = (float) $item['line_total'];
			$items[] = [
				'product_name' => (string) $item['product_name'],
				'price' => (float) $item['price'],
				'qty' => (int) $item['qty'],
				'total' => $lineTotal,
				'line_total' => $lineTotal,
				'total_formatted' => Tools::displayPrice($lineTotal),
				'id_product' => (int) ($item['id_product'] ?? 0),
			];
		}

		$reference = (string) ($pendingData['_esnekpos_reference'] ?? '');

		return [
			'id_order' => 0,
			'reference' => $reference,
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

	public static function isValidCardNumber(string $number): bool
	{
		if (!preg_match('/^[0-9]{13,19}$/', $number)) {
			return false;
		}

		$sum = 0;
		$alt = false;

		for ($i = strlen($number) - 1; $i >= 0; $i--) {
			$digit = (int) $number[$i];

			if ($alt) {
				$digit *= 2;

				if ($digit > 9) {
					$digit -= 9;
				}
			}

			$sum += $digit;
			$alt = !$alt;
		}

		return $sum % 10 === 0;
	}

	public static function isValidExpiry(int $month, int $year): bool
	{
		if ($month < 1 || $month > 12) {
			return false;
		}

		if ($year < 100) {
			$year += 2000;
		}

		$now = (int) date('Y') * 100 + (int) date('n');

		return ($year * 100 + $month) >= $now;
	}

	/** @param array<string, mixed> $response */
	private static function isSuccessResponse(array $response): bool
	{
		return strtoupper((string) ($response['STATUS'] ?? '')) === 'SUCCESS'
			&& (string) ($response['RETURN_CODE'] ?? '') === '0';
	}

	/** @param array<string, mixed> $query */
	private static function isPaymentSuccessful(array $query): bool
	{
		if (!self::isSuccessResponse($query)) {
			return false;
		}

		$transactions = $query['TRANSACTIONS'] ?? [];

		if (!is_array($transactions) || $transactions === []) {
			return false;
		}

		foreach ($transactions as $transaction) {
			if (!is_array($transaction)) {
				continue;
			}

			$statusId = (int) ($transaction['STATUS_ID'] ?? 0);
			$statusName = (string) ($transaction['STATUS_NAME'] ?? '');

			if ($statusId === 3 || stripos($statusName, 'Ödeme - Başarılı') !== false) {
				return true;
			}
		}

		return false;
	}

	/** @param array<string, mixed> $order */
	private static function buildProducts(array $order): array
	{
		$products = [];
		$index = 1;
		$runningTotal = 0.0;

		foreach ($order['items'] ?? [] as $item) {
			$lineTotal = (float) ($item['total'] ?? $item['line_total'] ?? $item['price'] ?? 0);
			$products[] = [
				'PRODUCT_ID' => (string) ($item['id_product'] ?? $index),
				'PRODUCT_NAME' => mb_substr((string) ($item['product_name'] ?? 'Ürün'), 0, 120),
				'PRODUCT_CATEGORY' => 'Genel',
				'PRODUCT_DESCRIPTION' => mb_substr((string) ($item['product_name'] ?? 'Ürün'), 0, 255),
				'PRODUCT_AMOUNT' => self::formatAmount($lineTotal),
			];
			$runningTotal += $lineTotal;
			$index++;
		}

		if ((float) ($order['shipping'] ?? 0) > 0) {
			$shipping = (float) $order['shipping'];
			$products[] = [
				'PRODUCT_ID' => 'shipping',
				'PRODUCT_NAME' => 'Kargo',
				'PRODUCT_CATEGORY' => 'Kargo',
				'PRODUCT_DESCRIPTION' => 'Kargo ücreti',
				'PRODUCT_AMOUNT' => self::formatAmount($shipping),
			];
			$runningTotal += $shipping;
		}

		$discount = (float) ($order['coupon_discount'] ?? 0);

		if ($discount > 0 && $runningTotal > 0) {
			$ratio = max(0, ($runningTotal - $discount) / $runningTotal);

			foreach ($products as $i => $product) {
				$amount = (float) self::parseAmount((string) $product['PRODUCT_AMOUNT']) * $ratio;
				$products[$i]['PRODUCT_AMOUNT'] = self::formatAmount($amount);
			}

			$runningTotal -= $discount;
		}

		if ($products === []) {
			$orderTotal = (float) $order['total'];
			$products[] = [
				'PRODUCT_ID' => '1',
				'PRODUCT_NAME' => 'Sipariş',
				'PRODUCT_CATEGORY' => 'Genel',
				'PRODUCT_DESCRIPTION' => 'Online sipariş',
				'PRODUCT_AMOUNT' => self::formatAmount($orderTotal),
			];

			return $products;
		}

		$orderTotal = (float) $order['total'];
		$diff = round($orderTotal - $runningTotal, 2);

		if (abs($diff) >= 0.01) {
			$lastIndex = count($products) - 1;
			$adjusted = (float) self::parseAmount((string) $products[$lastIndex]['PRODUCT_AMOUNT']) + $diff;
			$products[$lastIndex]['PRODUCT_AMOUNT'] = self::formatAmount($adjusted);
		}

		return $products;
	}

	private static function formatAmount(float $amount): string
	{
		$amount = round($amount, 2);

		if (abs($amount - round($amount)) < 0.001) {
			return (string) (int) round($amount);
		}

		return number_format($amount, 2, '.', '');
	}

	private static function parseAmount(string $amount): float
	{
		$amount = trim($amount);

		if ($amount === '') {
			return 0.0;
		}

		if (strpos($amount, ',') !== false && strpos($amount, '.') !== false) {
			$amount = str_replace('.', '', $amount);
			$amount = str_replace(',', '.', $amount);
		} elseif (strpos($amount, ',') !== false) {
			$amount = str_replace(',', '.', $amount);
		}

		return (float) preg_replace('/[^\d\.\-]/', '', $amount);
	}

	/** @return array{0: string, 1: string} */
	private static function splitName(string $fullName): array
	{
		$fullName = trim(preg_replace('/\s+/', ' ', $fullName));

		if ($fullName === '') {
			return ['Musteri', 'Adi'];
		}

		$parts = explode(' ', $fullName);

		if (count($parts) === 1) {
			return [$parts[0], $parts[0]];
		}

		$lastName = array_pop($parts);

		return [implode(' ', $parts), $lastName];
	}

	private static function normalizePhone(string $phone): string
	{
		$phone = preg_replace('/\D+/', '', $phone);

		if ($phone === '') {
			return '05000000000';
		}

		if (strlen($phone) === 10) {
			return '0' . $phone;
		}

		return $phone;
	}

	private static function markOrderPaid(array $order, float $paidAmount): void
	{
		$expected = (float) $order['total'];

		if (abs($paidAmount - $expected) > 0.05) {
			error_log('EsnekPOS amount mismatch for order ' . $order['reference']
				. ' expected=' . $expected . ' paid=' . $paidAmount);

			return;
		}

		if ((int) $order['status'] !== Order::STATUS_PENDING) {
			return;
		}

		Order::updateStatus((int) $order['id_order'], Order::STATUS_PROCESSING);
	}

	private static function formatAddress(array $order): string
	{
		$parts = array_filter([
			trim((string) ($order['address_text'] ?? '')),
			trim((string) ($order['address_district'] ?? '')),
			trim((string) ($order['address_city'] ?? '')),
		]);

		return $parts !== [] ? implode(', ', $parts) : 'Türkiye';
	}

	private static function resolveCustomerEmail(array $order): string
	{
		$email = strtolower(trim((string) ($order['customer_email'] ?? '')));

		if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return $email;
		}

		$idUser = (int) ($order['id_user'] ?? 0);
		$email = trim((string) DB::getValue('SELECT email FROM users WHERE id_user = ? LIMIT 1', [$idUser]));

		if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return $email;
		}

		$domain = parse_url(Settings::get('DOMAIN') ?: '', PHP_URL_HOST) ?: 'fshop.local';

		return 'musteri' . max(0, $idUser) . '@' . $domain;
	}

	public static function getClientIp(): string
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

	/** @param array<string, mixed> $response */
	private static function formatErrorMessage(array $response): string
	{
		$message = trim((string) ($response['RETURN_MESSAGE_TR'] ?? ''));
		if ($message === '') {
			$message = trim((string) ($response['RETURN_MESSAGE'] ?? ''));
		}
		if ($message === '') {
			$message = 'Ödeme işlemi başarısız';
		}

		$errorCode = trim((string) ($response['ERROR_CODE'] ?? ''));

		if ($errorCode !== '') {
			$message .= ' (' . $errorCode . ')';
		}

		return 'EsnekPOS: ' . $message;
	}

	private static function redirectAfterPayment(bool $success, string $message = '', string $reference = ''): void
	{
		global $domain;

		if ($success) {
			$idOrder = (int) DB::getValue('SELECT id_order FROM orders WHERE reference = ? LIMIT 1', [$reference]);
			$target = $idOrder > 0
				? rtrim($domain, '/') . '/checkout-success?id=' . $idOrder
				: rtrim($domain, '/') . '/checkout-success?ref=' . rawurlencode($reference);

			header('Location: ' . $target);
			exit;
		}

		$target = rtrim($domain, '/') . '/esnekpos-payment?fail=1';

		if ($message !== '') {
			$_SESSION['esnekpos_payment_error'] = $message;
		}

		header('Location: ' . $target);
		exit;
	}

	/** @param array<string, mixed> $payload */
	private static function httpPostJson(string $url, array $payload): ?array
	{
		if (!function_exists('curl_init')) {
			return null;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

		$result = curl_exec($ch);

		if (curl_errno($ch)) {
			error_log('EsnekPOS curl error: ' . curl_error($ch));
			curl_close($ch);

			return null;
		}

		curl_close($ch);

		if (!is_string($result) || $result === '') {
			return null;
		}

		$data = json_decode($result, true);

		return is_array($data) ? $data : null;
	}
}
