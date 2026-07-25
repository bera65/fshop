<?php

if (!defined('IN_ADMIN')) {
	exit;
}

$flash = '';
$flashType = 'success';

Tax::ensureSchema();

if (Tools::isSubmit('taxAction')) {
	$postToken = (string) Tools::getValue('token');

	if (!hash_equals($adminToken, $postToken)) {
		$flash = adminT('Invalid request');
		$flashType = 'danger';
	} else {
		$action = trim((string) Tools::getValue('action'));

		switch ($action) {
			case 'add':
				$result = Tax::add(
					(string) Tools::getValue('name'),
					(float) str_replace(',', '.', (string) Tools::getValue('rate')),
					!empty(Tools::getValue('active'))
				);
				break;

			case 'update':
				$result = Tax::update(
					(int) Tools::getValue('id_tax'),
					(string) Tools::getValue('name'),
					(float) str_replace(',', '.', (string) Tools::getValue('rate')),
					!empty(Tools::getValue('active'))
				);
				break;

			case 'delete':
				$result = Tax::delete((int) Tools::getValue('id_tax'));
				break;

			case 'default':
				$result = Tax::setDefault((int) Tools::getValue('id_tax'));
				break;

			default:
				$result = ['success' => false, 'message' => adminT('Invalid action')];
				break;
		}

		$flash = $result['message'];
		$flashType = !empty($result['success']) ? 'success' : 'danger';
	}
}

$smarty->assign([
	'taxes' => Tax::getAdminList(),
	'defaultTaxRate' => Tax::getDefaultRate(),
	'flash' => $flash,
	'flashType' => $flashType,
]);

AdminPage::add('taxes', 'Taxes');
