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
	public string $version = '1.0.0';
	public string $description = 'PWA (Progressive Web App) — manifest, service worker, mobil menüde uygulama indir';
	public string $author = 'FShop';

	public array $displayHooks = [
		'head.top' => 'PWA manifest, tema rengi ve service worker kaydı',
		'mobile_menu' => 'Mobil menüde uygulama indir bağlantısı',
	];

	public array $defaultDisplayHooks = ['head.top', 'mobile_menu'];

	public array $apiActions = [
		'manifest' => 'api/manifest.php',
		'service-worker' => 'api/service-worker.php',
		'icon' => 'api/icon.php',
	];

	public array $frontStylesheets = ['front.css'];
	public array $frontScripts = ['front.js'];
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

		return true;
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

		$this->registerAdminMenuLink('Mobile App', 'general', 46);
	}

	public function renderDisplayHook(string $hook, array $context = []): ?string
	{
		if (!MobilAppService::isEnabled()) {
			return null;
		}

		$settings = MobilAppService::getSettings();

		if ($hook === 'head.top') {
			$html = $this->renderFrontTemplate('head-top', [
				'settings' => $settings,
				'domain' => MobilAppService::getDomain(),
				'manifestUrl' => MobilAppService::getDomain() . 'manifest.php',
				'swUrl' => MobilAppService::getDomain() . 'sw.php',
				'scopePath' => MobilAppService::getScopePath(),
				'appleIcon' => $this->resolvePublicIcon($settings['icon_apple'] ?: $settings['icon_192'] ?: $settings['icon_512']),
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

		return null;
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken;

		$flash = '';
		$flashType = 'info';

		if (Tools::isSubmit('saveMobilApp')) {
			$postToken = (string) Tools::getValue('token');

			if (!hash_equals($adminToken, $postToken)) {
				$flash = 'Geçersiz istek';
				$flashType = 'danger';
			} else {
				$payload = [
					'enabled' => Tools::getValue('enabled'),
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

		$smarty->assign([
			'flash' => $flash,
			'flashType' => $flashType,
			'adminToken' => $adminToken,
			'settings' => $settings,
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
