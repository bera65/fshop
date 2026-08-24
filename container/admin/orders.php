<?php
	if (!defined('IN_ADMIN')) {
		exit;
	}

	if (Tools::isSubmit('logout')) {
		Admin::logout();
		header('Location: ' . Admin::url('login'));
		exit;
	}

	$flash = '';
	$flashType = 'success';

	if (Tools::isSubmit('saveOrderGoal')) {
		$postToken = (string) Tools::getValue('token');

		if (!hash_equals((string) $adminToken, $postToken)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} elseif (Order::saveOrderGoalTarget((int) Tools::getValue('order_goal_target'))) {
			header('Location: ' . Admin::url('orders') . '?' . http_build_query(array_filter([
				'goal_saved' => 1,
				'reference' => Tools::getValue('reference'),
				'status' => Tools::getValue('status'),
				'customer' => Tools::getValue('customer'),
				'date_from' => Tools::getValue('date_from'),
				'date_to' => Tools::getValue('date_to'),
			], static function ($v) {
				return $v !== null && $v !== '';
			})));
			exit;
		} else {
			$flash = adminT('Some settings could not be saved');
			$flashType = 'danger';
		}
	}

	if ((int) Tools::getValue('goal_saved') === 1) {
		$flash = adminT('Order goal saved');
		$flashType = 'success';
	}

	if ((int) Tools::getValue('deleted') === 1) {
		$flash = adminT('Order deleted');
		$flashType = 'success';
	}

	if (Tools::isSubmit('deleteOrder')) {
		$postToken = (string) Tools::getValue('token');
		$deleteId = (int) Tools::getValue('id');

		if (!hash_equals((string) $adminToken, $postToken)) {
			$flash = adminT('Invalid request');
			$flashType = 'danger';
		} else {
			$result = Order::deleteByAdmin($deleteId);

			if (!empty($result['success'])) {
				header('Location: ' . Admin::url('orders?deleted=1'));
				exit;
			}

			$flash = $result['message'];
			$flashType = 'danger';
		}
	}

	$currentPage = max(1, (int) Tools::getValue('page'));
	$status = (int) Tools::getValue('status');
	$filters = Order::normalizeAdminFilters([
		'reference' => Tools::getValue('reference'),
		'customer' => Tools::getValue('customer'),
		'date_from' => Tools::getValue('date_from'),
		'date_to' => Tools::getValue('date_to'),
		'payment_method' => Tools::getValue('payment_method'),
		'sku' => Tools::getValue('sku'),
		'product_name' => Tools::getValue('product_name'),
		'tracking_number' => Tools::getValue('tracking_number'),
		'cargo_company' => Tools::getValue('cargo_company'),
		'channel' => Tools::getValue('channel'),
		'sort' => Tools::getValue('sort'),
	]);
	$perPage = 30;
	$filterQuery = Order::buildAdminFilterQuery($status, $filters);
	$total = Order::countAdmin($status, $filters['date_from'], $filters['date_to'], $filters);
	$pagination = Pagination::build($total, $currentPage, $perPage, Admin::url('orders'), $filterQuery);
	$orders = Order::enrichAdminRows(
		Order::getAdminList($status, $perPage, $pagination['offset'], $filters['date_from'], $filters['date_to'], $filters),
		true
	);

	$cargoOptions = [];

	if (class_exists('Cargo', false)) {
		foreach (Cargo::getList(false) as $cargo) {
			$name = trim((string) ($cargo['name'] ?? ''));

			if ($name !== '') {
				$cargoOptions[$name] = $name;
			}
		}
	}

	$smarty->assign([
		'orders' => $orders,
		'ordersTotal' => $total,
		'pagination' => $pagination,
		'statusFilter' => $status,
		'orderFilters' => $filters,
		'statusOptions' => Order::getStatusOptions(),
		'paymentFilterOptions' => Order::getAdminPaymentFilterOptions(),
		'cargoFilterOptions' => $cargoOptions,
		'orderGoal' => Order::getOrderGoalStats(),
		'adminUseOrderStatus' => true,
		'orderStatusApiUrl' => Admin::url('order-status'),
		'flash' => $flash,
		'flashType' => $flashType,
	]);

	AdminPage::add('orders', 'Orders');
