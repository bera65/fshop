<?php

class MarketplaceAdmin
{
	public static function renderProductsPage(): void
	{
		global $smarty, $adminToken;

		$flash = Marketplace::handleAdminPosts($adminToken);
		$currentPage = max(1, (int) Tools::getValue('page'));
		$query = trim((string) Tools::getValue('q'));
		$filter = trim((string) Tools::getValue('filter', 'all'));

		if (!in_array($filter, ['all', 'linked', 'unlinked'], true)) {
			$filter = 'all';
		}

		$perPage = 20;
		$total = Trendyol\ProductSyncService::countMarketplaceCatalog($query, $filter);
		$pagination = Pagination::build(
			$total,
			$currentPage,
			$perPage,
			Admin::url('marketplace-products'),
			array_filter([
				'q' => $query !== '' ? $query : null,
				'filter' => $filter !== 'all' ? $filter : null,
			], static fn($v) => $v !== null && $v !== '')
		);

		$rows = Trendyol\ProductSyncService::getMarketplaceCatalog($query, $filter, $perPage, $pagination['offset']);
		$catalogProducts = [];

		foreach ($rows as $row) {
			$idProduct = (int) ($row['id_product'] ?? 0);
			$catalogProducts[] = [
				'row' => $row,
				'panel_html' => Marketplace::renderProductPanelHtml($idProduct),
			];
		}

		$urls = Marketplace::urls();

		$smarty->assign(array_merge($urls, [
			'flash' => $flash,
			'catalogProducts' => $catalogProducts,
			'searchQuery' => $query,
			'linkFilter' => $filter,
			'pagination' => $pagination,
			'marketplaceAdminAssets' => Marketplace::adminAssets(),
			'marketplacePage' => 'products',
			'marketplacePlatforms' => Marketplace::platformList(),
			'tyConfigured' => Marketplace::isTrendyolConfigured(),
			'categoryOptions' => Category::getProductSelectOptions(),
			'brandOptions' => Brand::getOptions(),
			'fiyattrendToken' => (string) Settings::get('TRENDYOL_FIYATTREND_TOKEN'),
		]));

		AdminPage::add('marketplace-products', 'Pazaryeri — Ürünler');
	}

	public static function renderOrdersPage(): void
	{
		global $smarty;

		$flash = Marketplace::handleAdminPosts($GLOBALS['adminToken'] ?? '');

		$smarty->assign(array_merge(Marketplace::urls(), [
			'flash' => $flash,
			'tyConfigured' => Marketplace::isTrendyolConfigured(),
			'tyOrders' => Trendyol\OrderService::getRecent(50),
			'marketplaceAdminAssets' => ['css' => [], 'js' => []],
			'marketplacePage' => 'orders',
		]));

		AdminPage::add('marketplace-orders', 'Pazaryeri — Siparişler');
	}

	public static function renderQuestionsPage(): void
	{
		global $smarty;

		$flash = Marketplace::handleAdminPosts($GLOBALS['adminToken'] ?? '');

		$smarty->assign(array_merge(Marketplace::urls(), [
			'flash' => $flash,
			'tyConfigured' => Marketplace::isTrendyolConfigured(),
			'tyQuestions' => Trendyol\QuestionService::getRecent(50),
			'marketplaceAdminAssets' => ['css' => [], 'js' => []],
			'marketplacePage' => 'questions',
		]));

		AdminPage::add('marketplace-questions', 'Pazaryeri — Soru-Cevap');
	}

	public static function renderSettingsPage(string $defaultPlatform = 'trendyol'): void
	{
		global $smarty;

		$flash = Marketplace::handleAdminPosts($GLOBALS['adminToken'] ?? '');
		$tab = (string) Tools::getValue('tab', 'settings');
		$platform = trim((string) Tools::getValue('platform', $defaultPlatform));

		if (!isset(Marketplace::PLATFORMS[$platform])) {
			$platform = 'trendyol';
		}

		if (!in_array($tab, ['settings', 'fiyattrend'], true)) {
			$tab = 'settings';
		}

		$assign = array_merge(Marketplace::urls(), [
			'flash' => $flash,
			'tab' => $tab,
			'marketplacePlatforms' => Marketplace::platformList(),
			'marketplacePlatform' => $platform,
			'marketplacePage' => 'settings',
			'settingsUrl' => Marketplace::settingsUrl($platform),
			'marketplaceAdminAssets' => ['css' => [], 'js' => []],
		]);

		if ($platform === 'trendyol' && Marketplace::isPlatformActive('trendyol')) {
			$assign = array_merge($assign, Trendyol\TrendyolAdminPages::settingsVars(), [
				'tyConfigured' => Marketplace::isTrendyolConfigured(),
				'tab' => $tab,
				'settingsUrl' => Marketplace::settingsUrl('trendyol'),
			]);
		}

		$smarty->assign($assign);

		AdminPage::add('marketplace-settings', 'Pazaryeri — Ayarlar');
	}
}
