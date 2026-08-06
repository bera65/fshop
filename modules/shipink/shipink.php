<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';

class ShipinkModule extends ModuleBase
{
	public string $name = 'shipink';
	public string $title = 'Shipink';
	public string $version = '1.0.0';
	public string $description = 'Shipink API ile kargo gönderisi oluşturur';
	public string $author = 'FShop';

	public array $displayHooks = [
		'admin_order_detail' => 'Sipariş detayında Shipink paneli',
	];
	public array $defaultDisplayHooks = ['admin_order_detail'];

	public array $apiActions = [
		'webhook' => 'api/webhook.php',
		'send' => 'api/send.php',
	];

	public function install(): bool
	{
		$ok = $this->runSqlFile('install.sql');
		Settings::set('SHIPINK_ENV', 'prod');
		Settings::set('SHIPINK_AUTO_SEND', '1');
		Settings::set('SHIPINK_PKG_WEIGHT', '1');
		Settings::set('SHIPINK_PKG_HEIGHT', '10');
		Settings::set('SHIPINK_PKG_WIDTH', '10');
		Settings::set('SHIPINK_PKG_LENGTH', '10');

		return $ok;
	}

	public function uninstall(): bool
	{
		foreach ([
			'SHIPINK_USERNAME', 'SHIPINK_PASSWORD', 'SHIPINK_ENV', 'SHIPINK_AUTO_SEND',
			'SHIPINK_WAREHOUSE_ID', 'SHIPINK_CARRIER_SERVICE_ID', 'SHIPINK_CARRIER_ACCOUNT_ID',
			'SHIPINK_CARD_ID', 'SHIPINK_LINK_TOKEN',
			'SHIPINK_ACCESS_TOKEN', 'SHIPINK_REFRESH_TOKEN', 'SHIPINK_TOKEN_EXPIRES',
			'SHIPINK_PKG_WEIGHT', 'SHIPINK_PKG_HEIGHT', 'SHIPINK_PKG_WIDTH', 'SHIPINK_PKG_LENGTH',
		] as $key) {
			Settings::set($key, '');
		}

		return $this->runSqlFile('uninstall.sql');
	}

	public function boot(): void
	{
		$module = $this;

		Module::registerHook('order.placed', function ($order) use ($module): void {
			if (!is_array($order) || !$module->isAutoSendEnabled()) {
				return;
			}

			$module->submitCargo((int) ($order['id_order'] ?? 0));
		});

		Module::registerHook('order.updated', function ($order, $oldStatus) use ($module): void {
			if (!is_array($order) || !$module->isAutoSendEnabled()) {
				return;
			}

			$newStatus = (int) ($order['status'] ?? 0);
			$oldStatus = (int) $oldStatus;

			if ($newStatus !== Order::STATUS_PROCESSING || $oldStatus === Order::STATUS_PROCESSING) {
				return;
			}

			$module->submitCargo((int) ($order['id_order'] ?? 0));
		});
	}

	public function isAutoSendEnabled(): bool
	{
		return (string) Settings::get('SHIPINK_AUTO_SEND') === '1';
	}

	public function isConfigured(): bool
	{
		return trim((string) Settings::get('SHIPINK_USERNAME')) !== ''
			&& trim((string) Settings::get('SHIPINK_PASSWORD')) !== ''
			&& trim((string) Settings::get('SHIPINK_WAREHOUSE_ID')) !== ''
			&& trim((string) Settings::get('SHIPINK_CARRIER_SERVICE_ID')) !== ''
			&& trim((string) Settings::get('SHIPINK_CARRIER_ACCOUNT_ID')) !== '';
	}

	public function renderAdminDisplayHook(string $hook, array $context = []): ?string
	{
		if ($hook !== 'admin_order_detail') {
			return null;
		}

		global $adminUrl, $adminToken;

		$idOrder = (int) ($context['id_order'] ?? Tools::getValue('id'));
		$order = is_array($context['order'] ?? null) ? $context['order'] : null;
		$cargoRow = $this->getCargoRow($idOrder);
		$preview = $idOrder > 0 ? $this->buildOrderPayload($idOrder) : null;

		$html = $this->renderAdminTemplate('admin_order_detail', [
			'id_order' => $idOrder,
			'order' => $order,
			'cargoRow' => $cargoRow,
			'orderPreview' => is_array($preview) ? $preview : null,
			'orderPreviewJson' => is_array($preview)
				? json_encode($preview, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
				: '',
			'cargoError' => is_string($preview) ? $preview : '',
			'isConfigured' => $this->isConfigured(),
			'adminModuleUrl' => rtrim((string) $adminUrl, '/') . '/module-shipink',
			'adminToken' => (string) $adminToken,
		]);

		return $html !== '' ? $html : null;
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken, $domain, $adminUrl;

		$flash = '';
		$flashType = 'success';
		$sonuc = '';
		$warehouses = [];
		$carrierAccounts = [];
		$carrierServices = [];

		if (Tools::isSubmit('sendOrder')) {
			$postToken = (string) Tools::getValue('token');

			if (hash_equals((string) $adminToken, $postToken)) {
				$idOrder = (int) Tools::getValue('id_order');
				$error = $this->submitCargo($idOrder, true);

				if ($error === null) {
					header('Location: ' . rtrim((string) $adminUrl, '/') . '/order?id=' . $idOrder . '&shipink=1');
					exit;
				}

				$flash = $error;
				$flashType = 'danger';
			} else {
				$flash = 'Geçersiz istek';
				$flashType = 'danger';
			}
		}

		if (Tools::isSubmit('saveShipink')) {
			$postToken = (string) Tools::getValue('saveShipink');

			if (hash_equals((string) $adminToken, $postToken)) {
				Settings::set('SHIPINK_USERNAME', trim((string) Tools::getValue('shipink_username')));
				$password = trim((string) Tools::getValue('shipink_password'));

				if ($password !== '') {
					Settings::set('SHIPINK_PASSWORD', $password);
				}

				$env = Tools::getValue('shipink_env') === 'dev' ? 'dev' : 'prod';
				Settings::set('SHIPINK_ENV', $env);
				Settings::set('SHIPINK_AUTO_SEND', Tools::getValue('shipink_auto_send') ? '1' : '0');
				Settings::set('SHIPINK_WAREHOUSE_ID', trim((string) Tools::getValue('shipink_warehouse_id')));
				Settings::set('SHIPINK_CARRIER_SERVICE_ID', trim((string) Tools::getValue('shipink_carrier_service_id')));
				Settings::set('SHIPINK_CARRIER_ACCOUNT_ID', trim((string) Tools::getValue('shipink_carrier_account_id')));
				Settings::set('SHIPINK_CARD_ID', trim((string) Tools::getValue('shipink_card_id')));
				Settings::set('SHIPINK_LINK_TOKEN', preg_replace('/[^a-zA-Z0-9]/', '', (string) Tools::getValue('shipink_link_token')));
				Settings::set('SHIPINK_PKG_WEIGHT', (string) max(0.1, (float) str_replace(',', '.', (string) Tools::getValue('shipink_pkg_weight'))));
				Settings::set('SHIPINK_PKG_HEIGHT', (string) max(0.1, (float) str_replace(',', '.', (string) Tools::getValue('shipink_pkg_height'))));
				Settings::set('SHIPINK_PKG_WIDTH', (string) max(0.1, (float) str_replace(',', '.', (string) Tools::getValue('shipink_pkg_width'))));
				Settings::set('SHIPINK_PKG_LENGTH', (string) max(0.1, (float) str_replace(',', '.', (string) Tools::getValue('shipink_pkg_length'))));
				Settings::set('SHIPINK_ACCESS_TOKEN', '');
				Settings::set('SHIPINK_REFRESH_TOKEN', '');
				Settings::set('SHIPINK_TOKEN_EXPIRES', '0');
				$flash = 'Shipink ayarları kaydedildi';
			} else {
				$flash = 'Geçersiz istek';
				$flashType = 'danger';
			}
		}

		if (Tools::isSubmit('testConnection')) {
			$postToken = (string) Tools::getValue('testConnection');

			if (hash_equals((string) $adminToken, $postToken)) {
				$tokenResult = $this->authenticate(true);
				$sonuc = json_encode($tokenResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

				if (!empty($tokenResult['success'])) {
					$flash = 'Bağlantı başarılı — token alındı';
				} else {
					$flash = (string) ($tokenResult['message'] ?? 'Bağlantı başarısız');
					$flashType = 'danger';
				}
			}
		}

		if (Tools::isSubmit('refreshLists')) {
			$postToken = (string) Tools::getValue('refreshLists');

			if (hash_equals((string) $adminToken, $postToken)) {
				$warehouses = $this->apiGetList('warehouses');
				$carrierAccounts = $this->apiGetList('carrier-accounts');
				$carrierId = trim((string) Tools::getValue('carrier_filter'));
				$path = 'carrier-services' . ($carrierId !== '' ? ('?carrier_id=' . rawurlencode($carrierId)) : '');
				$carrierServices = $this->apiGetList($path);
				$flash = 'Listeler güncellendi';
			}
		} elseif ($this->isConfigured() || (trim((string) Settings::get('SHIPINK_USERNAME')) !== '' && trim((string) Settings::get('SHIPINK_PASSWORD')) !== '')) {
			$warehouses = $this->apiGetList('warehouses');
			$carrierAccounts = $this->apiGetList('carrier-accounts');
			$svcId = trim((string) Settings::get('SHIPINK_CARRIER_SERVICE_ID'));
			$carrierFilter = $svcId !== '' && strpos($svcId, '_') !== false
				? explode('_', $svcId, 2)[0]
				: '';
			$path = 'carrier-services' . ($carrierFilter !== '' ? ('?carrier_id=' . rawurlencode($carrierFilter)) : '');
			$carrierServices = $this->apiGetList($path);
		}

		$linkToken = (string) Settings::get('SHIPINK_LINK_TOKEN');

		$smarty->assign([
			'shipinkUsername' => Settings::get('SHIPINK_USERNAME'),
			'shipinkHasPassword' => trim((string) Settings::get('SHIPINK_PASSWORD')) !== '',
			'shipinkEnv' => Settings::get('SHIPINK_ENV') ?: 'prod',
			'shipinkAutoSend' => Settings::get('SHIPINK_AUTO_SEND') === '1',
			'shipinkWarehouseId' => Settings::get('SHIPINK_WAREHOUSE_ID'),
			'shipinkCarrierServiceId' => Settings::get('SHIPINK_CARRIER_SERVICE_ID'),
			'shipinkCarrierAccountId' => Settings::get('SHIPINK_CARRIER_ACCOUNT_ID'),
			'shipinkCardId' => Settings::get('SHIPINK_CARD_ID'),
			'shipinkLinkToken' => $linkToken,
			'shipinkPkgWeight' => Settings::get('SHIPINK_PKG_WEIGHT') ?: '1',
			'shipinkPkgHeight' => Settings::get('SHIPINK_PKG_HEIGHT') ?: '10',
			'shipinkPkgWidth' => Settings::get('SHIPINK_PKG_WIDTH') ?: '10',
			'shipinkPkgLength' => Settings::get('SHIPINK_PKG_LENGTH') ?: '10',
			'shipinkWebhookUrl' => rtrim((string) $domain, '/') . '/api/module.php?m=shipink&action=webhook&token=' . rawurlencode($linkToken),
			'shipinkWarehouses' => $warehouses,
			'shipinkCarrierAccounts' => $carrierAccounts,
			'shipinkCarrierServices' => $carrierServices,
			'flash' => $flash,
			'flashType' => $flashType,
			'sonuc' => $sonuc,
			'isConfigured' => $this->isConfigured(),
		]);
	}

	public function getApiBaseUrl(): string
	{
		return Settings::get('SHIPINK_ENV') === 'dev'
			? 'https://api.dev.shipink.io'
			: 'https://api.shipink.io';
	}

	/**
	 * @return array{success:bool,message?:string,access_token?:string,refresh_token?:string,expires_in?:int}
	 */
	public function authenticate(bool $force = false): array
	{
		$expires = (int) Settings::get('SHIPINK_TOKEN_EXPIRES');
		$access = trim((string) Settings::get('SHIPINK_ACCESS_TOKEN'));

		if (!$force && $access !== '' && $expires > (time() + 60)) {
			return [
				'success' => true,
				'access_token' => $access,
				'refresh_token' => (string) Settings::get('SHIPINK_REFRESH_TOKEN'),
				'expires_in' => max(0, $expires - time()),
			];
		}

		$refresh = trim((string) Settings::get('SHIPINK_REFRESH_TOKEN'));

		if (!$force && $refresh !== '') {
			$result = $this->request('POST', '/token', ['refresh_token' => $refresh], false);

			if ($this->storeTokenFromResponse($result)) {
				return [
					'success' => true,
					'access_token' => (string) Settings::get('SHIPINK_ACCESS_TOKEN'),
					'refresh_token' => (string) Settings::get('SHIPINK_REFRESH_TOKEN'),
					'expires_in' => (int) ($result['response']['expires_in'] ?? 3600),
				];
			}
		}

		$username = trim((string) Settings::get('SHIPINK_USERNAME'));
		$password = (string) Settings::get('SHIPINK_PASSWORD');

		if ($username === '' || $password === '') {
			return ['success' => false, 'message' => 'Shipink kullanıcı adı / şifre tanımlı değil'];
		}

		$result = $this->request('POST', '/token', [
			'username' => $username,
			'password' => $password,
		], false);

		if ($this->storeTokenFromResponse($result)) {
			return [
				'success' => true,
				'access_token' => (string) Settings::get('SHIPINK_ACCESS_TOKEN'),
				'refresh_token' => (string) Settings::get('SHIPINK_REFRESH_TOKEN'),
				'expires_in' => (int) ($result['response']['expires_in'] ?? 3600),
			];
		}

		$message = $this->extractErrorMessage($result);

		return ['success' => false, 'message' => $message !== '' ? $message : 'Token alınamadı'];
	}

	/** @param array<string,mixed> $result */
	private function storeTokenFromResponse(array $result): bool
	{
		$http = (int) ($result['http_code'] ?? 0);
		$body = is_array($result['response'] ?? null) ? $result['response'] : [];
		$access = trim((string) ($body['access_token'] ?? ''));

		if ($http < 200 || $http >= 300 || $access === '') {
			return false;
		}

		$expiresIn = max(60, (int) ($body['expires_in'] ?? 3600));
		Settings::set('SHIPINK_ACCESS_TOKEN', $access);
		Settings::set('SHIPINK_REFRESH_TOKEN', trim((string) ($body['refresh_token'] ?? '')));
		Settings::set('SHIPINK_TOKEN_EXPIRES', (string) (time() + $expiresIn));

		return true;
	}

	/**
	 * @param array<string,mixed>|null $data
	 * @return array{http_code:int,response:?array,raw:string,success:bool,message?:string}
	 */
	public function request(string $method, string $path, ?array $data = null, bool $auth = true): array
	{
		$url = rtrim($this->getApiBaseUrl(), '/') . '/' . ltrim($path, '/');
		$headers = ['Content-Type: application/json', 'Accept: application/json'];

		if ($auth) {
			$tokenInfo = $this->authenticate(false);

			if (empty($tokenInfo['success']) || empty($tokenInfo['access_token'])) {
				return [
					'http_code' => 0,
					'response' => null,
					'raw' => '',
					'success' => false,
					'message' => (string) ($tokenInfo['message'] ?? 'Yetkilendirme başarısız'),
				];
			}

			$headers[] = 'Authorization: Bearer ' . $tokenInfo['access_token'];
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 45);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$method = strtoupper($method);

		if ($method === 'POST') {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data !== null ? json_encode($data, JSON_UNESCAPED_UNICODE) : '{}');
		} elseif ($method === 'PUT' || $method === 'PATCH' || $method === 'DELETE') {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
			if ($data !== null) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
			}
		}

		$raw = curl_exec($ch);
		$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if (curl_errno($ch)) {
			$error = curl_error($ch);
			curl_close($ch);

			return [
				'http_code' => 0,
				'response' => null,
				'raw' => '',
				'success' => false,
				'message' => 'cURL: ' . $error,
			];
		}

		curl_close($ch);
		$decoded = json_decode((string) $raw, true);

		return [
			'http_code' => $httpCode,
			'response' => is_array($decoded) ? $decoded : null,
			'raw' => (string) $raw,
			'success' => $httpCode >= 200 && $httpCode < 300,
		];
	}

	/** @return list<array<string,mixed>> */
	public function apiGetList(string $path): array
	{
		$result = $this->request('GET', $path);

		if (empty($result['success'])) {
			return [];
		}

		$body = is_array($result['response'] ?? null) ? $result['response'] : [];

		if (isset($body['data']) && is_array($body['data'])) {
			return array_values(array_filter($body['data'], 'is_array'));
		}

		return array_values(array_filter($body, 'is_array'));
	}

	/**
	 * Siparişi Shipink'e aktarır (order + shipment).
	 * @return string|null Hata metni veya başarıda null
	 */
	public function submitCargo(int $idOrder, bool $force = false): ?string
	{
		$idOrder = (int) $idOrder;

		if ($idOrder <= 0) {
			return 'Geçersiz sipariş';
		}

		if (!$this->isConfigured()) {
			return 'Shipink ayarları eksik (kullanıcı, depo, taşıyıcı hizmet/hesap)';
		}

		$existing = $this->getCargoRow($idOrder);

		if ($existing && !$force) {
			return null;
		}

		if ($existing && trim((string) ($existing['shipment_id'] ?? '')) !== '') {
			return null;
		}

		$orderPayload = $this->buildOrderPayload($idOrder);

		if (!is_array($orderPayload)) {
			return is_string($orderPayload) ? $orderPayload : 'Sipariş verisi oluşturulamadı';
		}

		$shipinkOrderId = trim((string) ($existing['shipink_order_id'] ?? ''));

		if ($shipinkOrderId === '') {
			$orderResult = $this->request('POST', '/orders', $orderPayload);

			if (empty($orderResult['success'])) {
				return 'Shipink sipariş hatası: ' . $this->extractErrorMessage($orderResult);
			}

			$orderData = $this->unwrapData($orderResult['response']);
			$shipinkOrderId = trim((string) ($orderData['id'] ?? ''));

			if ($shipinkOrderId === '') {
				return 'Shipink sipariş ID alınamadı';
			}
		}

		$shipmentPayload = $this->buildShipmentPayload($idOrder, $shipinkOrderId);

		if (!is_array($shipmentPayload)) {
			return is_string($shipmentPayload) ? $shipmentPayload : 'Gönderi verisi oluşturulamadı';
		}

		$shipResult = $this->request('POST', '/shipments', $shipmentPayload);

		if (empty($shipResult['success'])) {
			$this->upsertCargoRow($idOrder, [
				'shipink_order_id' => $shipinkOrderId,
				'shipment_id' => '',
				'tracking_number' => '',
				'tracking_url' => '',
				'carrier' => '',
				'label_url' => '',
				'raw_response' => (string) ($shipResult['raw'] ?? ''),
			]);

			return 'Shipink gönderi hatası: ' . $this->extractErrorMessage($shipResult);
		}

		$shipData = $this->unwrapData($shipResult['response']);
		$shipmentId = trim((string) ($shipData['id'] ?? ''));
		$carrier = is_array($shipData['carrier'] ?? null) ? $shipData['carrier'] : [];
		$trackingNumber = trim((string) ($carrier['shipment_id'] ?? $shipData['tracking']['code'] ?? ''));
		$trackingUrl = trim((string) ($carrier['tracking_url'] ?? ''));
		$carrierName = trim((string) ($carrier['carrier_id'] ?? $carrier['carrier_service_id'] ?? 'Shipink'));
		$labelUrl = '';

		if (!empty($shipData['labels'][0]['pdf'])) {
			$labelUrl = (string) $shipData['labels'][0]['pdf'];
		}

		$this->upsertCargoRow($idOrder, [
			'shipink_order_id' => $shipinkOrderId,
			'shipment_id' => $shipmentId,
			'tracking_number' => mb_substr($trackingNumber, 0, 128),
			'tracking_url' => mb_substr($trackingUrl, 0, 512),
			'carrier' => mb_substr($carrierName, 0, 128),
			'label_url' => mb_substr($labelUrl, 0, 512),
			'raw_response' => mb_substr((string) ($shipResult['raw'] ?? ''), 0, 60000),
		]);

		$update = [
			'cargo_company' => $carrierName !== '' ? $carrierName : 'Shipink',
		];

		if ($trackingNumber !== '') {
			$update['tracking_number'] = $trackingNumber;
		}

		DB::update('orders', $update, 'id_order = :id_order', ['id_order' => $idOrder]);

		return null;
	}

	/** @return array<string,mixed>|null */
	public function getCargoRow(int $idOrder): ?array
	{
		if ($idOrder <= 0) {
			return null;
		}

		$row = DB::execute('SELECT * FROM shipink WHERE id_order = ? LIMIT 1', [$idOrder]);

		return is_array($row[0] ?? null) ? $row[0] : null;
	}

	/**
	 * @param array<string,string> $fields
	 */
	private function upsertCargoRow(int $idOrder, array $fields): void
	{
		$existing = $this->getCargoRow($idOrder);
		$row = array_merge([
			'id_order' => $idOrder,
			'shipink_order_id' => '',
			'shipment_id' => '',
			'tracking_number' => '',
			'tracking_url' => '',
			'carrier' => '',
			'label_url' => '',
			'raw_response' => '',
		], $fields);

		if ($existing) {
			unset($row['id_order']);
			DB::update('shipink', $row, 'id_order = :id_order', ['id_order' => $idOrder]);
		} else {
			DB::insert('shipink', $row);
		}
	}

	/**
	 * @return array<string,mixed>|string
	 */
	public function buildOrderPayload(int $idOrder)
	{
		$idOrder = (int) $idOrder;

		if ($idOrder <= 0) {
			return 'Geçersiz sipariş';
		}

		$orderRows = DB::execute('SELECT * FROM orders WHERE id_order = ? LIMIT 1', [$idOrder]) ?: [];
		$order = $orderRows[0] ?? null;

		if (!$order) {
			return 'Sipariş bulunamadı';
		}

		$details = DB::execute(
			'SELECT od.*, p.stock_code, p.desi
			 FROM order_detail od
			 LEFT JOIN products p ON p.id_product = od.id_product
			 WHERE od.id_order = ?',
			[$idOrder]
		) ?: [];

		if ($details === []) {
			return 'Sipariş ürünleri bulunamadı';
		}

		$items = [];

		foreach ($details as $row) {
			$qty = max(1, (int) round((float) ($row['qty'] ?? 1)));
			$price = (float) ($row['price'] ?? 0);
			$sku = trim((string) ($row['stock_code'] ?? ''));

			$items[] = [
				'name' => (string) ($row['product_name'] ?? 'Ürün'),
				'quantity' => $qty,
				'category' => 'general',
				'price' => round($price, 2),
				'hs_code' => '',
				'origin' => 'TR',
				'sku' => $sku !== '' ? $sku : ('SKU' . (int) ($row['id_product'] ?? 0)),
			];
		}

		$phone = $this->normalizePhone((string) ($order['customer_phone'] ?? ''));
		$email = trim((string) ($order['customer_email'] ?? ''));
		$city = trim((string) ($order['address_district'] ?? ''));
		$state = trim((string) ($order['address_city'] ?? ''));
		$street = trim((string) ($order['address_text'] ?? ''));
		$zip = trim((string) Settings::get('POSTAL_CODE'));

		if ($zip === '') {
			$zip = '34000';
		}

		$paymentMethod = strtolower(trim((string) ($order['payment_method'] ?? '')));
		$shipinkPaymentMethod = $paymentMethod === 'cash_on_delivery' ? 'cash-on-delivery' : 'credit-card';
		$currency = strtoupper(trim((string) Settings::get('SHOP_CURRENCY')));

		if ($currency === '') {
			$currency = 'TRY';
		}

		$siteName = trim((string) Settings::get('SITE_NAME'));
		$reference = (string) ($order['reference'] ?? $idOrder);

		return [
			'sales_channel' => [
				'id' => 'fshop',
				'order_id' => (string) $idOrder,
				'order_number' => $reference,
				'name' => $siteName !== '' ? $siteName : 'FShop',
			],
			'customer' => [
				'name' => (string) ($order['customer_name'] ?? ''),
				'company' => (string) ($order['company_name'] ?? ''),
				'tax_id' => (string) ($order['tax_number'] ?? ''),
				'email' => [
					'main' => $email !== '' ? $email : 'noreply@example.com',
					'work' => '',
				],
				'phone' => [
					'main' => $phone,
					'work' => '',
					'cell' => '',
					'code' => '',
				],
				'address' => [
					'street' => $street,
					'city' => $city !== '' ? $city : $state,
					'state' => $state !== '' ? $state : $city,
					'zip' => $zip,
					'country_code' => 'TR',
				],
			],
			'items' => $items,
			'note' => (string) ($order['note'] ?? ''),
			'language' => 'tr',
			'currency' => $currency,
			'price' => round((float) ($order['total'] ?? 0), 2),
			'payment' => [
				'method' => $shipinkPaymentMethod,
				'status' => 'completed',
			],
			'placed_at' => date('c', strtotime((string) ($order['date_add'] ?? 'now')) ?: time()),
		];
	}

	/**
	 * @return array<string,mixed>|string
	 */
	public function buildShipmentPayload(int $idOrder, string $shipinkOrderId)
	{
		$shipinkOrderId = trim($shipinkOrderId);

		if ($shipinkOrderId === '') {
			return 'Shipink sipariş ID gerekli';
		}

		$orderRows = DB::execute('SELECT * FROM orders WHERE id_order = ? LIMIT 1', [$idOrder]) ?: [];
		$order = $orderRows[0] ?? null;

		if (!$order) {
			return 'Sipariş bulunamadı';
		}

		$weight = max(0.1, (float) Settings::get('SHIPINK_PKG_WEIGHT'));
		$height = max(0.1, (float) Settings::get('SHIPINK_PKG_HEIGHT'));
		$width = max(0.1, (float) Settings::get('SHIPINK_PKG_WIDTH'));
		$length = max(0.1, (float) Settings::get('SHIPINK_PKG_LENGTH'));

		$desiSum = (float) DB::getValue(
			'SELECT COALESCE(SUM(GREATEST(p.desi, 1) * od.qty), 0)
			 FROM order_detail od
			 LEFT JOIN products p ON p.id_product = od.id_product
			 WHERE od.id_order = ?',
			[$idOrder]
		);

		if ($desiSum > 0) {
			$weight = max($weight, round($desiSum, 2));
		}

		$payload = [
			'direction' => 'outgoing',
			'order_id' => $shipinkOrderId,
			'carrier_service_id' => trim((string) Settings::get('SHIPINK_CARRIER_SERVICE_ID')),
			'carrier_account_id' => trim((string) Settings::get('SHIPINK_CARRIER_ACCOUNT_ID')),
			'warehouse_id' => trim((string) Settings::get('SHIPINK_WAREHOUSE_ID')),
			'packages' => [[
				'dimension_unit' => 'cm',
				'height' => $height,
				'length' => $length,
				'width' => $width,
				'weight' => $weight,
				'weight_unit' => 'kg',
			]],
		];

		$cardId = trim((string) Settings::get('SHIPINK_CARD_ID'));

		if ($cardId !== '') {
			$payload['card_id'] = $cardId;
		}

		$paymentMethod = strtolower(trim((string) ($order['payment_method'] ?? '')));

		if ($paymentMethod === 'cash_on_delivery') {
			$payload['payment'] = [
				'type' => 'CODCash',
				'amount' => round((float) ($order['total'] ?? 0), 2),
				'currency' => strtoupper(trim((string) Settings::get('SHOP_CURRENCY')) ?: 'TRY'),
			];
		}

		return $payload;
	}

	/** @param mixed $response */
	private function unwrapData($response): array
	{
		if (!is_array($response)) {
			return [];
		}

		if (isset($response['data']) && is_array($response['data']) && isset($response['data']['id'])) {
			return $response['data'];
		}

		return $response;
	}

	/** @param array<string,mixed> $result */
	private function extractErrorMessage(array $result): string
	{
		if (!empty($result['message'])) {
			return (string) $result['message'];
		}

		$body = is_array($result['response'] ?? null) ? $result['response'] : [];
		$candidates = [
			$body['meta']['message'] ?? null,
			$body['message'] ?? null,
			$body['error'] ?? null,
			$body['errors'][0]['message'] ?? null,
		];

		foreach ($candidates as $msg) {
			if (is_string($msg) && trim($msg) !== '') {
				return trim($msg);
			}
		}

		$http = (int) ($result['http_code'] ?? 0);

		return $http > 0 ? ('HTTP ' . $http) : 'Bilinmeyen hata';
	}

	private function normalizePhone(string $phone): string
	{
		$digits = preg_replace('/\D+/', '', $phone) ?: '';

		if ($digits === '') {
			return '905000000000';
		}

		if (strpos($digits, '90') === 0 && strlen($digits) >= 12) {
			return $digits;
		}

		if (strpos($digits, '0') === 0 && strlen($digits) === 11) {
			return '90' . substr($digits, 1);
		}

		if (strlen($digits) === 10) {
			return '90' . $digits;
		}

		return $digits;
	}
}
