<?php

if (!defined('IN_ADMIN')) {
	exit;
}

require_once dirname(__DIR__, 2) . '/core/CustomerGroup.php';

$flash = '';
$flashType = 'success';

CustomerGroup::ensureSchema();

if (Tools::isSubmit('groupAction')) {
	$postToken = (string) Tools::getValue('token');

	if (!hash_equals($adminToken, $postToken)) {
		$flash = adminT('Invalid request');
		$flashType = 'danger';
	} else {
		$action = trim((string) Tools::getValue('action'));

		switch ($action) {
			case 'add':
				$result = CustomerGroup::add(
					(string) Tools::getValue('name'),
					(float) str_replace(',', '.', (string) Tools::getValue('discount_percent')),
					!empty(Tools::getValue('active'))
				);
				break;

			case 'update':
				$result = CustomerGroup::update(
					(int) Tools::getValue('id_group'),
					(string) Tools::getValue('name'),
					(float) str_replace(',', '.', (string) Tools::getValue('discount_percent')),
					!empty(Tools::getValue('active'))
				);
				break;

			case 'delete':
				$result = CustomerGroup::delete((int) Tools::getValue('id_group'));
				break;

			case 'default':
				$result = CustomerGroup::setDefault((int) Tools::getValue('id_group'));
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
	'groups' => CustomerGroup::getAdminList(),
	'flash' => $flash,
	'flashType' => $flashType,
]);

AdminPage::add('customer-groups', adminT('Customer groups'));
