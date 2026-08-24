<?php
	if (!defined('IN_ADMIN')) {
		exit;
	}

	$idOrder = (int) Tools::getValue('id');
	$order = Order::getByIdAdmin($idOrder);
	$flash = '';
	$flashType = 'success';
	$orderEditDiff = null;

	if (!$order) {
		http_response_code(404);
		AdminPage::add('404', 'Order not found');
		return;
	}

	if (!empty($_SESSION['order_edit_diff']) && is_array($_SESSION['order_edit_diff'])) {
		$orderEditDiff = $_SESSION['order_edit_diff'];
		unset($_SESSION['order_edit_diff']);
	}

	if (Tools::isSubmit('updateStatus')) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} else {
			$result = Order::updateFromApi($idOrder, [
				'status' => (int) Tools::getValue('status'),
				'cargo_company' => (string) Tools::getValue('cargo_company'),
				'tracking_number' => (string) Tools::getValue('tracking_number'),
			]);
			$flash = $result['message'];
			$flashType = !empty($result['success']) ? 'success' : 'danger';

			if ($result['success']) {
				$order = Order::getByIdAdmin($idOrder);
			}
		}
	}

	if (Tools::isSubmit('saveOrderEdit')) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} else {
			$items = Tools::getValue('items');
			$result = Order::updateByAdmin($idOrder, [
				'customer_name' => (string) Tools::getValue('customer_name'),
				'customer_phone' => (string) Tools::getValue('customer_phone'),
				'customer_email' => (string) Tools::getValue('customer_email'),
				'company_name' => (string) Tools::getValue('company_name'),
				'tax_office' => (string) Tools::getValue('tax_office'),
				'tax_number' => (string) Tools::getValue('tax_number'),
				'address_city' => (string) Tools::getValue('address_city'),
				'address_district' => (string) Tools::getValue('address_district'),
				'address_text' => (string) Tools::getValue('address_text'),
				'note' => (string) Tools::getValue('note'),
				'shipping' => (string) Tools::getValue('shipping'),
				'manual_discount_type' => (string) Tools::getValue('manual_discount_type'),
				'manual_discount_value' => (string) Tools::getValue('manual_discount_value'),
				'items' => is_array($items) ? $items : [],
			]);
			$flash = $result['message'];
			$flashType = !empty($result['success']) ? 'success' : 'danger';

			if (!empty($result['success'])) {
				$_SESSION['order_edit_diff'] = [
					'old_total' => (float) ($result['old_total'] ?? 0),
					'new_total' => (float) ($result['new_total'] ?? 0),
					'difference' => (float) ($result['difference'] ?? 0),
					'old_total_formatted' => Tools::displayPrice((float) ($result['old_total'] ?? 0)),
					'new_total_formatted' => Tools::displayPrice((float) ($result['new_total'] ?? 0)),
					'difference_formatted' => Tools::displayPrice(abs((float) ($result['difference'] ?? 0))),
				];
				header('Location: ' . Admin::url('order?id=' . $idOrder . '&edited=1'));
				exit;
			}
		}
	}

	if (Tools::isSubmit('saveInvoice')) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} else {
			$file = isset($_FILES['invoice_file']) && is_array($_FILES['invoice_file'])
				? $_FILES['invoice_file']
				: null;
			$result = Order::setInvoiceFromAdmin(
				$idOrder,
				$file,
				(string) Tools::getValue('invoice_url'),
				(string) Tools::getValue('invoice_name')
			);
			$flash = $result['message'];
			$flashType = !empty($result['success']) ? 'success' : 'danger';

			if (!empty($result['success'])) {
				$order = Order::getByIdAdmin($idOrder);
			}
		}
	}

	if (Tools::isSubmit('deleteInvoice')) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} else {
			$result = Order::clearInvoice($idOrder);
			$flash = $result['message'];
			$flashType = !empty($result['success']) ? 'success' : 'danger';

			if (!empty($result['success'])) {
				$order = Order::getByIdAdmin($idOrder);
			}
		}
	}

	if (Tools::isSubmit('deleteOrder')) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals($adminToken, $postToken)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} else {
			$result = Order::deleteByAdmin($idOrder);

			if (!empty($result['success'])) {
				header('Location: ' . Admin::url('orders?deleted=1'));
				exit;
			}

			$flash = $result['message'];
			$flashType = 'danger';
		}
	}

	$order = Order::getByIdAdmin($idOrder) ?: $order;

	$trackingUrl = '';
	$cargoCompany = trim((string) ($order['cargo_company'] ?? ''));
	$trackingNumber = trim((string) ($order['tracking_number'] ?? ''));

	if ($trackingNumber !== '' && class_exists('Cargo')) {
		$trackingUrl = Cargo::buildTrackingUrl($trackingNumber, $cargoCompany);
	}

	$canEditOrder = !in_array((int) $order['status'], [Order::STATUS_CANCELLED, Order::STATUS_RETURNED], true);

	$smarty->assign([
		'order' => $order,
		'flash' => $flash,
		'flashType' => $flashType,
		'orderEditDiff' => $orderEditDiff,
		'canEditOrder' => $canEditOrder,
		'statusOptions' => Order::getStatusOptions(),
		'cargoOptions' => class_exists('Cargo') ? Cargo::getList(true) : [],
		'trackingUrl' => $trackingUrl,
		'orderProductSearchUrl' => rtrim($domain, '/') . '/api/admin-order-products.php',
		'adminHooks' => [
			'admin_order_detail' => Module::renderDisplayHook('admin_order_detail', [
				'id_order' => $idOrder,
				'order' => $order,
			]),
		],
	]);

	AdminPage::add('order', adminT('Order #') . $order['reference']);
