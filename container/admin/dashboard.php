<?php
	if (!defined('IN_ADMIN')) {
		exit;
	}

	$stats = Admin::getDashboardStats();
	$stats['revenue_month_formatted'] = Tools::displayPrice($stats['revenue_month']);
	$charts = Admin::getDashboardCharts();
	$recentOrders = Order::getDashboardRecentOrders(50);
	$recentMarketplaceOrders = MarketplaceAdmin::getRecentMarketplaceOrders(20);
	$lowStockProducts = Admin::getLowStockProducts(6);

	$revenueTrend = 0.0;
	if ($stats['revenue_yesterday'] > 0) {
		$revenueTrend = round(
			(($stats['revenue_today'] - $stats['revenue_yesterday']) / $stats['revenue_yesterday']) * 100,
			1
		);
	}

	$ordersTrend = 0.0;
	if ($stats['orders_yesterday'] > 0) {
		$ordersTrend = round(
			(($stats['orders_today'] - $stats['orders_yesterday']) / $stats['orders_yesterday']) * 100,
			1
		);
	}

	$opsMax = max(
		1,
		(int) $stats['orders_pending'],
		(int) $stats['orders_processing'],
		(int) $stats['orders_cargo'],
		(int) $stats['products_low_stock']
	);

	$opsRings = [
		'pending' => (int) round(((int) $stats['orders_pending'] / $opsMax) * 100),
		'processing' => (int) round(((int) $stats['orders_processing'] / $opsMax) * 100),
		'shipped' => (int) round(((int) $stats['orders_cargo'] / $opsMax) * 100),
		'stock' => (int) round(((int) $stats['products_low_stock'] / $opsMax) * 100),
	];

	$activity = [];
	foreach (array_slice($recentOrders, 0, 4) as $orderRow) {
		$activity[] = [
			'type' => 'order',
			'title' => 'New order',
			'meta' => ($orderRow['reference'] ?? '') . ' · ' . ($orderRow['customer_name'] ?? ''),
			'time' => $orderRow['date_full'] ?? '',
		];
	}
	if ((int) $stats['users_today'] > 0) {
		$activity[] = [
			'type' => 'customer',
			'title' => 'Customer registered',
			'meta' => '+' . (int) $stats['users_today'] . ' today',
			'time' => '',
		];
	}
	if ((int) $stats['products_low_stock'] > 0) {
		$activity[] = [
			'type' => 'stock',
			'title' => 'Stock warning',
			'meta' => (int) $stats['products_low_stock'] . ' products low',
			'time' => '',
		];
	}
	if ((int) $stats['pending_reviews'] > 0) {
		$activity[] = [
			'type' => 'review',
			'title' => 'Review added',
			'meta' => (int) $stats['pending_reviews'] . ' awaiting approval',
			'time' => '',
		];
	}
	$activity = array_slice($activity, 0, 8);

	$dashboardContext = [
		'stats' => $stats,
		'recentOrders' => $recentOrders,
		'topProducts' => $charts['top_products'],
		'chartStatus' => $charts['status'],
		'adminUser' => $adminUser,
	];

	$dashboardHooks = [
		'admin_dashboard_top',
		'admin_dashboard_kpi',
		'admin_dashboard_main_left',
		'admin_dashboard_main_right',
		'admin_dashboard_bottom',
	];

	$smarty->assign([
		'stats' => $stats,
		'revenueTrend' => $revenueTrend,
		'ordersTrend' => $ordersTrend,
		'chartDaily' => json_encode($charts['daily'], JSON_UNESCAPED_UNICODE),
		'topProducts' => $charts['top_products'],
		'lowStockProducts' => $lowStockProducts,
		'activityFeed' => $activity,
		'opsMax' => $opsMax,
		'opsRings' => $opsRings,
		'recentOrders' => $recentOrders,
		'recentMarketplaceOrders' => $recentMarketplaceOrders,
		'orders' => $recentOrders,
		'statusPending' => Order::STATUS_PENDING,
		'statusProcessing' => Order::STATUS_PROCESSING,
		'statusShipped' => Order::STATUS_SHIPPED,
		'adminUseCharts' => true,
		'adminUseOrderStatus' => true,
		'orderStatusApiUrl' => Admin::url('order-status'),
		'statusOptions' => Order::getStatusOptions(),
		'adminHooks' => Module::renderAdminHooks($dashboardHooks, $dashboardContext),
	]);

	AdminPage::add('dashboard', 'Dashboard');
