<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';
require_once __DIR__ . '/lib/TamiClient.php';

/**
 * Tami (Garanti BBVA) 3D Secure sanal POS.
 *
 * Akış: checkout → kart formu → /payment/auth → 3D HTML → callback → /payment/complete-3ds → sipariş.
 */
class TamiModule extends ModuleBase
{
	public string $name = 'tami';
	public string $title = 'Tami Sanal POS';
	public string $version = '1.0.0';
	public string $description = 'Tami 3D Secure kredi/banka kartı ödemesi';
	public string $author = 'FShop';

	public bool $isPayment = true;
	public bool $paysBeforeOrder = true;
	public string $paymentMethodId = 'tami';
	public string $paymentMethodLabel = 'Kredi / Banka Kartı (Tami)';

	public array $routes = [
		'tami-payment' => 'front/payment.php',
	];

	public array $displayHooks = [
		'order_payment' => 'Checkout ödeme seçeneği',
	];

	public array $defaultDisplayHooks = ['order_payment'];

	public array $apiActions = [
		'callback' => 'api/callback.php',
	];

	public array $adminStylesheets = [];
	public array $frontStylesheets = [];

	public function install(): bool
	{
		self::ensurePendingStorage();

		return true;
	}

	public function uninstall(): bool
	{
		DB::execute('DROP TABLE IF EXISTS tami_pending_checkouts');

		return true;
	}

	public function getPaymentPageUrl(): string
	{
		global $domain;

		return rtrim((string) $domain, '/') . '/tami-payment';
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken, $domain;

		$flash = '';

		if (Tools::isSubmit('saveTami') && hash_equals($adminToken, (string) Tools::getValue('token'))) {
			Settings::set('TAMI_MERCHANT_NUMBER', trim((string) Tools::getValue('merchant_number')));
			Settings::set('TAMI_TERMINAL_NUMBER', trim((string) Tools::getValue('terminal_number')));
			Settings::set('TAMI_SECRET_KEY', trim((string) Tools::getValue('secret_key')));
			Settings::set('TAMI_KID', trim((string) Tools::getValue('kid')));
			Settings::set('TAMI_K_VALUE', trim((string) Tools::getValue('k_value')));
			Settings::set('TAMI_TEST_MODE', Tools::getValue('test_mode') ? '1' : '0');
			Settings::set('TAMI_MAX_INSTALLMENT', (string) max(1, min(12, (int) Tools::getValue('max_installment', 1))));
			$flash = 'Tami ayarları kaydedildi';
		}

		$smarty->assign([
			'tamiMerchantNumber' => Settings::get('TAMI_MERCHANT_NUMBER'),
			'tamiTerminalNumber' => Settings::get('TAMI_TERMINAL_NUMBER'),
			'tamiSecretKey' => Settings::get('TAMI_SECRET_KEY'),
			'tamiKid' => Settings::get('TAMI_KID'),
			'tamiKValue' => Settings::get('TAMI_K_VALUE'),
			'tamiTestMode' => Settings::get('TAMI_TEST_MODE') !== '0',
			'tamiMaxInstallment' => (int) (Settings::get('TAMI_MAX_INSTALLMENT') ?: 1),
			'tamiCallbackUrl' => rtrim((string) $domain, '/') . '/api/module.php?m=tami&action=callback',
			'tamiConfigured' => self::isConfigured(),
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
		return trim((string) Settings::get('TAMI_MERCHANT_NUMBER')) !== ''
			&& trim((string) Settings::get('TAMI_TERMINAL_NUMBER')) !== ''
			&& trim((string) Settings::get('TAMI_SECRET_KEY')) !== ''
			&& trim((string) Settings::get('TAMI_K_VALUE')) !== '';
	}

	public static function client(): ?TamiClient
	{
		if (!self::isConfigured()) {
			return null;
		}

		return new TamiClient(
			(string) Settings::get('TAMI_MERCHANT_NUMBER'),
			(string) Settings::get('TAMI_TERMINAL_NUMBER'),
			(string) Settings::get('TAMI_SECRET_KEY'),
			(string) Settings::get('TAMI_KID'),
			(string) Settings::get('TAMI_K_VALUE'),
			Settings::get('TAMI_TEST_MODE') !== '0'
		);
	}

	public static function ensurePendingStorage(): void
	{
		static $ready = false;

		if ($ready) {
			return;
		}

		$ready = true;

		DB::execute(
			'CREATE TABLE IF NOT EXISTS tami_pending_checkouts (
				order_id VARCHAR(36) NOT NULL,
				reference VARCHAR(32) NOT NULL DEFAULT \'\',
				payload LONGTEXT NOT NULL,
				amount DECIMAL(20,2) NOT NULL DEFAULT 0.00,
				status VARCHAR(32) NOT NULL DEFAULT \'pending\',
				date_add DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				date_upd DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (order_id),
				KEY reference (reference)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
		);
	}

	public static function persistPendingCheckout(string $orderId, string $reference, array $checkoutData, array $cart, float $amount): void
	{
		self::ensurePendingStorage();

		$payload = json_encode([
			'checkout' => $checkoutData,
			'cart' => $cart,
			'coupon_code' => (string) ($_SESSION[Coupon::SESSION_KEY] ?? ''),
			'id_user' => Customer::getId(),
			'reference' => $reference,
		], JSON_UNESCAPED_UNICODE);

		DB::execute(
			'INSERT INTO tami_pending_checkouts (order_id, reference, payload, amount, status) VALUES (?, ?, ?, ?, ?)
			 ON DUPLICATE KEY UPDATE reference = VALUES(reference), payload = VALUES(payload),
			 amount = VALUES(amount), status = VALUES(status), date_upd = CURRENT_TIMESTAMP',
			[$orderId, $reference, $payload, round($amount, 2), 'pending']
		);
	}

	/** @return array{checkout:array,cart:array,coupon_code:string,id_user:int,reference:string}|null */
	public static function loadPendingCheckout(string $orderId): ?array
	{
		self::ensurePendingStorage();
		$row = DB::getRowSafe('tami_pending_checkouts', 'order_id = ?', [$orderId]);

		if (!$row || empty($row['payload'])) {
			return null;
		}

		$data = json_decode((string) $row['payload'], true);

		if (!is_array($data)) {
			return null;
		}

		$data['_amount'] = (float) ($row['amount'] ?? 0);
		$data['_status'] = (string) ($row['status'] ?? '');

		return $data;
	}

	public static function markPendingStatus(string $orderId, string $status): void
	{
		self::ensurePendingStorage();
		DB::execute(
			'UPDATE tami_pending_checkouts SET status = ?, date_upd = CURRENT_TIMESTAMP WHERE order_id = ?',
			[$status, $orderId]
		);
	}

	public static function deletePendingCheckout(string $orderId): void
	{
		self::ensurePendingStorage();
		DB::execute('DELETE FROM tami_pending_checkouts WHERE order_id = ?', [$orderId]);
	}

	/** Tami orderId: 2-36, alnum + -/_ (ardışık tire yok). */
	public static function makeOrderId(string $reference): string
	{
		$base = preg_replace('/[^A-Za-z0-9_-]/', '', $reference) ?: 'FS';
		$base = preg_replace('/[-_]{2,}/', '-', $base) ?: 'FS';
		$id = $base . '-' . substr(bin2hex(random_bytes(4)), 0, 8);

		return mb_substr($id, 0, 36);
	}

	/**
	 * @param array<string, mixed> $card
	 * @param array<string, mixed> $previewOrder
	 * @return array{ok:bool,html?:string,orderId?:string,message?:string}
	 */
	public static function startPayment(array $card, array $previewOrder, string $orderId, string $callbackUrl): array
	{
		$client = self::client();

		if (!$client) {
			return ['ok' => false, 'message' => 'Tami yapılandırması eksik'];
		}

		$amount = round((float) ($previewOrder['total'] ?? 0), 2);

		if ($amount <= 0) {
			return ['ok' => false, 'message' => 'Geçersiz tutar'];
		}

		$installment = max(1, (int) ($card['installment'] ?? 1));
		$maxInst = max(1, (int) (Settings::get('TAMI_MAX_INSTALLMENT') ?: 1));

		if ($installment > $maxInst) {
			$installment = $maxInst;
		}

		$nameParts = preg_split('/\s+/', trim((string) ($previewOrder['customer_name'] ?? 'Musteri')), 2) ?: [];
		$firstName = mb_substr((string) ($nameParts[0] ?? 'Musteri'), 0, 30);
		$lastName = mb_substr((string) ($nameParts[1] ?? $firstName), 0, 30);
		$email = self::resolveCustomerEmail($previewOrder);
		$phone = self::normalizePhone((string) ($previewOrder['customer_phone'] ?? ''));
		$address = self::formatAddress($previewOrder);
		$city = mb_substr(trim((string) ($previewOrder['address_city'] ?? 'Istanbul')) ?: 'Istanbul', 0, 30);
		$district = mb_substr(trim((string) ($previewOrder['address_district'] ?? '')) ?: 'Merkez', 0, 50);
		$buyerId = (string) max(1, (int) ($previewOrder['id_user'] ?? 0));

		if ($buyerId === '0') {
			$buyerId = 'g-' . substr(md5($orderId), 0, 12);
		}

		$basketItems = self::buildBasketItems($previewOrder, $amount);
		$siteName = mb_substr(trim((string) Settings::get('SITE_NAME')) ?: 'Magaza', 0, 100);

		$payload = [
			'orderId' => $orderId,
			'amount' => $amount,
			'currency' => 'TRY',
			'installmentCount' => $installment,
			'motoInd' => false,
			'paymentGroup' => 'PRODUCT',
			'paymentChannel' => 'WEB',
			'callbackUrl' => $callbackUrl,
			'card' => [
				'holderName' => mb_substr(preg_replace('/\s+/', '', (string) $card['holder']) ?: 'KartSahibi', 0, 30),
				'cvv' => (string) ($card['cvv'] ?? ''),
				'expireMonth' => (int) $card['exp_month'],
				'expireYear' => (int) $card['exp_year'],
				'number' => (string) $card['number'],
			],
			'billingAddress' => [
				'address' => mb_substr($address, 0, 400),
				'city' => $city,
				'companyName' => $siteName,
				'country' => 'Turkiye',
				'district' => $district,
				'contactName' => mb_substr(trim((string) ($previewOrder['customer_name'] ?? 'Musteri')), 0, 30),
				'phoneNumber' => $phone,
				'zipCode' => '34000',
				'emailAddress' => $email,
			],
			'shippingAddress' => [
				'address' => mb_substr($address, 0, 400),
				'city' => $city,
				'companyName' => $siteName,
				'country' => 'Turkiye',
				'district' => $district,
				'contactName' => mb_substr(trim((string) ($previewOrder['customer_name'] ?? 'Musteri')), 0, 30),
				'phoneNumber' => $phone,
				'zipCode' => '34000',
				'emailAddress' => $email,
			],
			'buyer' => [
				'ipAddress' => self::getClientIp(),
				'buyerId' => mb_substr($buyerId, 0, 50),
				'name' => $firstName,
				'surName' => $lastName,
				'identityNumber' => 11111111111,
				'city' => $city,
				'country' => 'Turkiye',
				'zipCode' => '34000',
				'emailAddress' => $email,
				'phoneNumber' => $phone,
				'registrationAddress' => mb_substr($address, 0, 400),
				'lastLoginDate' => date('Y-m-d\TH:i:s.v'),
				'registrationDate' => date('Y-m-d\TH:i:s.v', strtotime('-30 days')),
			],
			'basket' => [
				'basketId' => mb_substr($orderId, 0, 50),
				'basketItems' => $basketItems,
			],
		];

		$result = $client->start3dAuth($payload);

		if (!$result['ok']) {
			return [
				'ok' => false,
				'message' => 'Tami: ' . ($result['error'] ?? '3D başlatılamadı'),
			];
		}

		return [
			'ok' => true,
			'html' => (string) ($result['html'] ?? ''),
			'orderId' => $orderId,
		];
	}

	/**
	 * 3D callback sonrası complete + sipariş oluştur.
	 *
	 * @param array<string, mixed> $post
	 * @return array{ok:bool,message:string,id_order?:int,reference?:string}
	 */
	public static function finalizeFromCallback(array $post): array
	{
		$client = self::client();

		if (!$client) {
			return ['ok' => false, 'message' => 'Tami yapılandırması eksik'];
		}

		$orderId = trim((string) ($post['orderId'] ?? $post['orderID'] ?? ''));

		if ($orderId === '') {
			return ['ok' => false, 'message' => 'orderId eksik'];
		}

		$pending = self::loadPendingCheckout($orderId);

		if (!$pending) {
			return ['ok' => false, 'message' => 'Bekleyen ödeme bulunamadı'];
		}

		if (($pending['_status'] ?? '') === 'paid') {
			$ref = (string) ($pending['reference'] ?? '');
			$existing = $ref !== '' ? DB::getRowSafe('orders', 'reference = ?', [$ref]) : null;

			return [
				'ok' => true,
				'message' => 'Ödeme zaten tamamlanmış',
				'id_order' => (int) ($existing['id_order'] ?? 0),
				'reference' => $ref,
			];
		}

		$mdStatus = (string) ($post['mdStatus'] ?? '');
		$successRaw = $post['success'] ?? null;
		$success = $successRaw === true
			|| $successRaw === 1
			|| (string) $successRaw === '1'
			|| (string) $successRaw === 'true';

		if ($mdStatus !== '' && $mdStatus !== '1') {
			self::markPendingStatus($orderId, 'failed');

			return [
				'ok' => false,
				'message' => (string) ($post['mdErrorMessage'] ?? $post['errorMessage'] ?? '3D doğrulama başarısız'),
			];
		}

		if (!$success && $mdStatus === '') {
			self::markPendingStatus($orderId, 'failed');

			return [
				'ok' => false,
				'message' => (string) ($post['errorMessage'] ?? '3D doğrulama başarısız'),
			];
		}

		if (!empty($post['hashedData']) && !$client->verifyCallbackHash($post)) {
			error_log('Tami callback hash mismatch for ' . $orderId);
			// Bazı sandbox yanıtlarında hash sapması olabilir; complete-3ds asıl doğrulama.
		}

		$complete = $client->complete3ds($orderId);

		if (!$complete['ok']) {
			self::markPendingStatus($orderId, 'failed');

			return [
				'ok' => false,
				'message' => 'Tami complete: ' . ($complete['error'] ?? 'satış tamamlanamadı'),
			];
		}

		self::markPendingStatus($orderId, 'paid');

		$checkout = is_array($pending['checkout'] ?? null) ? $pending['checkout'] : [];
		$cart = is_array($pending['cart'] ?? null) ? $pending['cart'] : [];
		$reference = (string) ($pending['reference'] ?? '');

		if ($reference === '') {
			$reference = Order::reserveReference();
		}

		// Session yoksa (banka POST) place() için snapshot kullan
		$checkout['_payment_done'] = 1;
		$checkout['_reference'] = $reference;
		$checkout['_cart_snapshot'] = $cart;
		$checkout['_stored_id_user'] = (int) ($pending['id_user'] ?? 0);
		$checkout['_stored_coupon_code'] = (string) ($pending['coupon_code'] ?? '');
		$checkout['payment_method'] = 'tami';

		$_SESSION['pending_order_data'] = $checkout;

		if (!empty($pending['coupon_code'])) {
			$_SESSION[Coupon::SESSION_KEY] = $pending['coupon_code'];
		}

		$result = Order::placePending();

		if (!$result['success']) {
			// Ödeme alındı ama sipariş oluşmadı — log; iade manuel
			error_log('Tami: order create failed for ' . $orderId . ' — ' . ($result['message'] ?? ''));

			return [
				'ok' => false,
				'message' => $result['message'] ?? 'Sipariş oluşturulamadı (ödeme alındı, destek ile iletişime geçin)',
			];
		}

		$idOrder = (int) ($result['id_order'] ?? 0);

		if ($idOrder > 0) {
			Order::updateStatus($idOrder, Order::STATUS_PROCESSING);
		}

		self::deletePendingCheckout($orderId);

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

		$subtotal = (float) $cart['total'];
		$summary = Coupon::getCheckoutSummary($subtotal);
		$items = [];

		foreach ($cart['items'] as $item) {
			$lineTotal = (float) $item['line_total'];
			$items[] = [
				'id_product' => (int) ($item['id_product'] ?? 0),
				'product_name' => (string) $item['product_name'],
				'price' => (float) $item['price'],
				'qty' => (int) $item['qty'],
				'total' => $lineTotal,
				'total_formatted' => Tools::displayPrice($lineTotal),
				'is_virtual' => !empty($item['is_virtual']),
			];
		}

		$reference = (string) ($pendingData['_tami_reference'] ?? '');

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

	/**
	 * @param array<string, mixed> $order
	 * @return list<array<string, mixed>>
	 */
	private static function buildBasketItems(array $order, float $amount): array
	{
		$items = [];
		$sum = 0.0;

		foreach ($order['items'] ?? [] as $item) {
			$qty = max(1, (int) ($item['qty'] ?? 1));
			$total = round((float) ($item['total'] ?? 0), 2);
			$unit = $qty > 0 ? round($total / $qty, 2) : $total;
			$sum += $total;
			$items[] = [
				'itemId' => (string) max(1, (int) ($item['id_product'] ?? count($items) + 1)),
				'name' => mb_substr((string) ($item['product_name'] ?? 'Urun'), 0, 50),
				'itemType' => !empty($item['is_virtual']) ? 'VIRTUAL' : 'PHYSICAL',
				'numberOfProducts' => $qty,
				'totalPrice' => $total,
				'unitPrice' => $unit,
			];
		}

		$shipping = round((float) ($order['shipping'] ?? 0), 2);

		if ($shipping > 0) {
			$sum += $shipping;
			$items[] = [
				'itemId' => 'shipping',
				'name' => 'Kargo',
				'itemType' => 'PHYSICAL',
				'numberOfProducts' => 1,
				'totalPrice' => $shipping,
				'unitPrice' => $shipping,
			];
		}

		$discount = round((float) ($order['coupon_discount'] ?? 0), 2);

		if ($discount > 0 && $items !== []) {
			// Tami negatif satır istemez; son kalemi tutara göre dengele
			$diff = round($amount - ($sum - $discount), 2);

			if (abs($diff) > 0.009) {
				$last = count($items) - 1;
				$items[$last]['totalPrice'] = round(max(0.01, (float) $items[$last]['totalPrice'] + $diff), 2);
				$items[$last]['unitPrice'] = round(
					(float) $items[$last]['totalPrice'] / max(1, (int) $items[$last]['numberOfProducts']),
					2
				);
			}
		}

		if ($items === []) {
			$items[] = [
				'itemId' => '1',
				'name' => 'Siparis',
				'itemType' => 'PHYSICAL',
				'numberOfProducts' => 1,
				'totalPrice' => $amount,
				'unitPrice' => $amount,
			];
		} else {
			// basket total = amount zorunlu
			$basketSum = 0.0;

			foreach ($items as $row) {
				$basketSum += (float) $row['totalPrice'];
			}

			$fix = round($amount - $basketSum, 2);

			if (abs($fix) >= 0.01) {
				$last = count($items) - 1;
				$items[$last]['totalPrice'] = round(max(0.01, (float) $items[$last]['totalPrice'] + $fix), 2);
				$items[$last]['unitPrice'] = round(
					(float) $items[$last]['totalPrice'] / max(1, (int) $items[$last]['numberOfProducts']),
					2
				);
			}
		}

		return $items;
	}

	private static function formatAddress(array $order): string
	{
		$parts = array_filter([
			trim((string) ($order['address_text'] ?? '')),
			trim((string) ($order['address_district'] ?? '')),
			trim((string) ($order['address_city'] ?? '')),
		]);

		return $parts !== [] ? implode(', ', $parts) : 'Turkiye';
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

		$host = parse_url((string) (Settings::get('DOMAIN') ?: ''), PHP_URL_HOST) ?: 'fshop.local';

		return 'musteri' . max(0, $idUser) . '@' . $host;
	}

	private static function normalizePhone(string $phone): string
	{
		$digits = preg_replace('/\D+/', '', $phone) ?: '';

		if ($digits === '') {
			return '05000000000';
		}

		if (strlen($digits) === 10) {
			return '0' . $digits;
		}

		if (strlen($digits) === 12 && strpos($digits, '90') === 0) {
			return '0' . substr($digits, 2);
		}

		return mb_substr($digits, 0, 15);
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
}
