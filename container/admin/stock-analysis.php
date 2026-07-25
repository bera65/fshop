<?php
	if (!defined('IN_ADMIN')) {
		exit;
	}

	require_once dirname(__DIR__, 2) . '/core/StockAnalysis.php';

	$flash = '';
	$flashType = 'success';
	$days = max(1, min(90, (int) Tools::getValue('days', 30)));

	if (Tools::isSubmit('quickAddStock') && hash_equals($adminToken, (string) Tools::getValue('token'))) {
		$result = StockAnalysis::quickAddStock(
			(int) Tools::getValue('id_product'),
			(int) Tools::getValue('add_qty')
		);
		$flash = $result['message'];
		$flashType = $result['success'] ? 'success' : 'danger';
	}

	$lowStockRows = StockAnalysis::getLowStockRows($days);
	$outOfStockRows = StockAnalysis::getOutOfStockBestSellers($days);

	$smarty->assign([
		'flash' => $flash,
		'flashType' => $flashType,
		'days' => $days,
		'lowStockRows' => $lowStockRows,
		'outOfStockRows' => $outOfStockRows,
	]);

	AdminPage::add('stock-analysis', 'Stock Analysis');
