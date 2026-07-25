<?php

if (!defined('IN_SCRIPT') && !defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/ModuleBase.php';
require_once __DIR__ . '/lib/TrustBarService.php';

class TrustBarModule extends ModuleBase
{
	public string $name = 'trust-bar';
	public string $title = 'Güven Bandı';
	public string $version = '1.0.0';
	public string $description = 'Ana sayfa altında güven / avantaj kutuları (ücretsiz kargo, iade vb.)';
	public string $author = 'FShop';

	public array $displayHooks = [
		'home_bottom' => 'Ana sayfa alt bölüm — güven bandı',
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
		return $this->runSqlFile('install.sql');
	}

	public function uninstall(): bool
	{
		return $this->runSqlFile('uninstall.sql');
	}

	public function renderDisplayHook(string $hook, array $context = []): ?string
	{
		if ($hook !== 'home_bottom' || !TrustBarService::isEnabled()) {
			return null;
		}

		$items = TrustBarService::getActiveItems();

		if ($items === []) {
			return null;
		}

		$html = $this->renderFrontTemplate('home_bottom', [
			'items' => $items,
		]);

		return $html !== '' ? $html : null;
	}

	public function adminPage(): void
	{
		global $smarty, $adminToken;

		TrustBarService::ensureSchema();
		$flash = '';
		$flashType = 'success';

		if (Tools::isSubmit('saveTrustBar')) {
			$postToken = (string) Tools::getValue('token');

			if (!hash_equals($adminToken, $postToken)) {
				$flash = 'Geçersiz istek';
				$flashType = 'danger';
			} else {
				TrustBarService::saveSettings((bool) Tools::getValue('enabled'));
				$rawItems = Tools::getValue('items');

				if (is_array($rawItems)) {
					TrustBarService::saveItems($rawItems);
				}

				$flash = 'Güven bandı kaydedildi';
			}
		}

		if (Tools::isSubmit('addTrustBarItem')) {
			$postToken = (string) Tools::getValue('token');

			if (!hash_equals($adminToken, $postToken)) {
				$flash = 'Geçersiz istek';
				$flashType = 'danger';
			} elseif (TrustBarService::addItem(
				(string) Tools::getValue('new_title'),
				(string) Tools::getValue('new_subtitle'),
				(string) Tools::getValue('new_icon')
			)) {
				$flash = 'Yeni kutu eklendi';
			} else {
				$flash = 'Başlık zorunludur';
				$flashType = 'danger';
			}
		}

		$deleteId = (int) Tools::getValue('delete_item');

		if ($deleteId > 0 && Tools::getValue('token') !== '' && hash_equals($adminToken, (string) Tools::getValue('token'))) {
			TrustBarService::deleteItem($deleteId);
			$flash = 'Kutu silindi';
		}

		$smarty->assign([
			'flash' => $flash,
			'flashType' => $flashType,
			'settings' => TrustBarService::getSettings(),
			'items' => TrustBarService::getAllItems(),
			'icons' => TrustBarService::ICONS,
		]);
	}
}
