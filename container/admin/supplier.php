<?php
	if (!defined('IN_ADMIN')) {
		exit;
	}

	Supplier::ensureSchema();

	$id = (int) Tools::getValue('id');
	$supplier = $id > 0 ? Supplier::getByIdAdmin($id) : null;
	$flash = '';
	$flashType = 'success';
	$isNew = $id <= 0;

	if (!$isNew && !$supplier) {
		http_response_code(404);
		AdminPage::add('404', 'Supplier not found');
		return;
	}

	if (Tools::isSubmit('saveSupplier')) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} else {
			$result = Supplier::save($_POST, $id);
			$flash = $result['message'];
			$flashType = !empty($result['success']) ? 'success' : 'danger';

			if ($result['success']) {
				header('Location: ' . Admin::url('supplier?id=' . (int) $result['id'] . '&saved=1'));
				exit;
			}
		}
	}

	if (Tools::getValue('saved')) {
		$flash = 'Tedarikçi kaydedildi';
	}

	$form = $supplier ?: [
		'supplier_name' => '',
		'active' => 1,
	];

	$smarty->assign([
		'supplier' => $form,
		'idSupplier' => $id,
		'isNew' => $isNew,
		'flash' => $flash,
		'flashType' => $flashType,
	]);

	AdminPage::add('supplier', $isNew ? 'New supplier' : 'Edit supplier');
