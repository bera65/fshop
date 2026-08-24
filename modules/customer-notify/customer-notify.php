<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';
require_once __DIR__ . '/lib/CustomerNotifyService.php';
require_once __DIR__ . '/lib/CustomerNotifyPush.php';

class CustomerNotifyModule extends ModuleBase
{
	public string $name = 'customer-notify';
	public string $title = 'Müşteri Bildirimleri';
	public string $version = '1.2.0';
	public string $description = 'Müşteri bildirimi + OneSignal tarayıcı push (kargo / yayın)';
	public string $author = 'FShop';

	public array $routes = [
		'customer-notification' => 'front/view.php',
	];

	public array $displayHooks = [
		'footer' => 'Tarayıcı bildirimi izin banner’ı ve OneSignal',
	];

	public array $defaultDisplayHooks = [
		'footer',
	];

	public array $adminStylesheets = ['admin.css'];
	public array $adminScripts = ['admin.js'];

	public array $apiActions = [
		'search-customers' => 'api/search-customers.php',
		'subscribe' => 'api/subscribe.php',
		'poll' => 'api/poll.php',
		'sw' => 'api/sw.php',
	];

	public function install(): bool
	{
		if (!$this->runSqlFile('install.sql')) {
			return false;
		}

		CustomerNotifyPush::ensureSchema();
		Settings::set(CustomerNotifyPush::SETTING_ENABLED, '1');
		Settings::set(CustomerNotifyPush::SETTING_MODE, CustomerNotifyPush::MODE_SHIPPED);

		return true;
	}

	public function uninstall(): bool
	{
		return $this->runSqlFile('uninstall.sql');
	}

	public function boot(): void
	{
		CustomerNotifyService::ensureSchema();
		CustomerNotifyPush::ensureSchema();
		$this->ensureDisplayHooks();
		$this->registerAdminMenuLink('Müşteri Bildirimleri', 'sales', 48);
	}

	private function ensureDisplayHooks(): void
	{
		if (!Module::isEnabled($this->name)) {
			return;
		}

		$assigned = Module::getAssignedDisplayHooks($this->name);

		if (!in_array('footer', $assigned, true)) {
			$assigned[] = 'footer';
			Module::setDisplayHooks($this->name, $assigned);
		}
	}

	public function renderDisplayHook(string $hook, array $context = []): ?string
	{
		if ($hook !== 'footer') {
			return null;
		}

		if (!CustomerNotifyPush::isEnabled() || !Customer::isLoggedIn()) {
			return null;
		}

		global $domain;

		$domainBase = rtrim((string) $domain, '/');
		$path = (string) (parse_url($domainBase . '/', PHP_URL_PATH) ?: '/');
		$scope = rtrim($path, '/') . '/';

		if ($scope === '//') {
			$scope = '/';
		}

		$oneSignalReady = CustomerNotifyPush::isOneSignalClientReady();

		$config = [
			'enabled' => true,
			'loggedIn' => true,
			'userId' => Customer::getId(),
			'pollUrl' => $domainBase . '/api/module.php?m=customer-notify&action=poll',
			'subscribeUrl' => $domainBase . '/api/module.php?m=customer-notify&action=subscribe',
			'swUrl' => $domainBase . '/api/module.php?m=customer-notify&action=sw',
			'scope' => $scope,
			'homeUrl' => $domainBase . '/',
			'iconUrl' => $this->resolveBrowserNotifyIconUrl($domainBase),
			'pollMs' => 25000,
			'oneSignal' => $oneSignalReady ? [
				'appId' => CustomerNotifyPush::getOneSignalAppId(),
				'safariWebId' => CustomerNotifyPush::getOneSignalSafariWebId(),
				'serviceWorkerPath' => CustomerNotifyPush::oneSignalWorkerPath(),
				'serviceWorkerScope' => CustomerNotifyPush::oneSignalWorkerScope(),
				'serverPush' => CustomerNotifyPush::isOneSignalConfigured(),
			] : null,
		];

		$html = $this->renderFrontTemplate('footer', [
			'cnPushEnabled' => true,
			'cnPushLoggedIn' => true,
			'cnPushOneSignal' => $oneSignalReady,
			'cnPushConfigJson' => Security::jsonForHtmlScript($config),
			'cnPushJsUrl' => $this->getAssetUrl('js/front-push.js') . '?v=6',
			'cnPushText' => function_exists('translate')
				? translate('Would you like to receive your order status updates as notifications?')
				: 'Would you like to receive your order status updates as notifications?',
			'cnPushEnableLabel' => function_exists('translate') ? translate('Yes') : 'Yes',
			'cnPushDismissLabel' => function_exists('translate') ? translate('No') : 'No',
		]);

		return $html !== '' ? $html : null;
	}

	private function resolveBrowserNotifyIconUrl(string $domainBase): string
	{
		$relative = 'img/favicon.ico';
		$absolute = dirname($this->getPath(), 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
		$version = is_file($absolute) ? (string) filemtime($absolute) : (string) time();

		return rtrim($domainBase, '/') . '/' . $relative . '?v=' . rawurlencode($version);
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken, $domain;

		$flash = '';
		$flashType = 'success';
		$tab = (string) Tools::getValue('tab', 'send');

		if (Tools::isSubmit('saveCustomerNotifyPush') && hash_equals($adminToken, (string) Tools::getValue('token'))) {
			$mode = (string) Tools::getValue('browser_mode', CustomerNotifyPush::MODE_SHIPPED);

			if (!in_array($mode, [
				CustomerNotifyPush::MODE_SHIPPED,
				CustomerNotifyPush::MODE_ALL_STATUS,
				CustomerNotifyPush::MODE_BROADCAST,
			], true)) {
				$mode = CustomerNotifyPush::MODE_SHIPPED;
			}

			Settings::set(CustomerNotifyPush::SETTING_ENABLED, Tools::getValue('browser_enabled') === '1' ? '1' : '0');
			Settings::set(CustomerNotifyPush::SETTING_MODE, $mode);
			Settings::set(CustomerNotifyPush::SETTING_WEBHOOK, trim((string) Tools::getValue('browser_webhook')));
			Settings::set(CustomerNotifyPush::SETTING_OS_APP_ID, trim((string) Tools::getValue('onesignal_app_id')));
			Settings::set(CustomerNotifyPush::SETTING_OS_SAFARI, trim((string) Tools::getValue('onesignal_safari_web_id')));

			$restKey = trim((string) Tools::getValue('onesignal_rest_api_key'));

			if ($restKey !== '') {
				Settings::set(CustomerNotifyPush::SETTING_OS_REST_KEY, $restKey);
			}

			$flash = 'Tarayıcı bildirim ayarları kaydedildi';
			$flashType = 'success';
			$tab = 'browser';
		}

		if (Tools::isSubmit('testCustomerNotifyOneSignal') && hash_equals($adminToken, (string) Tools::getValue('token'))) {
			$tab = 'browser';
			$testUserId = (int) Tools::getValue('test_user_id');

			if (!CustomerNotifyPush::isOneSignalConfigured()) {
				$flash = 'Önce App ID ve REST API Key kaydedin';
				$flashType = 'danger';
			} elseif ($testUserId <= 0) {
				$flash = 'Geçerli bir müşteri ID girin';
				$flashType = 'danger';
			} else {
				$result = CustomerNotifyPush::sendOneSignal(
					$testUserId,
					'OneSignal test',
					'FShop test bildirimi — ' . date('H:i:s'),
					rtrim((string) $domain, '/') . '/my-account#notifications'
				);

				if (!empty($result['ok'])) {
					$flash = 'OneSignal gönderildi (id=' . ($result['id'] ?? '') . ', recipients=' . (string) ($result['recipients'] ?? '?') . ')';
					$flashType = 'success';
				} else {
					$flash = 'OneSignal başarısız: ' . ($result['error'] ?? 'bilinmeyen hata');
					$flashType = 'danger';
				}
			}
		}

		if (Tools::isSubmit('sendCustomerNotify') && hash_equals($adminToken, (string) Tools::getValue('token'))) {
			$result = CustomerNotifyService::sendBroadcast([
				'title' => Tools::getValue('title'),
				'message' => Tools::getValue('message'),
				'link' => Tools::getValue('link'),
				'scope' => Tools::getValue('scope', 'all'),
				'send_email' => Tools::getValue('send_email'),
				'user_ids' => Tools::getValue('user_ids'),
			]);
			$flash = $result['message'];
			$flashType = !empty($result['success']) ? 'success' : 'danger';

			if (!empty($result['success'])) {
				$tab = 'history';
			}
		}

		$domainBase = rtrim((string) $domain, '/');
		$restKey = CustomerNotifyPush::getOneSignalRestKey();

		$smarty->assign([
			'flash' => $flash,
			'flashType' => $flashType,
			'tab' => $tab,
			'customerCount' => CustomerNotifyService::countActiveCustomers(),
			'broadcasts' => CustomerNotifyService::getRecentBroadcasts(40),
			'searchCustomersUrl' => $domainBase . '/api/module.php?m=customer-notify&action=search-customers',
			'cnBrowserEnabled' => CustomerNotifyPush::isEnabled(),
			'cnBrowserMode' => CustomerNotifyPush::getMode(),
			'cnBrowserWebhook' => (string) Settings::get(CustomerNotifyPush::SETTING_WEBHOOK),
			'cnBrowserDevices' => CustomerNotifyPush::countDevices(),
			'cnOneSignalAppId' => CustomerNotifyPush::getOneSignalAppId(),
			'cnOneSignalSafari' => CustomerNotifyPush::getOneSignalSafariWebId(),
			'cnOneSignalRestConfigured' => $restKey !== '',
			'cnOneSignalReady' => CustomerNotifyPush::isOneSignalConfigured(),
			'cnOneSignalWorkerUrl' => $domainBase . '/' . ltrim(CustomerNotifyPush::oneSignalWorkerPath(), '/'),
			'cnOneSignalLastError' => CustomerNotifyPush::getLastOneSignalError(),
			'cnOneSignalLastOk' => CustomerNotifyPush::getLastOneSignalOk(),
		]);
	}
}
