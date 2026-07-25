<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';
require_once __DIR__ . '/lib/ProductTabsService.php';

class ProductTabsModule extends ModuleBase
{
	public string $name = 'product-tabs';
	public string $title = 'Ürün Sekmeleri';
	public string $version = '1.0.0';
	public string $description = 'Ürün sayfasına özel HTML sekmeleri ekler (tüm veya seçili ürünler)';
	public string $author = 'FShop';

	public array $displayHooks = [
		'product_tab' => 'Ürün sekmesi butonu',
		'product_tab_content' => 'Ürün sekmesi içeriği',
	];

	public array $defaultDisplayHooks = ['product_tab', 'product_tab_content'];

	public array $frontStylesheets = ['front.css'];
	public array $adminStylesheets = ['admin.css'];
	public array $adminScripts = ['admin.js'];

	public function install(): bool
	{
		return $this->runSqlFile('install.sql');
	}

	public function uninstall(): bool
	{
		return $this->runSqlFile('uninstall.sql');
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken;

		$flash = '';
		$flashType = 'success';

		if (Tools::isSubmit('saveProductTab')) {
			$postToken = (string) Tools::getValue('token');

			if (!hash_equals($adminToken, $postToken)) {
				$flash = 'Geçersiz istek';
				$flashType = 'danger';
			} else {
				$result = ProductTabsService::save([
					'id_tab' => (int) Tools::getValue('id_tab'),
					'title' => (string) Tools::getValue('title'),
					'content' => (string) Tools::getValue('content'),
					'scope' => (string) Tools::getValue('scope'),
					'position' => (int) Tools::getValue('position'),
					'active' => Tools::getValue('active'),
					'product_ids' => Tools::getValue('product_ids'),
				]);
				$flash = $result['message'];
				$flashType = !empty($result['success']) ? 'success' : 'danger';

				if (!empty($result['success']) && empty(Tools::getValue('id_tab'))) {
					header('Location: ' . Admin::url($this->getAdminSlug()) . '?edit=' . (int) $result['id_tab'] . '&saved=1');
					exit;
				}
			}
		}

		if (Tools::isSubmit('deleteProductTab')) {
			$postToken = (string) Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				$result = ProductTabsService::delete((int) Tools::getValue('id_tab'));
				$flash = $result['message'];
				$flashType = !empty($result['success']) ? 'success' : 'danger';
			}
		}

		if (Tools::isSubmit('toggleProductTab')) {
			$postToken = (string) Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				$result = ProductTabsService::toggleActive((int) Tools::getValue('id_tab'));
				$flash = $result['message'];
				$flashType = !empty($result['success']) ? 'success' : 'danger';
			}
		}

		if (Tools::getValue('saved')) {
			$flash = 'Sekme kaydedildi';
			$flashType = 'success';
		}

		$editId = (int) Tools::getValue('edit');
		$editTab = $editId > 0 ? ProductTabsService::getById($editId) : null;

		$smarty->assign([
			'tabs' => ProductTabsService::getAdminList(),
			'editTab' => $editTab,
			'productOptions' => ProductTabsService::getProductOptions(),
			'flash' => $flash,
			'flashType' => $flashType,
			'adminUseEditor' => true,
		]);
	}

	public function renderDisplayHook(string $hook, array $context = []): ?string
	{
		if (!in_array($hook, ['product_tab', 'product_tab_content'], true)) {
			return null;
		}

		$idProduct = (int) ($context['id_product'] ?? 0);

		if ($idProduct <= 0) {
			return null;
		}

		$tabs = ProductTabsService::getTabsForProduct($idProduct);

		if ($tabs === []) {
			return null;
		}

		$html = $this->renderFrontTemplate($hook, [
			'tabs' => $tabs,
			'id_product' => $idProduct,
		]);

		return $html !== '' ? $html : null;
	}
}
