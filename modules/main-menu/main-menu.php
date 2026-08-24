<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';
require_once __DIR__ . '/lib/MenuService.php';

class MainMenuModule extends ModuleBase
{
	public string $name = 'main-menu';
	public string $title = 'Ana Menü';
	public string $version = '1.3.0';
	public string $description = 'Header, mobil ve footer menü konumları; mega menü desteği';
	public string $author = 'FShop';

	public array $displayHooks = [
		'main_menu' => 'Üst (header) navigasyon',
		'footer_menu' => 'Footer menü bağlantıları',
	];

	public array $defaultDisplayHooks = ['main_menu', 'footer_menu'];

	public array $frontStylesheets = ['main-menu.css'];
	public array $frontScripts = ['main-menu.js'];
	public array $adminStylesheets = ['admin.css'];

	public function install(): bool
	{
		MainMenuService::ensureSchema();

		return true;
	}

	public function uninstall(): bool
	{
		DB::execute('DROP TABLE IF EXISTS `main_menu_items`');

		return true;
	}

	public function boot(): void
	{
		MainMenuService::ensureSchema();
		$this->ensureFooterMenuHook();

		Module::registerHook('smarty.assign', static function (): void {
			global $smarty;

			if (!$smarty) {
				return;
			}

			$headerItems = MainMenuService::getActiveItems('header');
			$mobileItems = MainMenuService::getActiveItems('mobile');
			$footerItems = MainMenuService::getActiveItems('footer');

			$smarty->assign([
				'mainMenuItems' => $mobileItems,
				'mainMenuActive' => $mobileItems !== [],
				'headerMenuItems' => $headerItems,
				'footerMenuItems' => $footerItems,
				'footerMenuActive' => $footerItems !== [],
			]);
		});
	}

	public function renderDisplayHook(string $hook, array $context = []): ?string
	{
		if ($hook === 'main_menu') {
			$items = MainMenuService::getActiveItems('header');
			if ($items === []) {
				return null;
			}
			$html = $this->renderFrontTemplate('main_menu', ['items' => $items]);

			return $html !== '' ? $html : null;
		}

		if ($hook === 'footer_menu') {
			$items = MainMenuService::getActiveItems('footer');
			if ($items === []) {
				return null;
			}
			$html = $this->renderFrontTemplate('footer_menu', ['items' => $items]);

			return $html !== '' ? $html : null;
		}

		return null;
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken;

		MainMenuService::ensureSchema();
		$flash = '';
		$flashType = 'success';
		$edit = null;

		if (Tools::isSubmit('saveMenuItem')) {
			$postToken = (string) Tools::getValue('token');

			if (!hash_equals($adminToken, $postToken)) {
				$flash = 'Geçersiz istek';
				$flashType = 'danger';
			} else {
				$id = (int) Tools::getValue('id_menu_item');
				$result = MainMenuService::saveItem([
					'label' => (string) Tools::getValue('label'),
					'link_type' => (string) Tools::getValue('link_type'),
					'link_value' => (string) Tools::getValue('link_value'),
					'target' => (string) Tools::getValue('target'),
					'position' => (int) Tools::getValue('position'),
					'active' => (int) Tools::getValue('active') === 1,
					'show_header' => (int) Tools::getValue('show_header') === 1,
					'show_mobile' => (int) Tools::getValue('show_mobile') === 1,
					'show_footer' => (int) Tools::getValue('show_footer') === 1,
				], $id);
				$flash = $result['message'];
				$flashType = !empty($result['success']) ? 'success' : 'danger';
			}
		}

		if (Tools::isSubmit('deleteMenuItem')) {
			$postToken = (string) Tools::getValue('token');

			if (hash_equals($adminToken, $postToken)) {
				$result = MainMenuService::deleteItem((int) Tools::getValue('id_menu_item'));
				$flash = $result['message'];
				$flashType = !empty($result['success']) ? 'success' : 'danger';
			}
		}

		$editId = (int) Tools::getValue('edit');

		if ($editId > 0) {
			foreach (MainMenuService::getAllAdmin() as $row) {
				if ((int) $row['id_menu_item'] === $editId) {
					$edit = $row;
					break;
				}
			}
		}

		$cmsOptions = DB::execute('SELECT id_cms, slug FROM cms_pages WHERE active = 1 ORDER BY id_cms ASC') ?: [];

		$smarty->assign([
			'flash' => $flash,
			'flashType' => $flashType,
			'menuItems' => MainMenuService::getAllAdmin(),
			'editItem' => $edit,
			'categoryOptions' => Category::getMenuList(),
			'cmsOptions' => $cmsOptions,
		]);
	}

	/** Ensure upgraded installs also bind footer_menu without wiping custom hook picks. */
	private function ensureFooterMenuHook(): void
	{
		if (!Module::isInstalled($this->name) || !Module::isEnabled($this->name)) {
			return;
		}

		$assigned = Module::getAssignedDisplayHooks($this->name);

		if (in_array('footer_menu', $assigned, true)) {
			return;
		}

		$merged = array_values(array_unique(array_merge($assigned, ['footer_menu'])));
		Module::setDisplayHooks($this->name, $merged);
	}
}
