<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';
require_once __DIR__ . '/lib/InstagramGalleryService.php';

class InstagramGalleryModule extends ModuleBase
{
	public string $name = 'instagram-gallery';
	public string $title = 'Instagram Galeri';
	public string $version = '1.0.0';
	public string $description = 'Ana sayfa altında Instagram görsel galerisi';
	public string $author = 'FShop';

	public array $displayHooks = [
		'home_bottom' => 'Ana sayfa alt bölüm — Instagram galerisi',
	];

	public array $defaultDisplayHooks = ['home_bottom'];

	public array $frontStylesheets = ['front.css'];
	public array $adminStylesheets = ['admin.css'];

	public function boot(): void
	{
		if (!Module::isInstalled($this->name) || !Module::isEnabled($this->name)) {
			return;
		}

		$assigned = Module::getAssignedDisplayHooks($this->name);
		$needed = $this->getDefaultDisplayHookNames();
		$missing = array_diff($needed, $assigned);

		if ($missing !== []) {
			Module::setDisplayHooks($this->name, array_values(array_unique(array_merge($assigned, $needed))));
		}
	}

	public function install(): bool
	{
		if (!$this->runSqlFile('install.sql')) {
			return false;
		}

		$dir = InstagramGalleryService::getImageDir();

		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}

		return true;
	}

	public function uninstall(): bool
	{
		return $this->runSqlFile('uninstall.sql');
	}

	public function renderDisplayHook(string $hook, array $context = []): ?string
	{
		if ($hook !== 'home_bottom' || !InstagramGalleryService::isEnabled()) {
			return null;
		}

		$items = InstagramGalleryService::getActiveItems();

		if ($items === []) {
			return null;
		}

		$settings = InstagramGalleryService::getSettings();
		$html = $this->renderFrontTemplate('home_bottom', [
			'settings' => $settings,
			'items' => $items,
		]);

		return $html !== '' ? $html : null;
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken;

		InstagramGalleryService::ensureSchema();
		$flash = '';
		$flashType = 'success';

		if (Tools::isSubmit('saveInstagramGallery')) {
			$postToken = (string) Tools::getValue('token');

			if (!hash_equals($adminToken, $postToken)) {
				$flash = 'Geçersiz istek';
				$flashType = 'danger';
			} elseif (InstagramGalleryService::saveSettings([
				'enabled' => Tools::getValue('enabled'),
				'title' => Tools::getValue('title'),
				'subtitle' => Tools::getValue('subtitle'),
				'profile_url' => Tools::getValue('profile_url'),
				'profile_label' => Tools::getValue('profile_label'),
			])) {
				$flash = 'Galeri ayarları kaydedildi';
			} else {
				$flash = 'Ayarlar kaydedilemedi';
				$flashType = 'danger';
			}
		}

		if (Tools::isSubmit('addInstagramItem')) {
			$postToken = (string) Tools::getValue('token');

			if (!hash_equals($adminToken, $postToken)) {
				$flash = 'Geçersiz istek';
				$flashType = 'danger';
			} else {
				$result = InstagramGalleryService::addItem([
					'instagram_post_url' => Tools::getValue('instagram_post_url'),
					'image_url' => Tools::getValue('image_url'),
					'link_url' => Tools::getValue('link_url'),
					'caption' => Tools::getValue('caption'),
					'active' => 1,
				], isset($_FILES['image']) ? $_FILES['image'] : null);

				$flash = $result['message'];
				$flashType = !empty($result['success']) ? 'success' : 'danger';
			}
		}

		$deleteId = (int) Tools::getValue('delete_item');

		if ($deleteId > 0 && Tools::getValue('token') !== '' && hash_equals($adminToken, (string) Tools::getValue('token'))) {
			InstagramGalleryService::deleteItem($deleteId);
			$flash = 'Görsel silindi';
		}

		$toggleId = (int) Tools::getValue('toggle_item');

		if ($toggleId > 0 && Tools::getValue('token') !== '' && hash_equals($adminToken, (string) Tools::getValue('token'))) {
			InstagramGalleryService::toggleItem($toggleId);
			$flash = 'Görsel durumu güncellendi';
		}

		$smarty->assign([
			'flash' => $flash,
			'flashType' => $flashType,
			'settings' => InstagramGalleryService::getSettings(),
			'items' => InstagramGalleryService::getAllItems(),
		]);
	}
}
