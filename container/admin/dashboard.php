<?php
	if (!defined('IN_ADMIN')) {
		exit;
	}

	$period = Admin::normalizeDashboardPeriod((string) Tools::getValue('period', 'month'));
	$analytics = Admin::getDashboardAnalytics($period);
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

	$periodPills = [
		['key' => 'month', 'label' => 'This month'],
		['key' => '7', 'label' => 'Last 7 days'],
		['key' => '15', 'label' => 'Last 15 days'],
		['key' => '30', 'label' => 'Last 30 days'],
	];

	$dashboardContext = [
		'stats' => $stats,
		'analytics' => $analytics,
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

	$dailySalesChart = $analytics['daily_sales_chart'] ?? [
		'labels' => [],
		'data' => [],
		'marketplace_data' => [],
		'colors' => [],
		'marketplace_color' => '#F97316',
		'store_label' => 'Store sales',
		'marketplace_label' => 'Marketplace sales',
	];
	$dailySalesChart['store_label'] = adminT((string) ($dailySalesChart['store_label'] ?? 'Store sales'));
	$dailySalesChart['marketplace_label'] = adminT((string) ($dailySalesChart['marketplace_label'] ?? 'Marketplace sales'));

	$mpSalesChart = $analytics['mp_sales_chart'] ?? ['items' => [], 'total' => 0];

	$smarty->assign([
		'stats' => $stats,
		'analytics' => $analytics,
		'period' => $period,
		'periodPills' => $periodPills,
		'revenueTrend' => $revenueTrend,
		'ordersTrend' => $ordersTrend,
		'chartDaily' => json_encode($charts['daily'], JSON_UNESCAPED_UNICODE),
		'chartDailySales' => json_encode($dailySalesChart, JSON_UNESCAPED_UNICODE),
		'chartMpSales' => json_encode($mpSalesChart, JSON_UNESCAPED_UNICODE),
		'topProducts' => $charts['top_products'],
		'lowStockProducts' => $lowStockProducts,
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
