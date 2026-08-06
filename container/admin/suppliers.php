<?php
	if (!defined('IN_ADMIN')) {
		exit;
	}

	Supplier::ensureSchema();

	$sonuc = '';

	if (Tools::isSubmit('deleteSupplier')) {
		$postToken = (string) Tools::getValue('token');

		if (hash_equals($adminToken, $postToken)
			|| hash_equals($adminToken, (string) Tools::getValue('deleteSupplier'))
		) {
			$result = Supplier::delete((int) Tools::getValue('idSupplier'));
			$sonuc = (string) ($result['message'] ?? '');
		}
	}

	$activeRaw = Tools::getValue('active', -1);
	$activeFilter = ($activeRaw === '' || $activeRaw === false || $activeRaw === null)
		? -1
		: (int) $activeRaw;

	$smarty->assign([
		'suppliers' => Supplier::getAdminList($activeFilter),
		'activeFilter' => $activeFilter,
		'sonuc' => $sonuc,
	]);

	AdminPage::add('suppliers', 'Suppliers');
