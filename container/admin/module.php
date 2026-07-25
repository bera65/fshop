<?php

if (!defined('IN_ADMIN')) {
	exit;
}

$name = trim((string) Tools::getValue('name'));
$detail = $name !== '' ? Module::getDetail($name) : null;
$flash = '';
$flashType = 'info';

if (!$detail) {
	http_response_code(404);
	AdminPage::add('404', 'Module not found');
	return;
}

// Eski / yanlış link: module?name=slider&group=promo → yapılandırma sayfasına yönlendir
$adminTpl = Module::path($name) . '/assets/templates/admin/admin.tpl';

if (Tools::getValue('group') !== '' && is_file($adminTpl)) {
	$target = $detail['configure_url']
		. '?group=' . rawurlencode((string) Tools::getValue('group'));

	if ((int) Tools::getValue('edit') > 0) {
		$target .= '&edit=' . (int) Tools::getValue('edit');
	}

	header('Location: ' . $target);
	exit;
}

if (Tools::isSubmit('moduleAction')) {
	$postToken = (string) Tools::getValue('token');

	if (!hash_equals($adminToken, $postToken)) {
		$flash = adminT('Invalid request');
		$flashType = 'danger';
	} else {
		$action = trim((string) Tools::getValue('action'));

		switch ($action) {
			case 'install':
				$result = Module::install($name);
				break;

			case 'uninstall':
				$result = Module::uninstall($name);
				break;

			case 'enable':
				$result = Module::setActive($name, true);
				break;

			case 'disable':
				$result = Module::setActive($name, false);
				break;

			case 'save_hooks':
				$result = Module::setDisplayHooks(
					$name,
					(array) Tools::getValue('displayHooks', [])
				);
				break;

			default:
				$result = [
					'success' => false,
					'message' => adminT('Invalid action')
				];
				break;
		}

		$flash = $result['message'];
		$flashType = !empty($result['success']) ? 'success' : 'danger';
		$detail = Module::getDetail($name);
	}
}

$smarty->assign([
	'mod' => $detail,
	'hookCatalog' => Module::getHookCatalog(),
	'displayHookCatalog' => Module::getDisplayHookCatalog(),
	'flash' => $flash,
	'flashType' => $flashType,
]);

AdminPage::add('module', $detail['title']);
