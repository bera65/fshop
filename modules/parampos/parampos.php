<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';

class ParamposModule extends ModuleBase
{
	public string $name = 'parampos';
	public string $title = 'ParamPOS';
	public string $version = '1.1.0';
	public string $description = 'ParamPOS ortak ödeme sayfası ile kredi/banka kartı ödemesi';
	public string $author = 'FShop';

	public bool $isPayment = true;
	public bool $paysBeforeOrder = true;
	public string $paymentMethodId = 'parampos';
	public string $paymentMethodLabel = 'Kredi / Banka Kartı (ParamPOS)';

	public array $routes = [
		'parampos-payment' => 'front/payment.php',
	];

	public array $displayHooks = [
		'order_payment' => 'Checkout ödeme seçeneği',
	];

	public array $defaultDisplayHooks = ['order_payment'];

	public array $frontStylesheets = ['parampos.css'];
	public array $frontScripts = [];

	public array $apiActions = [
		'callback' => 'api/callback.php',
	];

	private static string $lastSoapError = '';

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
		DB::execute('DROP TABLE IF EXISTS parampos_pending_checkouts');

		return true;
	}

	public function getPaymentPageUrl(): string
	{
		global $domain;

		return rtrim($domain, '/') . '/parampos-payment';
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken;

		$flash = '';

		if (Tools::isSubmit('saveParampos')) {
			$postToken = (string) Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				Settings::set('PARAMPOS_CLIENT_CODE', trim((string) Tools::getValue('client_code')));
				Settings::set('PARAMPOS_CLIENT_USERNAME', trim((string) Tools::getValue('client_username')));
				Settings::set('PARAMPOS_CLIENT_PASSWORD', trim((string) Tools::getValue('client_password')));
				Settings::set('PARAMPOS_GUID', trim((string) Tools::getValue('guid')));
				Settings::set('PARAMPOS_TEST_MODE', Tools::getValue('test_mode') ? '1' : '0');
				$flash = 'ParamPOS ayarları kaydedildi';
			} else {
				$flash = 'Geçersiz istek';
			}
		}

		$smarty->assign([
			'paramposClientCode' => Settings::get('PARAMPOS_CLIENT_CODE'),
			'paramposClientUsername' => Settings::get('PARAMPOS_CLIENT_USERNAME'),
			'paramposClientPassword' => Settings::get('PARAMPOS_CLIENT_PASSWORD'),
			'paramposGuid' => Settings::get('PARAMPOS_GUID'),
			'paramposTestMode' => Settings::get('PARAMPOS_TEST_MODE') !== '0',
			'paramposReturnUrl' => self::getCallbackUrl(),
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
		return trim((string) Settings::get('PARAMPOS_CLIENT_CODE')) !== ''
			&& trim((string) Settings::get('PARAMPOS_CLIENT_USERNAME')) !== ''
			&& trim((string) Settings::get('PARAMPOS_CLIENT_PASSWORD')) !== ''
			&& trim((string) Settings::get('PARAMPOS_GUID')) !== '';
	}

	public static function getLastSoapError(): string
	{
		return self::$lastSoapError;
	}

	public static function getOosServiceUrl(): string
	{
		if (Settings::get('PARAMPOS_TEST_MODE') !== '0') {
			return 'https://testposws.param.com.tr/to.ws/Service_Odeme.asmx';
		}

		return 'https://posws.param.com.tr/to.ws/Service_Odeme.asmx';
	}

	public static function getHostedPaymentBaseUrl(): string
	{
		if (Settings::get('PARAMPOS_TEST_MODE') !== '0') {
			return 'https://testpos.param.com.tr/Tahsilat/Default.aspx?s=';
		}

		return 'https://pos.param.com.tr/Tahsilat/Default.aspx?s=';
	}

	public static function getCallbackUrl(): string
	{
		global $domain;

		return rtrim($domain, '/') . '/api/module.php?m=parampos&action=callback';
	}

	/**
	 * Ortak ödeme sayfası linki üretir (TO_Pre_Encrypting_OOS).
	 *
	 * @return array{success: bool, payment_url?: string, message?: string}
	 */
	public function createHostedPayment(array $order): array
	{
		if (!self::isConfigured()) {
			return ['success' => false, 'message' => 'ParamPOS yapılandırması eksik'];
		}

		$clientCode = trim((string) Settings::get('PARAMPOS_CLIENT_CODE'));
		$guid = trim((string) Settings::get('PARAMPOS_GUID'));
		$reference = (string) $order['reference'];
		$amount = self::formatParamAmount((float) $order['total']);
		$name = trim((string) ($order['customer_name'] ?? 'Musteri'));
		$phone = self::normalizeGsm((string) ($order['customer_phone'] ?? ''));
		$returnUrl = self::getCallbackUrl();

		$bodyParams = [
			'GUID' => $guid,
			'Borclu_Kisi_TC' => '',
			'Borclu_Aciklama' => 'r|FShop siparis ' . $reference,
			'Borclu_Tutar' => 'r|' . $amount,
			'Borclu_GSM' => 'r|' . $phone,
			'Borclu_Odeme_Tip' => 'r|Diger',
			'Borclu_AdSoyad' => 'r|' . $name,
			'Return_URL' => 'r|' . $returnUrl,
			'Islem_ID' => $reference,
			'Taksit' => 0,
			'Terminal_ID' => (int) $clientCode,
		];

		$token = self::callPreEncryptingOos($bodyParams);

		if ($token === null) {
			$detail = self::getLastSoapError();

			return [
				'success' => false,
				'message' => $detail !== ''
					? ('ParamPOS bağlantı hatası: ' . $detail)
					: 'ParamPOS ortak ödeme linki alınamadı',
			];
		}

		$token = preg_replace('/\s+/', '', $token);

		// Sonuç string bazen hata mesajı gelir (base64 değilse)
		$decoded = base64_decode($token, true);

		if ($decoded === false && strpos($token, ' ') === false && strlen($token) < 80) {
			return ['success' => false, 'message' => 'ParamPOS: ' . $token];
		}

		self::storeTransactionMeta($reference, [
			'amount' => $amount,
			'mode' => 'oos',
		]);

		return [
			'success' => true,
			'payment_url' => self::getHostedPaymentBaseUrl() . rawurlencode($token),
		];
	}

	public static function handleNotification(array $post): void
	{
		$reference = trim((string) (
			$post['TURKPOS_RETVAL_Islem_ID']
			?? $post['TURKPOS_RETVAL_Siparis_ID']
			?? $post['Islem_ID']
			?? $post['orderId']
			?? ''
		));

		$sonuc = (int) ($post['TURKPOS_RETVAL_Sonuc'] ?? $post['Sonuc'] ?? 0);
		$dekontId = (string) ($post['TURKPOS_RETVAL_Dekont_ID'] ?? $post['Dekont_ID'] ?? '0');
		$sonucStr = trim((string) ($post['TURKPOS_RETVAL_Sonuc_Str'] ?? $post['Sonuc_Str'] ?? ''));
		$paidRaw = (string) ($post['TURKPOS_RETVAL_Tahsilat_Tutari'] ?? $post['TURKPOS_RETVAL_Odeme_Tutari'] ?? '');
		$siparisId = trim((string) ($post['TURKPOS_RETVAL_Siparis_ID'] ?? $post['Siparis_ID'] ?? $reference));
		$islemId = trim((string) ($post['TURKPOS_RETVAL_Islem_ID'] ?? $post['Islem_ID'] ?? $reference));

		if ($reference === '') {
			self::redirectAfterPayment(false, 'Sipariş referansı alınamadı');

			return;
		}

		$success = $sonuc > 0 && (float) $dekontId > 0;

		if (!$success) {
			$message = $sonucStr !== '' ? $sonucStr : 'Ödeme başarısız';
			self::redirectAfterPayment(false, 'ParamPOS: ' . $message, $reference);

			return;
		}

		if (!self::verifyReturnHash($post, $dekontId, $paidRaw, $siparisId, $islemId)) {
			error_log('ParamPOS return hash mismatch for ' . $reference);
			self::redirectAfterPayment(false, 'ParamPOS: güvenlik doğrulaması başarısız', $reference);

			return;
		}

		$paidAmount = self::parseParamAmount($paidRaw);

		if ($paidAmount <= 0) {
			$meta = self::loadTransactionMeta($reference);
			$paidAmount = self::parseParamAmount((string) ($meta['amount'] ?? '0'));
		}

		self::completeOrderAfterPayment($reference, $paidAmount);
		Order::clearPendingPayment();
		self::redirectAfterPayment(true, '', $reference);
	}

	/**
	 * Param docs: SHA1(UTF-8(CLIENT_CODE + GUID + Dekont_ID + Tutar + Siparis_ID + Islem_ID)) → Base64
	 * compared to TURKPOS_RETVAL_Hash.
	 */
	private static function verifyReturnHash(
		array $post,
		string $dekontId,
		string $paidRaw,
		string $siparisId,
		string $islemId
	): bool {
		$provided = trim((string) ($post['TURKPOS_RETVAL_Hash'] ?? $post['Hash'] ?? ''));

		if ($provided === '') {
			return false;
		}

		$clientCode = trim((string) Settings::get('PARAMPOS_CLIENT_CODE'));
		$guid = trim((string) Settings::get('PARAMPOS_GUID'));

		if ($clientCode === '' || $guid === '') {
			return false;
		}

		$tutar = trim($paidRaw);

		if ($tutar === '') {
			$tutar = trim((string) ($post['TURKPOS_RETVAL_Odeme_Tutari'] ?? ''));
		}

		$payload = $clientCode . $guid . $dekontId . $tutar . $siparisId . $islemId;
		$expected = base64_encode(sha1($payload, true));

		if (hash_equals($expected, $provided)) {
			return true;
		}

		// Some Param environments hash with ISO-8859-9 bytes.
		if (function_exists('iconv')) {
			$latin = @iconv('UTF-8', 'ISO-8859-9//TRANSLIT', $payload);

			if (is_string($latin) && $latin !== '') {
				$expectedLatin = base64_encode(sha1($latin, true));

				if (hash_equals($expectedLatin, $provided)) {
					return true;
				}
			}
		}

		return false;
	}

	public static function ensurePendingStorage(): void
	{
		static $ready = false;

		if ($ready) {
			return;
		}

		$ready = true;

		DB::execute(
			'CREATE TABLE IF NOT EXISTS parampos_pending_checkouts (
				reference VARCHAR(24) NOT NULL,
				payload LONGTEXT NOT NULL,
				meta TEXT NULL,
				date_add DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (reference)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
		);

		$cols = DB::execute("SHOW COLUMNS FROM `parampos_pending_checkouts` LIKE 'meta'");

		if (!$cols) {
			DB::execute('ALTER TABLE `parampos_pending_checkouts` ADD COLUMN `meta` TEXT NULL AFTER `payload`');
		}
	}

	public static function persistPendingCheckout(string $reference, array $checkoutData, array $cart): void
	{
		self::ensurePendingStorage();

		$summary = Coupon::getCheckoutSummary((float) ($cart['total'] ?? 0), $cart);
		$payload = json_encode([
			'checkout' => $checkoutData,
			'cart' => $cart,
			'coupon_code' => (string) ($_SESSION[Coupon::SESSION_KEY] ?? ''),
			'id_user' => Customer::getId(),
			'expected_total' => round((float) ($summary['total'] ?? 0), 2),
		], JSON_UNESCAPED_UNICODE);

		DB::execute(
			'INSERT INTO parampos_pending_checkouts (reference, payload) VALUES (?, ?)
			 ON DUPLICATE KEY UPDATE payload = VALUES(payload), date_add = CURRENT_TIMESTAMP',
			[$reference, $payload]
		);
	}

	/** @return array{checkout: array, cart: array, coupon_code: string, id_user: int}|null */
	public static function loadPendingCheckout(string $reference): ?array
	{
		self::ensurePendingStorage();

		$row = DB::getRowSafe('parampos_pending_checkouts', 'reference = ?', [$reference]);

		if (!$row || empty($row['payload'])) {
			return null;
		}

		$data = json_decode((string) $row['payload'], true);

		return is_array($data) ? $data : null;
	}

	public static function deletePendingCheckout(string $reference): void
	{
		self::ensurePendingStorage();
		DB::execute('DELETE FROM parampos_pending_checkouts WHERE reference = ?', [$reference]);
	}

	/** @param array<string, mixed> $meta */
	public static function storeTransactionMeta(string $reference, array $meta): void
	{
		self::ensurePendingStorage();
		DB::execute(
			'UPDATE parampos_pending_checkouts SET meta = ? WHERE reference = ?',
			[json_encode($meta, JSON_UNESCAPED_UNICODE), $reference]
		);
	}

	/** @return array<string, mixed> */
	public static function loadTransactionMeta(string $reference): array
	{
		self::ensurePendingStorage();
		$row = DB::getRowSafe('parampos_pending_checkouts', 'reference = ?', [$reference]);

		if (!$row || empty($row['meta'])) {
			return [];
		}

		$data = json_decode((string) $row['meta'], true);

		return is_array($data) ? $data : [];
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
			error_log('ParamPOS: pending checkout not found for ' . $reference);

			return;
		}

		$checkout = is_array($pending['checkout'] ?? null) ? $pending['checkout'] : [];
		$cart = is_array($pending['cart'] ?? null) ? $pending['cart'] : [];

		if ($cart === [] || !empty($cart['empty'])) {
			error_log('ParamPOS: empty cart snapshot for ' . $reference);

			return;
		}

		$expected = (float) ($pending['expected_total'] ?? 0);

		if ($expected <= 0) {
			$summary = Coupon::getCheckoutSummary((float) ($cart['total'] ?? 0), $cart);
			$expected = (float) ($summary['total'] ?? 0);
		}

		$meta = self::loadTransactionMeta($reference);

		if ($expected <= 0 && !empty($meta['amount'])) {
			$expected = self::parseParamAmount((string) $meta['amount']);
		}

		if ($paidAmount <= 0 || ($expected > 0 && abs($paidAmount - $expected) > 0.05)) {
			error_log('ParamPOS: refusing order create for ' . $reference
				. ' expected=' . $expected . ' paid=' . $paidAmount);

			return;
		}

		$checkout['_payment_done'] = 1;
		$checkout['_reference'] = $reference;
		$checkout['_cart_snapshot'] = $cart;
		$checkout['_stored_id_user'] = (int) ($pending['id_user'] ?? 0);
		$checkout['_stored_coupon_code'] = (string) ($pending['coupon_code'] ?? '');

		$result = Order::place($checkout);

		if (!$result['success']) {
			error_log('ParamPOS: order create failed for ' . $reference . ' — ' . ($result['message'] ?? ''));

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

		$reference = (string) ($pendingData['_parampos_reference'] ?? '');

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

	public static function formatParamAmount(float $amount): string
	{
		return number_format(round($amount, 2), 2, ',', '');
	}

	public static function parseParamAmount(string $amount): float
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

	private static function normalizeGsm(string $phone): string
	{
		$phone = preg_replace('/\D+/', '', $phone);

		if ($phone === '') {
			return '5555555555';
		}

		if (strlen($phone) === 11 && $phone[0] === '0') {
			$phone = substr($phone, 1);
		}

		if (strlen($phone) > 10) {
			$phone = substr($phone, -10);
		}

		return $phone;
	}

	private static function markOrderPaid(array $order, float $paidAmount): void
	{
		$expected = (float) $order['total'];

		if ($paidAmount <= 0 || abs($paidAmount - $expected) > 0.05) {
			error_log('ParamPOS amount mismatch for order ' . $order['reference']
				. ' expected=' . $expected . ' paid=' . $paidAmount);

			return;
		}

		if ((int) $order['status'] !== Order::STATUS_PENDING) {
			return;
		}

		Order::updateStatus((int) $order['id_order'], Order::STATUS_PROCESSING);
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

		$target = rtrim($domain, '/') . '/parampos-payment?fail=1';

		if ($message !== '') {
			$_SESSION['parampos_payment_error'] = $message;
		}

		header('Location: ' . $target);
		exit;
	}

	private static function shouldVerifySsl(): bool
	{
		if (Settings::get('PARAMPOS_TEST_MODE') !== '0') {
			return false;
		}

		$ca = (string) ini_get('curl.cainfo');

		if ($ca === '') {
			$ca = (string) ini_get('openssl.cafile');
		}

		return $ca !== '' && is_file($ca);
	}

	/** @param array<string, mixed> $bodyParams */
	private static function callPreEncryptingOos(array $bodyParams): ?string
	{
		self::$lastSoapError = '';

		$clientCode = trim((string) Settings::get('PARAMPOS_CLIENT_CODE'));
		$username = trim((string) Settings::get('PARAMPOS_CLIENT_USERNAME'));
		$password = trim((string) Settings::get('PARAMPOS_CLIENT_PASSWORD'));

		$bodyInner = self::buildSoapParamsXml($bodyParams);
		$xml = '<?xml version="1.0" encoding="utf-8"?>'
			. '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
			. ' xmlns:xsd="http://www.w3.org/2001/XMLSchema"'
			. ' xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">'
			. '<soap:Header>'
			. '<ServiceSecuritySoapHeader xmlns="https://turkodeme.com.tr/">'
			. '<CLIENT_CODE>' . htmlspecialchars($clientCode, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</CLIENT_CODE>'
			. '<CLIENT_USERNAME>' . htmlspecialchars($username, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</CLIENT_USERNAME>'
			. '<CLIENT_PASSWORD>' . htmlspecialchars($password, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</CLIENT_PASSWORD>'
			. '</ServiceSecuritySoapHeader>'
			. '</soap:Header>'
			. '<soap:Body>'
			. '<TO_Pre_Encrypting_OOS xmlns="https://turkodeme.com.tr/">'
			. $bodyInner
			. '</TO_Pre_Encrypting_OOS>'
			. '</soap:Body>'
			. '</soap:Envelope>';

		$url = self::getOosServiceUrl();
		$verifySsl = self::shouldVerifySsl();

		if (!function_exists('curl_init')) {
			self::$lastSoapError = 'cURL eklentisi yüklü değil';

			return null;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: text/xml; charset=utf-8',
			'SOAPAction: "https://turkodeme.com.tr/TO_Pre_Encrypting_OOS"',
			'Content-Length: ' . strlen($xml),
			'User-Agent: FShop-ParamPOS/1.1',
		]);
		curl_setopt($ch, CURLOPT_TIMEOUT, 45);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifySsl);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verifySsl ? 2 : 0);

		$result = curl_exec($ch);
		$errno = curl_errno($ch);
		$error = curl_error($ch);
		$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($errno) {
			self::$lastSoapError = 'cURL #' . $errno . ': ' . $error;
			error_log('ParamPOS OOS curl error: ' . self::$lastSoapError);

			return null;
		}

		if (!is_string($result) || $result === '') {
			if ($httpCode === 403) {
				self::$lastSoapError = 'Param sunucusu erişimi reddetti (HTTP 403). '
					. 'Sunucu çıkış IP adresinizi Param paneline veya integration@param.com.tr adresine kaydettirmeniz gerekiyor.';
			} else {
				self::$lastSoapError = 'Boş SOAP yanıtı (HTTP ' . $httpCode . ')';
			}

			return null;
		}

		if ($httpCode === 403 || stripos($result, 'Access Denied') !== false) {
			self::$lastSoapError = 'Param sunucusu erişimi reddetti (HTTP 403). '
				. 'Sunucu çıkış IP adresinizi Param paneline veya integration@param.com.tr adresine kaydettirmeniz gerekiyor.';

			return null;
		}

		if ($httpCode >= 400) {
			self::$lastSoapError = 'Param sunucusu hata döndü (HTTP ' . $httpCode . ')';
			error_log('ParamPOS OOS HTTP ' . $httpCode . ': ' . substr($result, 0, 400));

			return null;
		}

		$token = self::extractOosResult($result);

		if ($token === null || $token === '') {
			self::$lastSoapError = 'TO_Pre_Encrypting_OOS sonucu parse edilemedi';
			error_log('ParamPOS OOS parse fail: ' . substr($result, 0, 500));

			return null;
		}

		return $token;
	}

	private static function extractOosResult(string $xml): ?string
	{
		$previous = libxml_use_internal_errors(true);
		$doc = simplexml_load_string($xml);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);

		if ($doc === false) {
			if (preg_match('/<TO_Pre_Encrypting_OOSResult>(.*?)<\/TO_Pre_Encrypting_OOSResult>/s', $xml, $m)) {
				return trim(html_entity_decode($m[1], ENT_QUOTES | ENT_XML1, 'UTF-8'));
			}

			return null;
		}

		$nodes = $doc->xpath('//*[local-name()="TO_Pre_Encrypting_OOSResult"]');

		if ($nodes && isset($nodes[0])) {
			return trim((string) $nodes[0]);
		}

		return null;
	}

	/** @param array<string, mixed> $params */
	private static function buildSoapParamsXml(array $params): string
	{
		$xml = '';

		foreach ($params as $key => $value) {
			if (is_array($value)) {
				$xml .= '<' . $key . '>' . self::buildSoapParamsXml($value) . '</' . $key . '>';
			} else {
				$xml .= '<' . $key . '>' . htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</' . $key . '>';
			}
		}

		return $xml;
	}
}
