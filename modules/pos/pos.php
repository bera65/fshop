<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';

class PosModule extends ModuleBase
{
	public string $name = 'pos';
	public string $title = 'Point of Sale (Kasa)';
	public string $version = '1.4.0';
	public string $description = 'Mağaza içi satış ekranı — PIN veya admin ile giriş';
	public string $author = 'FShop';

	public array $routes = [
		'pos' => 'front/pos.php',
		'pos-card-payment' => 'front/card-payment.php',
	];

	public array $adminRoutes = [
		'pos' => 'front/pos.php',
		'pos-card-payment' => 'front/card-payment.php',
	];

	public array $apiActions = [
		'products' => 'api/products.php',
		'product' => 'api/product.php',
		'barcode' => 'api/barcode.php',
		'cart' => 'api/cart.php',
		'complete' => 'api/complete.php',
		'customers' => 'api/customers.php',
		'customer' => 'api/customer.php',
		'stats' => 'api/stats.php',
		'lock' => 'api/lock.php',
		'unlock' => 'api/unlock.php',
		'prepare-card' => 'api/prepare-card.php',
		'receipt' => 'api/receipt.php',
		'login' => 'api/login.php',
		'logout' => 'api/logout.php',
	];

	public array $adminStylesheets = ['admin.css'];

	/** POS CSS/JS yalnızca POS şablonlarında yüklenir (getHeadAssets otomatik taramasını kapatır). */
	public array $frontStylesheets = ['.no-global-assets'];
	public array $frontScripts = ['.no-global-assets'];

	public const SESSION_AUTH = 'pos_auth';
	public const SESSION_CART = 'pos_cart';
	public const SESSION_CUSTOMER = 'pos_customer';
	public const SESSION_SCREEN_LOCKED = 'pos_screen_locked';
	public const SESSION_CARD_PENDING = 'pos_card_pending';

	public const PAYMENT_CASH = 'pos_cash';
	public const PAYMENT_CARD = 'pos_card';
	public const PAYMENT_TRANSFER = 'pos_transfer';

	private const SET_ENABLED = 'POS_ENABLED';
	private const SET_PIN_HASH = 'POS_PIN_HASH';
	private const SET_ORDER_STATUS = 'POS_ORDER_STATUS';
	private const SET_STORE_LABEL = 'POS_STORE_LABEL';
	private const SET_CARD_URL = 'POS_CARD_URL';
	private const SET_FULLSCREEN_AUTO = 'POS_FULLSCREEN_AUTO';
	private const SET_HIDE_OUT_OF_STOCK = 'POS_HIDE_OUT_OF_STOCK';
	private const SET_ALLOW_OUT_OF_STOCK_SALE = 'POS_ALLOW_OUT_OF_STOCK_SALE';
	private const SET_CARD_ADJ_TYPE = 'POS_CARD_ADJ_TYPE';
	private const SET_CARD_ADJ_PERCENT = 'POS_CARD_ADJ_PERCENT';
	private const SET_TRANSFER_ADJ_TYPE = 'POS_TRANSFER_ADJ_TYPE';
	private const SET_TRANSFER_ADJ_PERCENT = 'POS_TRANSFER_ADJ_PERCENT';

	public function install(): bool
	{
		Settings::set(self::SET_ENABLED, '1');
		Settings::set(self::SET_ORDER_STATUS, (string) Order::STATUS_DELIVERED);
		Settings::set(self::SET_STORE_LABEL, 'Mağaza Satış');
		Settings::set(self::SET_CARD_URL, '');
		Settings::set(self::SET_FULLSCREEN_AUTO, '0');
		Settings::set(self::SET_HIDE_OUT_OF_STOCK, '0');
		Settings::set(self::SET_ALLOW_OUT_OF_STOCK_SALE, '0');
		Settings::set(self::SET_CARD_ADJ_TYPE, 'none');
		Settings::set(self::SET_CARD_ADJ_PERCENT, '0');
		Settings::set(self::SET_TRANSFER_ADJ_TYPE, 'none');
		Settings::set(self::SET_TRANSFER_ADJ_PERCENT, '0');

		return true;
	}

	public function uninstall(): bool
	{
		Settings::set(self::SET_ENABLED, '0');
		Settings::set(self::SET_PIN_HASH, '');
		Settings::set(self::SET_ORDER_STATUS, '');
		Settings::set(self::SET_STORE_LABEL, '');
		Settings::set(self::SET_CARD_URL, '');
		Settings::set(self::SET_FULLSCREEN_AUTO, '0');
		Settings::set(self::SET_HIDE_OUT_OF_STOCK, '0');
		Settings::set(self::SET_ALLOW_OUT_OF_STOCK_SALE, '0');
		Settings::set(self::SET_CARD_ADJ_TYPE, 'none');
		Settings::set(self::SET_CARD_ADJ_PERCENT, '0');
		Settings::set(self::SET_TRANSFER_ADJ_TYPE, 'none');
		Settings::set(self::SET_TRANSFER_ADJ_PERCENT, '0');

		return true;
	}

	public function boot(): void
	{
		$this->registerAdminMenuLink('Point of Sale', 'general', 72, 'pos');
	}

	public function isEnabled(): bool
	{
		return Settings::get(self::SET_ENABLED, '1') === '1';
	}

	public function hasPinConfigured(): bool
	{
		$hash = (string) Settings::get(self::SET_PIN_HASH, '');

		return $hash !== '';
	}

	public function hasTerminalAccess(): bool
	{
		if (!$this->isEnabled()) {
			return false;
		}

		return !empty($_SESSION['id_admin']) || !empty($_SESSION[self::SESSION_AUTH]);
	}

	public function isScreenLocked(): bool
	{
		return !empty($_SESSION[self::SESSION_SCREEN_LOCKED]);
	}

	public function isAuthorized(): bool
	{
		return $this->hasTerminalAccess() && !$this->isScreenLocked();
	}

	public function lockScreen(): array
	{
		if (!$this->hasTerminalAccess()) {
			return ['success' => false, 'message' => 'Oturum gerekli'];
		}

		if (!$this->hasPinConfigured()) {
			return ['success' => false, 'message' => 'Ekran kilidi için önce PIN tanımlayın (modül ayarları)'];
		}

		$_SESSION[self::SESSION_SCREEN_LOCKED] = time();

		return ['success' => true, 'message' => 'Ekran kilitlendi'];
	}

	public function unlockScreen(string $pin): array
	{
		$pin = trim($pin);

		if (!$this->isScreenLocked()) {
			return ['success' => true, 'message' => 'Ekran zaten açık'];
		}

		if (!$this->hasPinConfigured()) {
			return ['success' => false, 'message' => 'PIN yapılandırılmamış'];
		}

		if ($pin === '') {
			return ['success' => false, 'message' => 'PIN girin'];
		}

		$identifier = RateLimit::loginIdentifier('pos_unlock');

		if (RateLimit::isLimited(RateLimit::SCOPE_POS_LOGIN, $identifier, 8, 900)) {
			return ['success' => false, 'message' => 'Çok fazla deneme. 15 dakika sonra tekrar deneyin.'];
		}

		$hash = (string) Settings::get(self::SET_PIN_HASH, '');

		if (!password_verify($pin, $hash)) {
			RateLimit::record(RateLimit::SCOPE_POS_LOGIN, $identifier);

			return ['success' => false, 'message' => 'Hatalı PIN'];
		}

		RateLimit::clear(RateLimit::SCOPE_POS_LOGIN, $identifier);

		unset($_SESSION[self::SESSION_SCREEN_LOCKED]);

		return ['success' => true, 'message' => 'Ekran açıldı'];
	}

	/** Sanal POS (chargeCard destekleyen) modül */
	public function getCardGatewayModule(): ?ModuleBase
	{
		$module = Module::getPaymentModule('credit_card');

		if ($module && method_exists($module, 'chargeCard')) {
			return $module;
		}

		return null;
	}

	public function hasOnlineCardGateway(): bool
	{
		return $this->getCardGatewayModule() !== null;
	}

	public function prepareCardPayment(): array
	{
		if (!$this->isAuthorized()) {
			return ['success' => false, 'message' => 'Oturum gerekli', 'auth_required' => true];
		}

		$cart = $this->getCartSummary();

		if (!empty($cart['empty'])) {
			return ['success' => false, 'message' => 'Sepet boş'];
		}

		$gateway = $this->getCardGatewayModule();

		if (!$gateway) {
			return [
				'success' => false,
				'message' => 'Kredi kartı modülü (Sanal POS) aktif değil. Modüllerden kurup etkinleştirin.',
			];
		}

		$payable = $this->calculatePaymentTotal((float) $cart['subtotal'], self::PAYMENT_CARD);

		$_SESSION[self::SESSION_CARD_PENDING] = [
			'total' => (float) $payable['total'],
			'subtotal' => (float) $payable['subtotal'],
			'customer' => $this->getSessionCustomer(),
			'prepared_at' => time(),
		];

		return [
			'success' => true,
			'message' => 'Kart ödeme sayfasına yönlendiriliyorsunuz',
			'redirect' => $this->resolvePosPageUrl('pos-card-payment'),
		];
	}

	public function clearCardPending(): void
	{
		unset($_SESSION[self::SESSION_CARD_PENDING]);
	}

	/** @return array<string, mixed>|null */
	public function getCardPending(): ?array
	{
		$pending = $_SESSION[self::SESSION_CARD_PENDING] ?? null;

		return is_array($pending) ? $pending : null;
	}

	public function processCardPayment(array $card): array
	{
		if (!$this->hasTerminalAccess()) {
			return ['success' => false, 'message' => 'Oturum gerekli'];
		}

		$pending = $this->getCardPending();

		if (!$pending) {
			return ['success' => false, 'message' => 'Bekleyen kart ödemesi yok'];
		}

		$cart = $this->getCartSummary();

		if (!empty($cart['empty'])) {
			$this->clearCardPending();

			return ['success' => false, 'message' => 'Sepet boş'];
		}

		$gateway = $this->getCardGatewayModule();

		if (!$gateway) {
			return ['success' => false, 'message' => 'Kart modülü kullanılamıyor'];
		}

		$payable = $this->calculatePaymentTotal((float) $cart['subtotal'], self::PAYMENT_CARD);
		$amount = (float) $payable['total'];
		$bank = $gateway->chargeCard($card, $amount);

		if (empty($bank['success'])) {
			return [
				'success' => false,
				'message' => (string) ($bank['message'] ?? 'Banka ödemeyi reddetti'),
			];
		}

		$customer = is_array($pending['customer'] ?? null) ? $pending['customer'] : $this->getSessionCustomer();
		$txn = (string) ($bank['transaction_id'] ?? '');
		$note = $txn !== '' ? 'Kart ref: ' . $txn : '';

		$result = $this->createSale(
			self::PAYMENT_CARD,
			(string) ($customer['name'] ?? ''),
			(string) ($customer['phone'] ?? ''),
			$note,
			0.0,
			(int) ($customer['id_user'] ?? 0)
		);

		if (!empty($result['success'])) {
			$this->clearCardPending();
		}

		return $result;
	}

	public function renderCardPaymentPage(): void
	{
		global $smarty, $domain, $adminUrl, $token;

		if (!$this->hasTerminalAccess()) {
			header('Location: ' . rtrim($domain, '/') . '/pos');
			exit;
		}

		$pending = $this->getCardPending();
		$cart = $this->getCartSummary();

		if (!$pending || !empty($cart['empty'])) {
			$this->clearCardPending();
			$back = !empty($_SESSION['id_admin'])
				? rtrim($adminUrl, '/') . '/pos'
				: rtrim($domain, '/') . '/pos';
			header('Location: ' . $back);
			exit;
		}

		$gateway = $this->getCardGatewayModule();

		if (!$gateway) {
			header('Location: ' . rtrim($domain, '/') . '/pos');
			exit;
		}

		$paymentError = '';
		$cardForm = [
			'holder' => '',
			'number' => '',
			'exp_month' => '',
			'exp_year' => '',
		];

		if (Tools::isSubmit('payPosCard')) {
			$postToken = (string) Tools::getValue('token');

			if (!$this->verifyApiToken($postToken)) {
				$paymentError = 'Geçersiz istek, sayfayı yenileyin';
			} else {
				$cardForm = [
					'holder' => trim((string) Tools::getValue('card_holder')),
					'number' => preg_replace('/[^0-9]/', '', (string) Tools::getValue('card_number')),
					'exp_month' => (string) Tools::getValue('exp_month'),
					'exp_year' => (string) Tools::getValue('exp_year'),
				];
				$cvv = (string) Tools::getValue('cvv');

				if ($cardForm['holder'] === '') {
					$paymentError = 'Kart üzerindeki ismi girin';
				} elseif (class_exists('SanalposModule', false)
					&& !SanalposModule::isValidCardNumber($cardForm['number'])) {
					$paymentError = 'Geçerli bir kart numarası girin';
				} elseif (class_exists('SanalposModule', false)
					&& !SanalposModule::isValidExpiry((int) $cardForm['exp_month'], (int) $cardForm['exp_year'])) {
					$paymentError = 'Son kullanma tarihi geçersiz';
				} elseif (!preg_match('/^[0-9]{3,4}$/', $cvv)) {
					$paymentError = 'Geçerli bir CVV girin';
				} else {
					$result = $this->processCardPayment([
						'holder' => $cardForm['holder'],
						'number' => $cardForm['number'],
						'exp_month' => (int) $cardForm['exp_month'],
						'exp_year' => (int) $cardForm['exp_year'],
						'cvv' => $cvv,
					]);

					if (!empty($result['success'])) {
						$back = !empty($_SESSION['id_admin'])
							? rtrim($adminUrl, '/') . '/pos'
							: rtrim($domain, '/') . '/pos';
						header('Location: ' . $back . '?sale=' . urlencode((string) ($result['reference'] ?? '')));
						exit;
					}

					$paymentError = (string) ($result['message'] ?? 'Ödeme tamamlanamadı');
				}
			}
		}

		$backUrl = !empty($_SESSION['id_admin'])
			? rtrim($adminUrl, '/') . '/pos'
			: rtrim($domain, '/') . '/pos';

		$html = $this->renderFrontTemplate('card_payment', [
			'posPaymentError' => $paymentError,
			'posCardForm' => $cardForm,
			'posCart' => $cart,
			'posToken' => $this->getApiToken(),
			'posBackUrl' => $backUrl,
			'posCssUrl' => $this->getAssetUrl('css/pos.css'),
			'posGatewayTitle' => $gateway->title,
		]);

		header('Content-Type: text/html; charset=utf-8');
		echo $html;
		exit;
	}

	public function verifyApiToken(string $token): bool
	{
		$front = (string) ($_SESSION['csrf_token'] ?? '');
		$admin = (string) ($_SESSION['admin_csrf_token'] ?? '');

		if ($front !== '' && hash_equals($front, $token)) {
			return true;
		}

		return $admin !== '' && hash_equals($admin, $token);
	}

	public function getApiToken(): string
	{
		if (!empty($_SESSION['csrf_token'])) {
			return (string) $_SESSION['csrf_token'];
		}

		return (string) ($_SESSION['admin_csrf_token'] ?? '');
	}

	public function authorizeWithPin(string $pin): array
	{
		$pin = trim($pin);

		if (!$this->isEnabled()) {
			return ['success' => false, 'message' => 'POS modülü kapalı'];
		}

		if (!$this->hasPinConfigured()) {
			return ['success' => false, 'message' => 'PIN henüz yapılandırılmamış. Yönetim panelinden ayarlayın.'];
		}

		if ($pin === '') {
			return ['success' => false, 'message' => 'PIN girin'];
		}

		$identifier = RateLimit::loginIdentifier('pos_login');

		if (RateLimit::isLimited(RateLimit::SCOPE_POS_LOGIN, $identifier, 8, 900)) {
			return ['success' => false, 'message' => 'Çok fazla deneme. 15 dakika sonra tekrar deneyin.'];
		}

		$hash = (string) Settings::get(self::SET_PIN_HASH, '');

		if (!password_verify($pin, $hash)) {
			RateLimit::record(RateLimit::SCOPE_POS_LOGIN, $identifier);

			return ['success' => false, 'message' => 'Hatalı PIN'];
		}

		RateLimit::clear(RateLimit::SCOPE_POS_LOGIN, $identifier);

		$_SESSION[self::SESSION_AUTH] = time();
		unset($_SESSION[self::SESSION_SCREEN_LOCKED]);

		return ['success' => true, 'message' => 'Giriş başarılı'];
	}

	public function logout(): void
	{
		unset(
			$_SESSION[self::SESSION_AUTH],
			$_SESSION[self::SESSION_CART],
			$_SESSION[self::SESSION_CUSTOMER],
			$_SESSION[self::SESSION_SCREEN_LOCKED],
			$_SESSION[self::SESSION_CARD_PENDING]
		);
	}

	/** @return array{id_user: int, name: string, phone: string, email: string, label: string} */
	public function getDefaultCustomer(): array
	{
		return [
			'id_user' => 0,
			'name' => 'Ziyaretçi',
			'phone' => '',
			'email' => '',
			'label' => 'Ziyaretçi',
		];
	}

	/** @return array{id_user: int, name: string, phone: string, email: string, label: string} */
	public function getSessionCustomer(): array
	{
		$stored = $_SESSION[self::SESSION_CUSTOMER] ?? null;

		if (!is_array($stored)) {
			return $this->getDefaultCustomer();
		}

		return [
			'id_user' => (int) ($stored['id_user'] ?? 0),
			'name' => (string) ($stored['name'] ?? 'Ziyaretçi'),
			'phone' => (string) ($stored['phone'] ?? ''),
			'email' => (string) ($stored['email'] ?? ''),
			'label' => (string) ($stored['label'] ?? $stored['name'] ?? 'Ziyaretçi'),
		];
	}

	/** @param array<string, mixed> $data */
	public function setSessionCustomer(array $data): array
	{
		$idUser = (int) ($data['id_user'] ?? 0);

		if ($idUser > 0) {
			$user = Customer::getByIdAdmin($idUser);

			if (!$user) {
				return ['success' => false, 'message' => 'Müşteri bulunamadı'];
			}

			$name = trim((string) ($user['user_full_name'] ?? ''));
			$phone = Customer::normalizePhone((string) ($user['phone'] ?? ''));
			$email = trim((string) ($user['email'] ?? ''));

			$_SESSION[self::SESSION_CUSTOMER] = [
				'id_user' => $idUser,
				'name' => $name !== '' ? $name : 'Müşteri #' . $idUser,
				'phone' => $phone,
				'email' => $email,
				'label' => $name !== '' ? $name : 'Müşteri #' . $idUser,
			];
		} else {
			$name = mb_substr(trim(strip_tags((string) ($data['name'] ?? 'Ziyaretçi'))), 0, 128);
			$phone = Customer::normalizePhone((string) ($data['phone'] ?? ''));

			if ($name === '') {
				$name = 'Ziyaretçi';
			}

			$_SESSION[self::SESSION_CUSTOMER] = [
				'id_user' => 0,
				'name' => $name,
				'phone' => $phone,
				'email' => '',
				'label' => $name,
			];
		}

		return [
			'success' => true,
			'message' => 'Müşteri güncellendi',
			'customer' => $this->getSessionCustomer(),
		];
	}

	public function resetSessionCustomer(): array
	{
		unset($_SESSION[self::SESSION_CUSTOMER]);

		return [
			'success' => true,
			'message' => 'Ziyaretçi seçildi',
			'customer' => $this->getDefaultCustomer(),
		];
	}

	/** @return array<int, array<string, mixed>> */
	public function searchCustomers(string $query, int $limit = 20): array
	{
		$query = trim($query);

		if ($query === '' || Tools::strlen($query) < 2) {
			return [];
		}

		$rows = Customer::getAdminList($query, max(1, min(30, $limit)));

		$list = [];
		foreach ($rows as $row) {
			$list[] = [
				'id_user' => (int) ($row['id_user'] ?? 0),
				'name' => (string) ($row['user_full_name'] ?? ''),
				'phone' => (string) ($row['phone'] ?? ''),
				'email' => (string) ($row['email'] ?? ''),
				'order_count' => (int) ($row['order_count'] ?? 0),
			];
		}

		return $list;
	}

	/** @return array{success: bool, message: string, customer?: array<string, mixed>, id_user?: int} */
	public function createCustomer(string $fullName, string $phone, string $email = ''): array
	{
		$result = Customer::createByAdmin($fullName, $phone, $email);

		if (empty($result['success'])) {
			return $result;
		}

		$idUser = (int) ($result['id_user'] ?? 0);

		return [
			'success' => true,
			'message' => (string) ($result['message'] ?? 'Müşteri oluşturuldu'),
			'id_user' => $idUser,
			'customer' => [
				'id_user' => $idUser,
				'name' => trim($fullName),
				'phone' => Customer::normalizePhone($phone),
				'email' => trim(strtolower($email)),
			],
		];
	}

	/** @return array<int, array{label: string, url: string}> */
	public function getCardUrlSuggestions(): array
	{
		global $domain, $adminUrl;

		$base = rtrim((string) $domain, '/');
		$adminBase = rtrim((string) ($adminUrl ?? $base . '/admin'), '/');

		return [
			['label' => 'Site ana sayfa', 'url' => $base . '/'],
			['label' => 'Mağaza POS (vitrin)', 'url' => $base . '/pos'],
			['label' => 'Admin POS', 'url' => $adminBase . '/pos'],
		];
	}

	public function resolveCardUrl(): string
	{
		return trim((string) Settings::get(self::SET_CARD_URL, ''));
	}

	/**
	 * Absolute POS page URL (shop or admin). Safe when called from api/module.php
	 * where $adminUrl global may be unset.
	 */
	public function resolvePosPageUrl(string $slug): string
	{
		global $domain, $adminUrl;

		$slug = trim($slug, '/');
		if ($slug === '') {
			$slug = 'pos';
		}

		if (!empty($_SESSION['id_admin'])) {
			if (class_exists('Admin', false)) {
				$adminBase = rtrim(Admin::baseUrl(), '/');
				if ($adminBase !== '') {
					return $adminBase . '/' . $slug;
				}
			}

			$adminBase = rtrim((string) ($adminUrl ?? ''), '/');
			if ($adminBase !== '') {
				return $adminBase . '/' . $slug;
			}
		}

		$shopBase = rtrim((string) ($domain ?? ''), '/');
		if ($shopBase === '') {
			$shopBase = rtrim((string) Settings::get('DOMAIN'), '/');
		}

		$folder = trim((string) Settings::get('FOLDER'), '/');
		if ($shopBase === '') {
			$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
			$host = trim((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
			$shopBase = ($https ? 'https' : 'http') . '://' . $host
				. ($folder !== '' ? '/' . $folder : '');
		} elseif ($folder !== '') {
			$path = (string) (parse_url($shopBase, PHP_URL_PATH) ?? '');
			$path = rtrim($path, '/');
			if ($path === '' || $path === '/') {
				$shopBase = rtrim($shopBase, '/') . '/' . $folder;
			}
		}

		return rtrim($shopBase, '/') . '/' . $slug;
	}

	public function isFullscreenAuto(): bool
	{
		return Settings::get(self::SET_FULLSCREEN_AUTO, '0') === '1';
	}

	public function hideOutOfStock(): bool
	{
		return Settings::get(self::SET_HIDE_OUT_OF_STOCK, '0') === '1';
	}

	public function allowOutOfStockSale(): bool
	{
		return Settings::get(self::SET_ALLOW_OUT_OF_STOCK_SALE, '0') === '1';
	}

	private function normalizeAdjustmentType(string $type): string
	{
		$type = strtolower(trim($type));

		return in_array($type, ['discount', 'commission'], true) ? $type : 'none';
	}

	private function getAdjustmentPercent(string $settingKey): float
	{
		$value = (float) str_replace(',', '.', (string) Settings::get($settingKey, '0'));

		return max(0.0, min(100.0, round($value, 2)));
	}

	/** @return array<string, array{type: string, percent: float}> */
	public function getPaymentAdjustmentConfig(): array
	{
		return [
			self::PAYMENT_CASH => ['type' => 'none', 'percent' => 0.0],
			self::PAYMENT_CARD => [
				'type' => $this->normalizeAdjustmentType((string) Settings::get(self::SET_CARD_ADJ_TYPE, 'none')),
				'percent' => $this->getAdjustmentPercent(self::SET_CARD_ADJ_PERCENT),
			],
			self::PAYMENT_TRANSFER => [
				'type' => $this->normalizeAdjustmentType((string) Settings::get(self::SET_TRANSFER_ADJ_TYPE, 'none')),
				'percent' => $this->getAdjustmentPercent(self::SET_TRANSFER_ADJ_PERCENT),
			],
		];
	}

	/** @return array<string, mixed> */
	public function calculatePaymentTotal(float $subtotal, string $paymentMethod): array
	{
		$subtotal = max(0.0, round($subtotal, 2));
		$config = $this->getPaymentAdjustmentConfig();
		$rule = $config[$paymentMethod] ?? ['type' => 'none', 'percent' => 0.0];
		$type = (string) ($rule['type'] ?? 'none');
		$percent = (float) ($rule['percent'] ?? 0.0);
		$adjustment = 0.0;
		$label = '';

		if ($percent > 0 && $type === 'discount') {
			$adjustment = -round($subtotal * $percent / 100, 2);
			$label = 'Havale indirimi';
			if ($paymentMethod === self::PAYMENT_CARD) {
				$label = 'Kart indirimi';
			}
			$label .= ' (%' . rtrim(rtrim(number_format($percent, 2, '.', ''), '0'), '.') . ')';
		} elseif ($percent > 0 && $type === 'commission') {
			$adjustment = round($subtotal * $percent / 100, 2);
			$label = 'Kart komisyonu';
			if ($paymentMethod === self::PAYMENT_TRANSFER) {
				$label = 'Havale komisyonu';
			}
			$label .= ' (%' . rtrim(rtrim(number_format($percent, 2, '.', ''), '0'), '.') . ')';
		}

		$total = max(0.0, round($subtotal + $adjustment, 2));

		return [
			'subtotal' => $subtotal,
			'subtotal_formatted' => Tools::displayPrice($subtotal),
			'adjustment' => $adjustment,
			'adjustment_formatted' => Tools::displayPrice(abs($adjustment)),
			'adjustment_signed_formatted' => ($adjustment >= 0 ? '+' : '−') . Tools::displayPrice(abs($adjustment)),
			'adjustment_label' => $label,
			'adjustment_type' => $type,
			'adjustment_percent' => $percent,
			'total' => $total,
			'total_formatted' => Tools::displayPrice($total),
		];
	}

	/** @return array<int, array<string, mixed>> */
	private function queryProductsRaw(int $idCategory, string $query, int $limit, int $offset): array
	{
		if ($query !== '' && Tools::strlen($query) >= 2) {
			return Product::search($query, $limit, $offset, 'name_asc');
		}

		return Product::getActiveList($idCategory > 0 ? $idCategory : null, $limit, $offset, 'name_asc');
	}

	private function countProductsRaw(int $idCategory, string $query): int
	{
		if ($query !== '' && Tools::strlen($query) >= 2) {
			return Product::countSearch($query);
		}

		return Product::countActive($idCategory > 0 ? $idCategory : null);
	}

	/** @param array<string, mixed> $product */
	public function isProductInStockForPos(array $product): bool
	{
		return !empty($product['in_stock']);
	}

	/**
	 * @return array{products: array<int, array<string, mixed>>, total: int}
	 */
	public function listProductsForPos(int $idCategory, string $query, int $page, int $limit): array
	{
		$page = max(1, $page);
		$limit = max(1, min(48, $limit));
		$offset = ($page - 1) * $limit;

		if (!$this->hideOutOfStock()) {
			$rows = $this->queryProductsRaw($idCategory, $query, $limit, $offset);

			return [
				'products' => $this->formatProductsForPos($rows),
				'total' => $this->countProductsRaw($idCategory, $query),
			];
		}

		$batchSize = max($limit * 4, 40);
		$scanOffset = 0;
		$inStockRows = [];
		$needCount = $offset + $limit;

		while (count($inStockRows) < $needCount) {
			$batch = $this->queryProductsRaw($idCategory, $query, $batchSize, $scanOffset);

			if ($batch === []) {
				break;
			}

			foreach ($batch as $product) {
				if ($this->isProductInStockForPos($product)) {
					$inStockRows[] = $product;
				}
			}

			$scanOffset += $batchSize;

			if (count($batch) < $batchSize) {
				break;
			}
		}

		return [
			'products' => $this->formatProductsForPos(array_slice($inStockRows, $offset, $limit)),
			'total' => $this->countInStockProductsForPos($idCategory, $query),
		];
	}

	public function countInStockProductsForPos(int $idCategory, string $query): int
	{
		$batchSize = 100;
		$scanOffset = 0;
		$count = 0;

		while (true) {
			$batch = $this->queryProductsRaw($idCategory, $query, $batchSize, $scanOffset);

			if ($batch === []) {
				break;
			}

			foreach ($batch as $product) {
				if ($this->isProductInStockForPos($product)) {
					$count++;
				}
			}

			$scanOffset += $batchSize;

			if (count($batch) < $batchSize) {
				break;
			}
		}

		return $count;
	}

	/** @return array<string, mixed> */
	public function getTodayStats(): array
	{
		$rows = DB::execute(
			"SELECT payment_method, status, COUNT(*) AS cnt, COALESCE(SUM(total), 0) AS total_sum
			FROM orders
			WHERE note LIKE '[POS]%'
			AND DATE(date_add) = CURDATE()
			GROUP BY payment_method, status"
		) ?: [];

		$stats = [
			'cash' => ['count' => 0, 'total' => 0.0, 'total_formatted' => Tools::displayPrice(0)],
			'card' => ['count' => 0, 'total' => 0.0, 'total_formatted' => Tools::displayPrice(0)],
			'transfer_ok' => ['count' => 0, 'total' => 0.0, 'total_formatted' => Tools::displayPrice(0)],
			'transfer_pending' => ['count' => 0, 'total' => 0.0, 'total_formatted' => Tools::displayPrice(0)],
		];

		foreach ($rows as $row) {
			$method = (string) ($row['payment_method'] ?? '');
			$status = (int) ($row['status'] ?? 0);
			$cnt = (int) ($row['cnt'] ?? 0);
			$sum = (float) ($row['total_sum'] ?? 0);

			if ($method === self::PAYMENT_CASH) {
				$stats['cash']['count'] += $cnt;
				$stats['cash']['total'] += $sum;
			} elseif ($method === self::PAYMENT_CARD) {
				$stats['card']['count'] += $cnt;
				$stats['card']['total'] += $sum;
			} elseif ($method === self::PAYMENT_TRANSFER) {
				if ($status === Order::STATUS_PENDING) {
					$stats['transfer_pending']['count'] += $cnt;
					$stats['transfer_pending']['total'] += $sum;
				} else {
					$stats['transfer_ok']['count'] += $cnt;
					$stats['transfer_ok']['total'] += $sum;
				}
			}
		}

		foreach ($stats as &$entry) {
			$entry['total_formatted'] = Tools::displayPrice($entry['total']);
		}
		unset($entry);

		return $stats;
	}

	public function getTerminalUserName(): string
	{
		if (!empty($_SESSION['id_admin'])) {
			$row = DB::getRowSafe('admins', 'id_admin = ? AND active = 1', [(int) $_SESSION['id_admin']]);

			if ($row && trim((string) ($row['full_name'] ?? '')) !== '') {
				return (string) $row['full_name'];
			}
		}

		return 'Kasiyer';
	}

	/** @return array<int, array{id_category: int, name: string, icon: string, color: string}> */
	public function getCategoryList(): array
	{
		$rows = DB::execute(
			'SELECT id_category, category_name FROM categories WHERE active = 1 ORDER BY category_name ASC'
		) ?: [];

		$palette = ['#14b8a6', '#0ea5e9', '#8b5cf6', '#f59e0b', '#ec4899', '#6366f1', '#10b981', '#ef4444'];
		$list = [
			['id_category' => 0, 'name' => 'Tüm Ürünler', 'icon' => 'grid', 'color' => '#14b8a6'],
		];

		foreach ($rows as $i => $row) {
			$name = (string) ($row['category_name'] ?? '');
			$list[] = [
				'id_category' => (int) $row['id_category'],
				'name' => $name,
				'icon' => mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8')),
				'color' => $palette[$i % count($palette)],
			];
		}

		return $list;
	}

	public function saveSettings(array $post): array
	{
		$enabled = !empty($post['pos_enabled']) ? '1' : '0';
		$storeLabel = mb_substr(trim(strip_tags((string) ($post['pos_store_label'] ?? ''))), 0, 64);
		$status = (int) ($post['pos_order_status'] ?? Order::STATUS_DELIVERED);
		$newPin = trim((string) ($post['pos_pin'] ?? ''));
		$confirmPin = trim((string) ($post['pos_pin_confirm'] ?? ''));
		$cardUrl = trim((string) ($post['pos_card_url'] ?? ''));

		if (!isset(Order::getStatusOptions()[$status])) {
			$status = Order::STATUS_DELIVERED;
		}

		if ($storeLabel === '') {
			$storeLabel = 'Mağaza Satış';
		}

		if ($newPin !== '' || $confirmPin !== '') {
			if (!preg_match('/^\d{4,8}$/', $newPin)) {
				return ['success' => false, 'message' => 'PIN 4–8 haneli rakamlardan oluşmalı'];
			}

			if ($newPin !== $confirmPin) {
				return ['success' => false, 'message' => 'PIN tekrarı eşleşmiyor'];
			}

			Settings::set(self::SET_PIN_HASH, password_hash($newPin, PASSWORD_DEFAULT));
		}

		Settings::set(self::SET_ENABLED, $enabled);
		Settings::set(self::SET_ORDER_STATUS, (string) $status);
		Settings::set(self::SET_STORE_LABEL, $storeLabel);

		if ($cardUrl !== '' && !preg_match('~^https?://~i', $cardUrl)) {
			return ['success' => false, 'message' => 'Kart POS URL http veya https ile başlamalı'];
		}

		Settings::set(self::SET_CARD_URL, $cardUrl);
		Settings::set(self::SET_FULLSCREEN_AUTO, !empty($post['pos_fullscreen_auto']) ? '1' : '0');
		Settings::set(self::SET_HIDE_OUT_OF_STOCK, !empty($post['pos_hide_out_of_stock']) ? '1' : '0');
		Settings::set(self::SET_ALLOW_OUT_OF_STOCK_SALE, !empty($post['pos_allow_out_of_stock_sale']) ? '1' : '0');

		$cardAdjType = $this->normalizeAdjustmentType((string) ($post['pos_card_adj_type'] ?? 'none'));
		$transferAdjType = $this->normalizeAdjustmentType((string) ($post['pos_transfer_adj_type'] ?? 'none'));
		$cardAdjPercent = max(0.0, min(100.0, (float) str_replace(',', '.', (string) ($post['pos_card_adj_percent'] ?? '0'))));
		$transferAdjPercent = max(0.0, min(100.0, (float) str_replace(',', '.', (string) ($post['pos_transfer_adj_percent'] ?? '0'))));

		if ($cardAdjType === 'none') {
			$cardAdjPercent = 0.0;
		}

		if ($transferAdjType === 'none') {
			$transferAdjPercent = 0.0;
		}

		Settings::set(self::SET_CARD_ADJ_TYPE, $cardAdjType);
		Settings::set(self::SET_CARD_ADJ_PERCENT, (string) $cardAdjPercent);
		Settings::set(self::SET_TRANSFER_ADJ_TYPE, $transferAdjType);
		Settings::set(self::SET_TRANSFER_ADJ_PERCENT, (string) $transferAdjPercent);

		return ['success' => true, 'message' => 'Ayarlar kaydedildi'];
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken, $domain, $adminUrl;

		$flash = '';
		$flashType = 'success';

		if (Tools::isSubmit('savePos')) {
			$postToken = (string) Tools::getValue('token');

			if (!hash_equals($adminToken, $postToken)) {
				$flash = 'Geçersiz istek';
				$flashType = 'danger';
			} else {
				$result = $this->saveSettings($_POST);
				$flash = $result['message'];
				$flashType = !empty($result['success']) ? 'success' : 'danger';
			}
		}

		$statusOptions = [];
		foreach (Order::getStatusOptions() as $id => $label) {
			$statusOptions[] = ['id' => $id, 'label' => $label];
		}

		$smarty->assign([
			'posEnabled' => $this->isEnabled(),
			'posHasPin' => $this->hasPinConfigured(),
			'posStoreLabel' => Settings::get(self::SET_STORE_LABEL, 'Mağaza Satış'),
			'posOrderStatus' => (int) Settings::get(self::SET_ORDER_STATUS, (string) Order::STATUS_DELIVERED),
			'posStatusOptions' => $statusOptions,
			'posFrontUrl' => rtrim($domain, '/') . '/pos',
			'posAdminUrl' => rtrim($adminUrl, '/') . '/pos',
			'posCardUrl' => $this->resolveCardUrl(),
			'posCardUrlSuggestions' => $this->getCardUrlSuggestions(),
			'posFullscreenAuto' => $this->isFullscreenAuto(),
			'posHideOutOfStock' => $this->hideOutOfStock(),
			'posAllowOutOfStockSale' => $this->allowOutOfStockSale(),
			'posCardAdjType' => $this->normalizeAdjustmentType((string) Settings::get(self::SET_CARD_ADJ_TYPE, 'none')),
			'posCardAdjPercent' => $this->getAdjustmentPercent(self::SET_CARD_ADJ_PERCENT),
			'posTransferAdjType' => $this->normalizeAdjustmentType((string) Settings::get(self::SET_TRANSFER_ADJ_TYPE, 'none')),
			'posTransferAdjPercent' => $this->getAdjustmentPercent(self::SET_TRANSFER_ADJ_PERCENT),
			'posFlash' => $flash,
			'posFlashType' => $flashType,
		]);
	}

	public function renderTerminalPage(): void
	{
		global $smarty, $domain, $adminUrl;

		if (!$this->isEnabled()) {
			http_response_code(503);
			echo '<!DOCTYPE html><html lang="tr"><head><meta charset="utf-8"><title>POS</title></head><body><p>POS modülü kapalı.</p></body></html>';
			exit;
		}

		$loginError = '';
		$lockError = '';

		if (Tools::isSubmit('posUnlock')) {
			$result = $this->unlockScreen((string) Tools::getValue('pos_pin'));
			$lockError = !empty($result['success']) ? '' : $result['message'];

			if (!empty($result['success'])) {
				$redirect = defined('IN_ADMIN')
					? rtrim($adminUrl, '/') . '/pos'
					: rtrim($domain, '/') . '/pos';
				header('Location: ' . $redirect);
				exit;
			}
		}

		if (Tools::isSubmit('posLogin')) {
			$result = $this->authorizeWithPin((string) Tools::getValue('pos_pin'));
			$loginError = !empty($result['success']) ? '' : $result['message'];

			if (!empty($result['success'])) {
				$redirect = defined('IN_ADMIN')
					? rtrim($adminUrl, '/') . '/pos'
					: rtrim($domain, '/') . '/pos';
				header('Location: ' . $redirect);
				exit;
			}
		}

		if (Tools::isSubmit('posLogout')) {
			$this->logout();
			$redirect = defined('IN_ADMIN')
				? rtrim($adminUrl, '/') . '/pos'
				: rtrim($domain, '/') . '/pos';
			header('Location: ' . $redirect);
			exit;
		}

		if (!$this->isAuthorized()) {
			if ($this->hasTerminalAccess() && $this->isScreenLocked()) {
				$this->renderLockPage($lockError);

				return;
			}

			$this->renderLoginPage($loginError);

			return;
		}

		$categories = $this->getCategoryList();

		$isAdminContext = !empty($_SESSION['id_admin']);
		$exitUrl = $isAdminContext ? rtrim($adminUrl, '/') . '/' : rtrim($domain, '/') . '/';
		$customer = $this->getSessionCustomer();

		$html = $this->renderFrontTemplate('pos', [
			'posApiBase' => rtrim($domain, '/') . '/api/module.php?m=pos&action=',
			'posToken' => $this->getApiToken(),
			'posCategories' => $categories,
			'posStoreLabel' => Settings::get(self::SET_STORE_LABEL, 'Mağaza Satış'),
			'posCssUrl' => $this->getAssetUrl('css/pos.css'),
			'posJsUrl' => $this->getAssetUrl('js/pos.js'),
			'posCardUrl' => $this->resolveCardUrl(),
			'posFullscreenAuto' => $this->isFullscreenAuto(),
			'posHideOutOfStock' => $this->hideOutOfStock(),
			'posAllowOutOfStockSale' => $this->allowOutOfStockSale(),
			'posPaymentAdjustmentsJson' => json_encode($this->getPaymentAdjustmentConfig(), JSON_UNESCAPED_UNICODE),
			'posIsAdmin' => $isAdminContext,
			'posExitUrl' => $exitUrl,
			'posAdminUrl' => rtrim($adminUrl, '/'),
			'posSiteName' => Settings::get('SITE_NAME', 'FShop'),
			'posUserName' => $this->getTerminalUserName(),
			'posCustomer' => $customer,
			'posHasPin' => $this->hasPinConfigured(),
			'posHasCardGateway' => $this->hasOnlineCardGateway(),
		]);

		header('Content-Type: text/html; charset=utf-8');
		echo $html;
		exit;
	}

	private function renderLoginPage(string $error): void
	{
		global $domain, $adminUrl;

		$html = $this->renderFrontTemplate('pos_login', [
			'posError' => $error,
			'posCssUrl' => $this->getAssetUrl('css/pos.css'),
			'posHasPin' => $this->hasPinConfigured(),
			'posIsAdmin' => !empty($_SESSION['id_admin']),
			'posAdminConfigUrl' => rtrim($adminUrl, '/') . '/module-pos',
			'posSiteName' => Settings::get('SITE_NAME', 'FShop'),
			'posBackUrl' => defined('IN_ADMIN') ? rtrim($adminUrl, '/') . '/login' : rtrim($domain, '/') . '/',
			'posToken' => $this->getApiToken(),
		]);

		header('Content-Type: text/html; charset=utf-8');
		echo $html;
		exit;
	}

	private function renderLockPage(string $error): void
	{
		global $domain;

		$html = $this->renderFrontTemplate('pos_lock', [
			'posError' => $error,
			'posCssUrl' => $this->getAssetUrl('css/pos.css'),
			'posSiteName' => Settings::get('SITE_NAME', 'FShop'),
			'posToken' => $this->getApiToken(),
		]);

		header('Content-Type: text/html; charset=utf-8');
		echo $html;
		exit;
	}

	/** @return array<int, array<string, mixed>> */
	public function formatProductsForPos(array $products): array
	{
		$rows = [];

		foreach ($products as $product) {
			$rows[] = $this->formatProductRow($product);
		}

		return $rows;
	}

	/** @param array<string, mixed> $product */
	public function formatProductRow(array $product): array
	{
		$idProduct = (int) ($product['id_product'] ?? 0);
		$price = (float) ($product['price'] ?? 0);
		$hasVariations = !empty($product['has_variations']);

		return [
			'id_product' => $idProduct,
			'product_name' => (string) ($product['product_name'] ?? ''),
			'price' => $price,
			'price_formatted' => (string) ($product['price_formatted'] ?? Tools::displayPrice($price)),
			'image_url' => (string) ($product['image_url'] ?? Product::getImageUrl((int) ($product['id_image'] ?? 0))),
			'category_name' => (string) ($product['category_name'] ?? ''),
			'has_variations' => $hasVariations,
			'in_stock' => !empty($product['in_stock']),
			'stock' => (int) ($product['stock'] ?? 0),
		];
	}

	public function getProductDetail(int $idProduct): ?array
	{
		$product = Product::getById($idProduct);

		if (!$product || (int) ($product['active'] ?? 0) !== 1) {
			return null;
		}

		$row = $this->formatProductRow($product);
		$variationData = ProductVariation::getForStorefront($idProduct, (float) $product['price']);
		$variations = [];

		foreach ($variationData['items'] ?? [] as $item) {
			$idVariation = (int) ($item['id_variation'] ?? 0);
			$variation = ProductVariation::getById($idVariation);

			$variations[] = [
				'id_variation' => $idVariation,
				'label' => $variation ? ProductVariation::formatLabel($variation) : '',
				'price' => (float) ($item['price'] ?? 0),
				'price_formatted' => (string) ($item['price_formatted'] ?? ''),
				'in_stock' => !empty($item['in_stock']),
				'stock' => (int) ($item['stock'] ?? 0),
			];
		}

		$row['has_variations'] = !empty($variationData['has_variations']);
		$row['variations'] = $variations;

		return $row;
	}

	/** @return array<string, mixed> */
	public function getCartSummary(): array
	{
		$items = $this->getCartItems();
		$subtotal = 0.0;

		foreach ($items as $item) {
			$subtotal += (float) $item['line_total'];
		}

		return [
			'items' => $items,
			'count' => array_sum(array_column($items, 'qty')),
			'subtotal' => $subtotal,
			'subtotal_formatted' => Tools::displayPrice($subtotal),
			'empty' => $items === [],
		];
	}

	/** @return array<int, array<string, mixed>> */
	private function getCartItems(): array
	{
		$cart = $_SESSION[self::SESSION_CART] ?? [];

		return is_array($cart) ? array_values($cart) : [];
	}

	private function saveCartItems(array $items): void
	{
		$_SESSION[self::SESSION_CART] = $items;
	}

	private function cartItemKey(int $idProduct, int $idVariation): string
	{
		return $idProduct . ':' . $idVariation;
	}

	/**
	 * Barkod / stok kodu / varyasyon SKU ile ürün bulur.
	 * @return array{id_product: int, id_variation: int}|null
	 */
	public function resolveByBarcode(string $code): ?array
	{
		$code = trim($code);

		if ($code === '') {
			return null;
		}

		$variationRows = DB::execute(
			'SELECT pv.id_variation, pv.id_product
			FROM product_variations pv
			INNER JOIN products p ON p.id_product = pv.id_product AND p.active = 1
			WHERE pv.active = 1 AND (pv.barcode = ? OR pv.sku = ?)
			LIMIT 1',
			[$code, $code]
		);
		$variationRow = ($variationRows && isset($variationRows[0])) ? $variationRows[0] : null;

		if ($variationRow) {
			return [
				'id_product' => (int) $variationRow['id_product'],
				'id_variation' => (int) $variationRow['id_variation'],
			];
		}

		$productId = (int) DB::getValue(
			'SELECT id_product FROM products
			WHERE active = 1 AND (barcode = ? OR stock_code = ?)
			LIMIT 1',
			[$code, $code]
		);

		if ($productId <= 0) {
			return null;
		}

		return [
			'id_product' => $productId,
			'id_variation' => 0,
		];
	}

	public function addByBarcode(string $code, int $qty = 1): array
	{
		$match = $this->resolveByBarcode($code);

		if (!$match) {
			return ['success' => false, 'message' => 'Barkod bulunamadı: ' . trim($code)];
		}

		return $this->addToCart(
			$match['id_product'],
			$qty,
			$match['id_variation']
		);
	}

	public function addToCart(int $idProduct, int $qty = 1, int $idVariation = 0): array
	{
		$qty = max(1, min(999, $qty));
		$product = Product::getById($idProduct);

		if (!$product || (int) ($product['active'] ?? 0) !== 1) {
			return ['success' => false, 'message' => 'Ürün bulunamadı'];
		}

		$price = (float) $product['price'];
		$variationLabel = '';

		if (ProductVariation::hasVariations($idProduct)) {
			if ($idVariation <= 0) {
				return [
					'success' => false,
					'message' => 'Varyasyon seçin',
					'needs_variation' => true,
				];
			}

			$variation = ProductVariation::getById($idVariation);

			if (!$variation || (int) $variation['id_product'] !== $idProduct || (int) $variation['active'] !== 1) {
				return ['success' => false, 'message' => 'Geçersiz varyasyon'];
			}

			$price = ProductVariation::getEffectivePrice($variation, (float) $product['price']);
			$variationLabel = ProductVariation::formatLabel($variation);
		} elseif ($idVariation > 0) {
			return ['success' => false, 'message' => 'Geçersiz varyasyon'];
		}

		if (!$this->allowOutOfStockSale()) {
			if (!Product::isInStock($product, $qty, $idVariation)) {
				return ['success' => false, 'message' => 'Yetersiz stok'];
			}
		}

		$key = $this->cartItemKey($idProduct, $idVariation);
		$cart = $_SESSION[self::SESSION_CART] ?? [];

		if (!is_array($cart)) {
			$cart = [];
		}

		$newQty = $qty;

		if (isset($cart[$key])) {
			$newQty = (int) $cart[$key]['qty'] + $qty;
		}

		if (!$this->allowOutOfStockSale() && !Product::isInStock($product, $newQty, $idVariation)) {
			return ['success' => false, 'message' => 'Yetersiz stok'];
		}

		$productName = (string) $product['product_name'];

		$cart[$key] = [
			'key' => $key,
			'id_product' => $idProduct,
			'id_variation' => $idVariation,
			'product_name' => $productName,
			'variation_label' => $variationLabel,
			'price' => $price,
			'price_formatted' => Tools::displayPrice($price),
			'qty' => $newQty,
			'line_total' => $price * $newQty,
			'line_total_formatted' => Tools::displayPrice($price * $newQty),
		];

		$this->saveCartItems($cart);

		return [
			'success' => true,
			'message' => 'Sepete eklendi',
			'cart' => $this->getCartSummary(),
		];
	}

	public function updateCartQty(string $key, int $qty): array
	{
		$cart = $_SESSION[self::SESSION_CART] ?? [];

		if (!is_array($cart) || !isset($cart[$key])) {
			return ['success' => false, 'message' => 'Sepet satırı bulunamadı'];
		}

		if ($qty <= 0) {
			unset($cart[$key]);
			$this->saveCartItems($cart);

			return [
				'success' => true,
				'message' => 'Ürün kaldırıldı',
				'cart' => $this->getCartSummary(),
			];
		}

		$qty = min(999, $qty);
		$item = $cart[$key];
		$product = Product::getById((int) $item['id_product']);

		if (!$product) {
			unset($cart[$key]);
			$this->saveCartItems($cart);

			return ['success' => false, 'message' => 'Ürün artık mevcut değil'];
		}

		if (!$this->allowOutOfStockSale() && !Product::isInStock($product, $qty, (int) ($item['id_variation'] ?? 0))) {
			return ['success' => false, 'message' => 'Yetersiz stok'];
		}

		$price = (float) $item['price'];
		$item['qty'] = $qty;
		$item['line_total'] = $price * $qty;
		$item['line_total_formatted'] = Tools::displayPrice($price * $qty);
		$cart[$key] = $item;
		$this->saveCartItems($cart);

		return [
			'success' => true,
			'message' => 'Sepet güncellendi',
			'cart' => $this->getCartSummary(),
		];
	}

	public function removeFromCart(string $key): array
	{
		return $this->updateCartQty($key, 0);
	}

	public function clearCart(): array
	{
		unset($_SESSION[self::SESSION_CART]);

		return [
			'success' => true,
			'message' => 'Sepet temizlendi',
			'cart' => $this->getCartSummary(),
		];
	}

	public static function getPaymentLabel(string $method): string
	{
		$labels = [
			self::PAYMENT_CASH => 'POS — Nakit',
			self::PAYMENT_CARD => 'POS — Kart',
			self::PAYMENT_TRANSFER => 'POS — Havale',
		];

		return $labels[$method] ?? $method;
	}

	public function createSale(
		string $payment,
		string $customerName,
		string $customerPhone,
		string $note,
		float $cashPaid = 0.0,
		int $idUser = -1
	): array {
		Order::ensureSchema();

		$cart = $this->getCartSummary();

		if (!empty($cart['empty'])) {
			return ['success' => false, 'message' => 'Sepet boş'];
		}

		if (!in_array($payment, [self::PAYMENT_CASH, self::PAYMENT_CARD, self::PAYMENT_TRANSFER], true)) {
			return ['success' => false, 'message' => 'Geçersiz ödeme yöntemi'];
		}

		$sessionCustomer = $this->getSessionCustomer();

		if ($idUser < 0) {
			$idUser = (int) $sessionCustomer['id_user'];
		}

		if ($idUser > 0) {
			$user = Customer::getByIdAdmin($idUser);

			if ($user) {
				$customerName = trim((string) ($user['user_full_name'] ?? ''));
				$customerPhone = Customer::normalizePhone((string) ($user['phone'] ?? ''));
				$customerEmail = trim((string) ($user['email'] ?? ''));
			} else {
				$idUser = 0;
				$customerEmail = '';
			}
		} else {
			$customerEmail = trim((string) ($sessionCustomer['email'] ?? ''));
		}

		$customerName = mb_substr(trim(strip_tags($customerName)), 0, 128);
		$storeLabel = (string) Settings::get(self::SET_STORE_LABEL, 'Mağaza Satış');

		if ($customerName === '') {
			$customerName = $idUser > 0 ? 'Müşteri' : 'Ziyaretçi';
		}

		$customerPhone = Customer::normalizePhone($customerPhone);

		if ($customerPhone === '' || !Customer::isValidPhone($customerPhone)) {
			$customerPhone = '05000000000';
		}

		if (!isset($customerEmail)) {
			$customerEmail = '';
		}

		$note = trim(strip_tags($note));
		$posNote = '[POS] ' . self::getPaymentLabel($payment);

		$subtotal = (float) $cart['subtotal'];
		$payable = $this->calculatePaymentTotal($subtotal, $payment);
		$orderTotal = (float) $payable['total'];

		if (abs((float) $payable['adjustment']) > 0.009 && ($payable['adjustment_label'] ?? '') !== '') {
			$posNote .= ' | ' . $payable['adjustment_label'] . ': ' . $payable['adjustment_signed_formatted'];
		}

		if ($payment === self::PAYMENT_CASH && $cashPaid > 0) {
			$change = max(0.0, $cashPaid - $orderTotal);
			$posNote .= ' | Alınan: ' . Tools::displayPrice($cashPaid)
				. ' | Para üstü: ' . Tools::displayPrice($change);
		}

		if ($note !== '') {
			$posNote .= ' — ' . $note;
		}

		$status = (int) Settings::get(self::SET_ORDER_STATUS, (string) Order::STATUS_DELIVERED);

		if ($payment === self::PAYMENT_TRANSFER) {
			$status = Order::STATUS_PENDING;
		} elseif (!isset(Order::getStatusOptions()[$status])) {
			$status = Order::STATUS_DELIVERED;
		}

		$subtotal = (float) $cart['subtotal'];

		if ($payment === self::PAYMENT_CASH && $cashPaid > 0 && $cashPaid + 0.009 < $orderTotal) {
			return ['success' => false, 'message' => 'Alınan tutar toplamdan az'];
		}

		$reference = Order::reserveReference();
		global $db;

		$allowOutOfStock = $this->allowOutOfStockSale();

		try {
			$db->beginTransaction();

			foreach ($cart['items'] as $item) {
				$idVariation = (int) ($item['id_variation'] ?? 0);
				$product = Product::getById((int) $item['id_product']);

				if (!$product) {
					throw new RuntimeException('Ürün bulunamadı: ' . ($item['product_name'] ?? ''));
				}

				if (!$allowOutOfStock && !Product::isInStock($product, (int) $item['qty'], $idVariation)) {
					throw new RuntimeException('Stok yetersiz: ' . ($item['product_name'] ?? ''));
				}

				if (!$allowOutOfStock) {
					if (!Product::decreaseStock((int) $item['id_product'], (int) $item['qty'], $idVariation)) {
						throw new RuntimeException('Stok güncellenemedi: ' . ($item['product_name'] ?? ''));
					}
				} else {
					Product::decreaseStock((int) $item['id_product'], (int) $item['qty'], $idVariation);
				}
			}

			$idOrder = DB::insert('orders', [
				'id_user' => max(0, $idUser),
				'reference' => $reference,
				'status' => $status,
				'payment_method' => $payment,
				'customer_name' => $customerName,
				'customer_phone' => $customerPhone,
				'customer_email' => $customerEmail,
				'address_city' => $storeLabel,
				'address_district' => 'Mağaza',
				'address_text' => 'Mağaza içi satış (POS)',
				'note' => $posNote,
				'coupon_code' => '',
				'coupon_discount' => 0,
				'subtotal' => $subtotal,
				'shipping' => 0,
				'total' => $orderTotal,
			]);

			if (!$idOrder) {
				throw new RuntimeException('Sipariş kaydedilemedi');
			}

			foreach ($cart['items'] as $item) {
				$ok = DB::insert('order_detail', [
					'id_order' => (int) $idOrder,
					'id_product' => (int) $item['id_product'],
					'id_variation' => (int) ($item['id_variation'] ?? 0),
					'product_name' => (string) $item['product_name'],
					'variation_label' => (string) ($item['variation_label'] ?? ''),
					'price' => (float) $item['price'],
					'qty' => (int) $item['qty'],
					'total' => (float) $item['line_total'],
				]);

				if (!$ok) {
					throw new RuntimeException('Sipariş satırı kaydedilemedi');
				}
			}

			$db->commit();
		} catch (Throwable $e) {
			if ($db->inTransaction()) {
				$db->rollBack();
			}

			return ['success' => false, 'message' => $e->getMessage()];
		}

		$this->clearCart();
		$this->resetSessionCustomer();

		if (Order::isPaymentAccepted($status)) {
			VirtualProduct::fulfillOrder((int) $idOrder);
		}

		$placedOrder = Order::getByIdAdmin((int) $idOrder);

		if ($placedOrder) {
			Module::runHook('order.placed', [$placedOrder]);

			if ($status !== Order::STATUS_PENDING) {
				Module::runHook('order.updated', [$placedOrder, Order::STATUS_PENDING]);
			}
		}

		$change = $payment === self::PAYMENT_CASH && $cashPaid > 0
			? max(0.0, $cashPaid - $orderTotal)
			: 0.0;

		return [
			'success' => true,
			'message' => 'Satış tamamlandı',
			'id_order' => (int) $idOrder,
			'reference' => $reference,
			'total' => $orderTotal,
			'total_formatted' => Tools::displayPrice($orderTotal),
			'payment_label' => self::getPaymentLabel($payment),
			'payment_method' => $payment,
			'cart' => $this->getCartSummary(),
			'receipt' => $this->buildReceiptPayload(
				$reference,
				(int) $idOrder,
				$cart['items'],
				$customerName,
				$customerPhone,
				$payment,
				$subtotal,
				$cashPaid,
				$change,
				$payable
			),
		];
	}

	/** @param array<string, mixed>|null $payable */
	public function buildReceiptPayload(
		string $reference,
		int $idOrder,
		array $cartItems,
		string $customerName,
		string $customerPhone,
		string $payment,
		float $subtotal,
		float $cashPaid = 0.0,
		float $change = 0.0,
		?array $payable = null
	): array {
		$payable = $payable ?? $this->calculatePaymentTotal($subtotal, $payment);
		$items = [];

		foreach ($cartItems as $item) {
			$items[] = [
				'product_name' => (string) ($item['product_name'] ?? ''),
				'variation_label' => (string) ($item['variation_label'] ?? ''),
				'qty' => (int) ($item['qty'] ?? 0),
				'price_formatted' => (string) ($item['price_formatted'] ?? ''),
				'line_total_formatted' => (string) ($item['line_total_formatted'] ?? ''),
			];
		}

		return [
			'reference' => $reference,
			'id_order' => $idOrder,
			'customer_name' => $customerName,
			'customer_phone' => $customerPhone,
			'payment_method' => $payment,
			'payment_label' => self::getPaymentLabel($payment),
			'items' => $items,
			'item_count' => array_sum(array_column($items, 'qty')),
			'subtotal' => (float) $payable['subtotal'],
			'subtotal_formatted' => (string) $payable['subtotal_formatted'],
			'adjustment' => (float) ($payable['adjustment'] ?? 0),
			'adjustment_formatted' => (string) ($payable['adjustment_formatted'] ?? ''),
			'adjustment_signed_formatted' => (string) ($payable['adjustment_signed_formatted'] ?? ''),
			'adjustment_label' => (string) ($payable['adjustment_label'] ?? ''),
			'total' => (float) $payable['total'],
			'total_formatted' => (string) $payable['total_formatted'],
			'cash_paid' => $cashPaid,
			'cash_paid_formatted' => $cashPaid > 0 ? Tools::displayPrice($cashPaid) : '',
			'change' => $change,
			'change_formatted' => $change > 0 ? Tools::displayPrice($change) : '',
			'date' => date('d.m.Y H:i'),
			'store_label' => (string) Settings::get(self::SET_STORE_LABEL, 'Mağaza Satış'),
			'cashier' => $this->getTerminalUserName(),
			'site_name' => (string) Settings::get('SITE_NAME', 'FShop'),
		];
	}

	public function getReceiptByReference(string $reference): ?array
	{
		$reference = trim($reference);

		if ($reference === '' || !Order::isValidPublicReference($reference)) {
			return null;
		}

		$rows = DB::execute(
			'SELECT * FROM orders WHERE reference = ? AND note LIKE ? LIMIT 1',
			[$reference, '[POS]%']
		);
		$order = ($rows && isset($rows[0])) ? $rows[0] : null;

		if (!$order) {
			return null;
		}

		$idOrder = (int) ($order['id_order'] ?? 0);
		$details = DB::execute(
			'SELECT product_name, variation_label, price, qty, total FROM order_detail WHERE id_order = ? ORDER BY id_order_detail ASC',
			[$idOrder]
		) ?: [];

		$cartItems = [];

		foreach ($details as $row) {
			$price = (float) ($row['price'] ?? 0);
			$qty = (int) ($row['qty'] ?? 0);
			$lineTotal = (float) ($row['total'] ?? 0);

			$cartItems[] = [
				'product_name' => (string) ($row['product_name'] ?? ''),
				'variation_label' => (string) ($row['variation_label'] ?? ''),
				'qty' => $qty,
				'price_formatted' => Tools::displayPrice($price),
				'line_total_formatted' => Tools::displayPrice($lineTotal),
			];
		}

		$payment = (string) ($order['payment_method'] ?? '');
		$orderSubtotal = (float) ($order['subtotal'] ?? 0);
		$orderTotal = (float) ($order['total'] ?? 0);
		$payable = $this->calculatePaymentTotal($orderSubtotal, $payment);
		if (abs($orderTotal - (float) $payable['total']) > 0.02) {
			$payable['total'] = $orderTotal;
			$payable['total_formatted'] = Tools::displayPrice($orderTotal);
			$payable['adjustment'] = round($orderTotal - $orderSubtotal, 2);
			$payable['adjustment_formatted'] = Tools::displayPrice(abs((float) $payable['adjustment']));
			$sign = ((float) $payable['adjustment']) >= 0 ? '+' : '−';
			$payable['adjustment_signed_formatted'] = $sign . Tools::displayPrice(abs((float) $payable['adjustment']));
		}
		$cashPaid = 0.0;
		$change = 0.0;
		$note = (string) ($order['note'] ?? '');

		if ($payment === self::PAYMENT_CASH && preg_match('/Alınan:\s*([^\|]+)/u', $note, $m)) {
			$cashPaid = (float) preg_replace('/[^\d,.-]/', '', str_replace('.', '', str_replace(',', '.', trim($m[1]))));
		}

		if ($payment === self::PAYMENT_CASH && preg_match('/Para üstü:\s*(.+)$/u', $note, $m)) {
			$change = (float) preg_replace('/[^\d,.-]/', '', str_replace('.', '', str_replace(',', '.', trim($m[1]))));
		}

		return $this->buildReceiptPayload(
			$reference,
			$idOrder,
			$cartItems,
			(string) ($order['customer_name'] ?? ''),
			(string) ($order['customer_phone'] ?? ''),
			$payment,
			$orderSubtotal,
			$cashPaid,
			$change,
			$payable
		);
	}

	public function requireApiAuth(): void
	{
		if ($this->hasTerminalAccess() && $this->isScreenLocked()) {
			http_response_code(423);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(['success' => false, 'message' => 'Ekran kilitli', 'screen_locked' => true]);
			exit;
		}

		if (!$this->isAuthorized()) {
			http_response_code(401);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(['success' => false, 'message' => 'Oturum gerekli', 'auth_required' => true]);
			exit;
		}
	}

	public function requireApiToken(): void
	{
		$token = Tools::getValue('token') ?: ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

		if (!$this->verifyApiToken((string) $token)) {
			http_response_code(403);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
			exit;
		}
	}
}
