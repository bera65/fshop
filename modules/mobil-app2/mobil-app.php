<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';
require_once __DIR__ . '/lib/MobilAppService.php';

class MobilAppModule extends ModuleBase
{
	public string $name = 'mobil-app';
	public string $title = 'Mobil App';
	public string $version = '1.1.0';
	public string $description = 'PWA (Progressive Web App) — manifest, service worker, mobil menüde uygulama indir';
	public string $author = 'FShop';

	public array $displayHooks = [
		'head.top' => 'PWA manifest, tema rengi ve service worker kaydı',
		'mobile_menu' => 'Mobil menüde uygulama indir bağlantısı',
		'footer' => 'Push bildirim izni popup',
	];

	public array $defaultDisplayHooks = ['head.top', 'mobile_menu', 'footer'];

	public array $apiActions = [
		'manifest' => 'api/manifest.php',
		'service-worker' => 'api/service-worker.php',
		'icon' => 'api/icon.php',
		'push-subscribe' => 'api/push-subscribe.php',
		'push-unsubscribe' => 'api/push-unsubscribe.php',
		'push-vapid-public' => 'api/push-vapid-public.php',
	];

	public array $frontStylesheets = ['front.css'];
	public array $frontScripts = ['front.js', 'push.js'];
	public array $adminStylesheets = ['admin.css'];

	public function install(): bool
	{
		if (!$this->runSqlFile('install.sql')) {
			return false;
		}

		MobilAppService::ensureSchema();

		$siteName = trim((string) Settings::get('SITE_NAME')) ?: 'FShop';
		MobilAppService::saveSettings([
			'enabled' => 1,
			'app_name' => $siteName,
			'short_name' => mb_substr($siteName, 0, 12, 'UTF-8'),
			'description' => trim((string) Settings::get('SITE_DESC')) ?: 'Online alışveriş',
			'theme_color' => '#194e70',
			'background_color' => '#ffffff',
			'orientation' => 'portrait-primary',
			'menu_enabled' => 1,
			'menu_label' => 'Uygulamayı yükle',
			'menu_hint_ios' => 'Safari\'de Paylaş > Ana Ekrana Ekle',
			'offline_title' => 'İnternet bağlantısı yok',
			'offline_message' => 'Bağlantınızı kontrol edip tekrar deneyin.',
		]);

		MobilAppService::ensureDefaultIcons();
		self::ensurePushKeysOnInstall();

		return true;
	}

	private function ensurePushKeysOnInstall(): void
	{
		require_once __DIR__ . '/lib/WebPushNative.php';

		if (!function_exists('openssl_pkey_new')) {
			return;
		}

		$keys = WebPushNative::createVapidKeys();

		if (($keys['publicKey'] ?? '') === '' || ($keys['privateKey'] ?? '') === '') {
			return;
		}

		MobilAppService::saveVapidKeys($keys['publicKey'], $keys['privateKey'], true);
		MobilAppService::ensurePushFooterHook();
	}

	public function uninstall(): bool
	{
		return $this->runSqlFile('uninstall.sql');
	}

	public function boot(): void
	{
		if (!Module::isInstalled($this->name)) {
			return;
		}

		MobilAppService::ensureSchema();
		MobilAppService::ensureDefaultIcons();
		MobilAppService::ensurePushKeys();
		MobilAppService::ensurePushFooterHook();

		$this->registerAdminMenuLink('Mobile App', 'general', 46);
	}

	public function renderDisplayHook(string $hook, array $context = []): ?string
	{
		if (!MobilAppService::isEnabled()) {
			return null;
		}

		$settings = MobilAppService::getSettings();

		if ($hook === 'head.top') {
			$pushKeys = MobilAppService::getVapidKeys();
			$apiBase = rtrim(MobilAppService::getDomain(), '/');
			$csrfToken = (string) ($_SESSION['csrf_token'] ?? '');

			$html = $this->renderFrontTemplate('head-top', [
				'settings' => $settings,
				'domain' => MobilAppService::getDomain(),
				'manifestUrl' => MobilAppService::publicPath('manifest.php'),
				'swUrl' => MobilAppService::publicPath('sw.php'),
				'scopePath' => MobilAppService::getScopePath(),
				'appleIcon' => $this->resolvePublicIcon($settings['icon_apple'] ?: $settings['icon_192'] ?: $settings['icon_512']),
				'pushEnabled' => MobilAppService::isPushEnabled() && $pushKeys['public'] !== '',
				'pushPublicKey' => $pushKeys['public'],
				'pushSubscribeUrl' => $apiBase . '/api/module.php?m=mobil-app&action=push-subscribe',
				'isLoggedIn' => class_exists('Customer', false) && Customer::isLoggedIn(),
				'csrfToken' => $csrfToken,
				'siteName' => trim((string) Settings::get('SITE_NAME')) ?: ($settings['app_name'] ?: 'Mağazamız'),
			]);

			return $html !== '' ? $html : null;
		}

		if ($hook === 'mobile_menu') {
			if ((int) ($settings['menu_enabled'] ?? 0) !== 1) {
				return null;
			}

			$html = $this->renderFrontTemplate('mobile_menu', [
				'settings' => $settings,
			]);

			return $html !== '' ? $html : null;
		}

		if ($hook === 'footer') {
			$pushKeys = MobilAppService::getVapidKeys();

			if (!MobilAppService::isPushEnabled() || $pushKeys['public'] === '') {
				return null;
			}

			if (!class_exists('Customer', false) || !Customer::isLoggedIn()) {
				return null;
			}

			$siteName = trim((string) Settings::get('SITE_NAME')) ?: ($settings['app_name'] ?: 'Mağazamız');
			$html = $this->renderFrontTemplate('push-prompt', [
				'siteName' => $siteName,
				'themeColor' => (string) ($settings['theme_color'] ?? '#194e70'),
			]);

			return $html !== '' ? $html : null;
		}

		return null;
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken;

		$flash = '';
		$flashType = 'info';

		if (Tools::isSubmit('clearMobilAppPushSubs') && hash_equals($adminToken, (string) Tools::getValue('token'))) {
			MobilAppService::clearPushSubscriptions();
			$flash = 'Tüm push abonelikleri silindi. Müşteriler mağazaya girince popup ile tekrar izin vermeli.';
			$flashType = 'success';
		}

		if (Tools::isSubmit('testMobilAppPush') && hash_equals($adminToken, (string) Tools::getValue('token'))) {
			require_once __DIR__ . '/lib/WebPushService.php';
			$result = WebPushService::sendTestBroadcast();
			$flash = $result['message'];
			$flashType = !empty($result['success']) ? 'success' : 'danger';

			if (!empty($result['details'])) {
				$flash .= ' — ' . implode('; ', $result['details']);
			}
		}

		if (Tools::isSubmit('generateMobilAppVapid') && hash_equals($adminToken, (string) Tools::getValue('token'))) {
			require_once __DIR__ . '/lib/WebPushService.php';

			if (!function_exists('openssl_pkey_new')) {
				$flash = 'Sunucuda OpenSSL EC desteği yok; push kullanılamaz.';
				$flashType = 'danger';
			} else {
				$keys = WebPushService::generateVapidKeys();

				if ($keys['public'] === '' || $keys['private'] === '') {
					$flash = 'VAPID anahtarları oluşturulamadı';
					$flashType = 'danger';
				} elseif (MobilAppService::saveVapidKeys($keys['public'], $keys['private'], true)) {
					$flash = 'VAPID anahtarları oluşturuldu. Eski cihaz abonelikleri sıfırlandı — müşterilerin tekrar izin vermesi gerekir.';
					$flashType = 'success';
				} else {
					$flash = 'VAPID anahtarları kaydedilemedi';
					$flashType = 'danger';
				}
			}
		}

		if (Tools::isSubmit('saveMobilApp')) {
			$postToken = (string) Tools::getValue('token');

			if (!hash_equals($adminToken, $postToken)) {
				$flash = 'Geçersiz istek';
				$flashType = 'danger';
			} else {
				$payload = [
					'enabled' => Tools::getValue('enabled'),
					'push_enabled' => Tools::getValue('push_enabled'),
					'app_name' => Tools::getValue('app_name'),
					'short_name' => Tools::getValue('short_name'),
					'description' => Tools::getValue('description'),
					'theme_color' => Tools::getValue('theme_color'),
					'background_color' => Tools::getValue('background_color'),
					'orientation' => Tools::getValue('orientation'),
					'menu_enabled' => Tools::getValue('menu_enabled'),
					'menu_label' => Tools::getValue('menu_label'),
					'menu_hint_ios' => Tools::getValue('menu_hint_ios'),
					'offline_title' => Tools::getValue('offline_title'),
					'offline_message' => Tools::getValue('offline_message'),
					'vapid_public' => Tools::getValue('vapid_public'),
					'vapid_private' => Tools::getValue('vapid_private'),
					'vapid_subject' => Tools::getValue('vapid_subject'),
				];

				foreach (['icon_master', 'icon_192', 'icon_512', 'icon_apple'] as $field) {
					if (!empty($_FILES[$field]['tmp_name'])) {
						$stored = MobilAppService::handleIconUpload($field, $_FILES[$field]);

						if ($stored !== null) {
							if ($field === 'icon_master') {
								$payload['icon_512'] = 'assets/img/icon-512.png';
								$payload['icon_192'] = 'assets/img/icon-192.png';
								$payload['icon_apple'] = 'assets/img/apple-touch-icon.png';
							} else {
								$key = $field === 'icon_apple' ? 'icon_apple' : $field;
								$payload[$key] = $stored;
							}
						}
					}
				}

				if (MobilAppService::saveSettings($payload)) {
					$flash = 'Mobil uygulama ayarları kaydedildi';
					$flashType = 'success';
				} else {
					$flash = 'Ayarlar kaydedilemedi';
					$flashType = 'danger';
				}
			}
		}

		$settings = MobilAppService::getSettings();
		require_once __DIR__ . '/lib/PushSubscriptionService.php';
		require_once __DIR__ . '/lib/WebPushService.php';

		$smarty->assign([
			'flash' => $flash,
			'flashType' => $flashType,
			'adminToken' => $adminToken,
			'settings' => $settings,
			'pushOpenSslReady' => function_exists('openssl_pkey_new') && function_exists('curl_init'),
			'pushLibraryReady' => WebPushService::isLibraryReady(),
			'pushAvailable' => WebPushService::isAvailable(),
			'pushSubscriptionCount' => PushSubscriptionService::countAll(),
			'iconUrls' => [
				'192' => $this->resolvePublicIcon($settings['icon_192']),
				'512' => $this->resolvePublicIcon($settings['icon_512']),
				'apple' => $this->resolvePublicIcon($settings['icon_apple']),
			],
			'manifestPreviewUrl' => MobilAppService::getDomain() . 'manifest.php',
			'swPreviewUrl' => MobilAppService::getDomain() . 'sw.php',
			'scopePath' => MobilAppService::getScopePath(),
			'orientations' => [
				'portrait-primary' => 'Dikey (varsayılan)',
				'any' => 'Serbest',
				'landscape' => 'Yatay',
				'portrait' => 'Dikey',
			],
		]);
	}

	private function resolvePublicIcon(string $relative): string
	{
		$relative = ltrim(str_replace('\\', '/', $relative), '/');

		if ($relative === '') {
			return '';
		}

		if (strpos($relative, 'http://') === 0 || strpos($relative, 'https://') === 0) {
			return $relative;
		}

		if (strpos($relative, 'assets/') === 0) {
			return $this->getAssetUrl(substr($relative, 7));
		}

		return $this->getAssetUrl($relative);
	}
}
