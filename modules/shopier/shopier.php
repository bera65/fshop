<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';
require_once __DIR__ . '/lib/ShopierApi.php';
require_once __DIR__ . '/lib/ProductSyncService.php';

use Shopier\ProductSyncService;

class ShopierModule extends ModuleBase
{
	public string $name = 'shopier';
	public string $title = 'Shopier Ürün Senkronizasyonu';
	public string $version = '1.0.0';
	public string $description = 'FShop ürünlerini Shopier API ile oluşturma, güncelleme ve silme';
	public string $author = 'FShop';

	public array $displayHooks = [
		'admin_product_button' => 'Ürün düzenleme ekranında Shopier gönder / güncelle / sil',
	];

	public array $defaultDisplayHooks = ['admin_product_button'];

	public array $adminStylesheets = ['admin.css'];
	public array $adminScripts = ['admin.js'];

	public array $apiActions = [
		'sync' => 'api/sync.php',
		'delete' => 'api/delete.php',
		'categories' => 'api/categories.php',
	];

	public function install(): bool
	{
		ProductSyncService::ensureSchema();

		return true;
	}

	public function uninstall(): bool
	{
		\DB::execute('DROP TABLE IF EXISTS shopier_category_map');
		\DB::execute('DROP TABLE IF EXISTS shopier_products');

		return true;
	}

	public function boot(): void
	{
		ProductSyncService::ensureSchema();

		if (Settings::get('SHOPIER_AUTO_SYNC') !== '1') {
			return;
		}

		Module::registerHook('product.updated', static function ($idProduct, $product, $isNew): void {
			$idProduct = (int) $idProduct;

			if ($idProduct <= 0 || !ProductSyncService::isConfigured()) {
				return;
			}

			$mapping = ProductSyncService::findMapping($idProduct);

			if (!$isNew && (!$mapping || trim((string) ($mapping['shopier_id'] ?? '')) === '')) {
				return;
			}

			ProductSyncService::sync($idProduct);
		});
	}

	public function renderAdminDisplayHook(string $hook, array $context = []): ?string
	{
		global $adminToken;

		if ($hook !== 'admin_product_button') {
			return null;
		}

		$idProduct = (int) ($context['id_product'] ?? Tools::getValue('id'));
		$isNew = !empty($context['is_new']);

		if ($isNew || $idProduct <= 0) {
			return null;
		}

		$mapping = ProductSyncService::findMapping($idProduct);
		$domain = rtrim((string) Settings::get('DOMAIN'), '/');

		$html = $this->renderAdminTemplate('admin_product_button', [
			'id_product' => $idProduct,
			'mapping' => $mapping,
			'configured' => ProductSyncService::isConfigured(),
			'syncUrl' => $domain . '/api/module.php?m=shopier&action=sync',
			'deleteUrl' => $domain . '/api/module.php?m=shopier&action=delete',
			'settingsUrl' => Admin::url('module-shopier'),
			'adminToken' => (string) $adminToken,
		]);

		return $html !== '' ? $html : null;
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken;

		$flash = '';
		$tab = (string) Tools::getValue('tab', 'settings');

		if (Tools::isSubmit('saveShopier')) {
			$postToken = (string) Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				Settings::set('SHOPIER_API_TOKEN', trim((string) Tools::getValue('api_token')));
				Settings::set('SHOPIER_DEFAULT_CATEGORY_ID', trim((string) Tools::getValue('default_category_id')));
				Settings::set('SHOPIER_PRODUCT_TYPE', (string) Tools::getValue('product_type', 'physical'));
				Settings::set('SHOPIER_SHIPPING_PAYER', (string) Tools::getValue('shipping_payer', 'buyerPays'));
				Settings::set('SHOPIER_SHIPPING_PRICE', trim((string) Tools::getValue('shipping_price')));
				Settings::set('SHOPIER_DISPATCH_DURATION', (string) max(1, min(3, (int) Tools::getValue('dispatch_duration', 1))));
				Settings::set('SHOPIER_PLACEMENT_SCORE', (string) max(0, (int) Tools::getValue('placement_score', 0)));
				Settings::set('SHOPIER_AUTO_SYNC', Tools::getValue('auto_sync') ? '1' : '0');
				$flash = 'Shopier ayarları kaydedildi';
			} else {
				$flash = 'Geçersiz istek';
			}
		}

		if (Tools::isSubmit('saveShopierCategories')) {
			$postToken = (string) Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				$maps = Tools::getValue('shopier_category');

				if (is_array($maps)) {
					foreach ($maps as $idCategory => $shopierCategoryId) {
						ProductSyncService::saveCategoryMap((int) $idCategory, (string) $shopierCategoryId);
					}
				}

				$flash = 'Kategori eşlemeleri kaydedildi';
				$tab = 'categories';
			} else {
				$flash = 'Geçersiz istek';
			}
		}

		$categoryOptions = Category::getMenuList();
		$categoryMaps = [];

		foreach (ProductSyncService::getCategoryMaps() as $row) {
			$categoryMaps[(int) $row['id_category']] = (string) $row['shopier_category_id'];
		}

		$smarty->assign([
			'flash' => $flash,
			'tab' => $tab,
			'shopierApiToken' => Settings::get('SHOPIER_API_TOKEN'),
			'shopierDefaultCategoryId' => Settings::get('SHOPIER_DEFAULT_CATEGORY_ID'),
			'shopierProductType' => Settings::get('SHOPIER_PRODUCT_TYPE') ?: 'physical',
			'shopierShippingPayer' => Settings::get('SHOPIER_SHIPPING_PAYER') ?: 'buyerPays',
			'shopierShippingPrice' => Settings::get('SHOPIER_SHIPPING_PRICE'),
			'shopierDispatchDuration' => (int) (Settings::get('SHOPIER_DISPATCH_DURATION') ?: 1),
			'shopierPlacementScore' => (int) (Settings::get('SHOPIER_PLACEMENT_SCORE') ?: 0),
			'shopierAutoSync' => Settings::get('SHOPIER_AUTO_SYNC') === '1',
			'categoryOptions' => $categoryOptions,
			'categoryMaps' => $categoryMaps,
			'recentSyncs' => ProductSyncService::getRecentSyncs(40),
			'categoriesApiUrl' => rtrim((string) Settings::get('DOMAIN'), '/') . '/api/module.php?m=shopier&action=categories',
			'adminToken' => $adminToken,
		]);
	}
}
