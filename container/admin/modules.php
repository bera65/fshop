<?php

if (!defined('IN_ADMIN')) {
	exit;
}

$uploadedQuery = trim((string) Tools::getValue('uploaded'));

if ($uploadedQuery !== '' && (string) Tools::getValue('refreshed') !== '1') {
	header(
		'Location: ' . Admin::url('modules')
		. '?uploaded=' . rawurlencode($uploadedQuery)
		. '&refreshed=1'
	);
	exit;
}

$flash = '';
$flashType = 'info';

if (!empty($_SESSION['admin_module_flash']) && is_array($_SESSION['admin_module_flash'])) {
	$flash = (string) ($_SESSION['admin_module_flash']['message'] ?? '');
	$flashType = (string) ($_SESSION['admin_module_flash']['type'] ?? 'success');
	unset($_SESSION['admin_module_flash']);
}

if (Tools::isSubmit('uploadModuleZip')) {
	$postToken = (string) Tools::getValue('token');

	if (!hash_equals($adminToken, $postToken)) {
		$flash = adminT('Invalid request');
		$flashType = 'danger';
	} elseif (Admin::isDemoMode()) {
		$flash = adminT('Demo mode: module upload is not allowed');
		$flashType = 'warning';
	} else {
		$result = Module::installFromZip(isset($_FILES['module_zip']) && is_array($_FILES['module_zip']) ? $_FILES['module_zip'] : []);

		if (!empty($result['success'])) {
			$uploadedName = trim((string) ($result['name'] ?? ''));
			$_SESSION['admin_module_flash'] = [
				'message' => $result['message'],
				'type' => 'success',
			];
			$redirectUrl = Admin::url('modules');

			if ($uploadedName !== '') {
				$redirectUrl .= '?uploaded=' . rawurlencode($uploadedName);
			}

			header('Location: ' . $redirectUrl);
			exit;
		}

		$flash = (string) ($result['message'] ?? adminT('Invalid request'));
		$flashType = 'danger';
	}
}

if (Tools::isSubmit('moduleAction')) {
	$postToken = (string) Tools::getValue('token');

	if (!hash_equals($adminToken, $postToken)) {
		$flash = adminT('Invalid request');
		$flashType = 'danger';
	} else {
		$name = trim((string) Tools::getValue('name'));
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

			default:
				$result = [
					'success' => false,
					'message' => adminT('Invalid action')
				];
				break;
		}

		if ($result['success'] && $action === 'install') {
			$_SESSION['admin_module_flash'] = [
				'message' => $result['message'],
				'type' => 'success',
			];
			header('Location: ' . Admin::url('modules'));
			exit;
		}

		$flash = $result['message'];
		$flashType = !empty($result['success']) ? 'success' : 'danger';
	}
}

$smarty->assign([
	'modules' => Module::getAdminList(),
	'moduleStats' => Module::getAdminStats(),
	'frisayModulesUrl' => 'https://frisay.com/modules',
	'demoMode' => Admin::isDemoMode(),
	'flash' => $flash,
	'flashType' => $flashType,
]);

AdminPage::add('modules', 'Modules');
