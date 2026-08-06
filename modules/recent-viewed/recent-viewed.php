<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';
require_once __DIR__ . '/lib/RecentViewedService.php';

class RecentViewedModule extends ModuleBase
{
	public string $name = 'recent-viewed';
	public string $title = 'Son Bakılan Ürünler';
	public string $version = '1.0.0';
	public string $description = 'Ziyaretçinin son baktığı ürünleri ana sayfada kompakt listede gösterir';
	public string $author = 'FShop';

	public array $displayHooks = [
		'home_bottom' => 'Ana sayfa — son bakılan ürünler bloğu',
		'product_detail' => 'Ürün detay — görüntüleme kaydı (görünmez)',
	];

	public array $defaultDisplayHooks = [
		'home_bottom',
		'product_detail',
	];

	public array $frontStylesheets = ['front.css'];
	public array $adminStylesheets = ['admin.css'];

	public function install(): bool
	{
		if (Settings::get('RECENT_VIEWED_TITLE') === '') {
			Settings::set('RECENT_VIEWED_TITLE', 'Son baktığınız ürünler');
		}

		if (Settings::get('RECENT_VIEWED_ENABLED') === '') {
			Settings::set('RECENT_VIEWED_ENABLED', '1');
		}

		if (Settings::get('RECENT_VIEWED_LIMIT') === '') {
			Settings::set('RECENT_VIEWED_LIMIT', '8');
		}

		if (Settings::get('RECENT_VIEWED_STORE') === '') {
			Settings::set('RECENT_VIEWED_STORE', '24');
		}

		return true;
	}

	public function uninstall(): bool
	{
		Settings::set('RECENT_VIEWED_ENABLED', '0');

		return true;
	}

	public function renderDisplayHook(string $hook, array $context = []): ?string
	{
		if ($hook === 'product_detail') {
			$idProduct = (int) ($context['id_product'] ?? 0);
			RecentViewedService::track($idProduct);

			return null;
		}

		if ($hook !== 'home_bottom' || !RecentViewedService::isEnabled()) {
			return null;
		}

		$products = RecentViewedService::getProductsForHome();

		if ($products === []) {
			return null;
		}

		$html = $this->renderFrontTemplate('home_bottom', [
			'title' => RecentViewedService::getTitle(),
			'products' => $products,
		]);

		return $html !== '' ? $html : null;
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken;

		$flash = '';
		$flashType = 'success';

		if (Tools::isSubmit('saveRecentViewed')) {
			$postToken = (string) Tools::getValue('token');

			if (!hash_equals($adminToken, $postToken)) {
				$flash = 'Geçersiz istek';
				$flashType = 'danger';
			} else {
				RecentViewedService::saveSettings([
					'enabled' => Tools::getValue('enabled'),
					'title' => Tools::getValue('title'),
					'limit' => Tools::getValue('limit'),
					'store' => Tools::getValue('store'),
				]);
				$flash = 'Ayarlar kaydedildi';
			}
		}

		$smarty->assign([
			'flash' => $flash,
			'flashType' => $flashType,
			'settings' => RecentViewedService::getSettings(),
		]);
	}
}
