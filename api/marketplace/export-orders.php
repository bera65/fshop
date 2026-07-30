<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

if (!class_exists('Admin')) {
	require_once dirname(__DIR__, 2) . '/core/Admin.php';
}

require_once dirname(__DIR__, 2) . '/core/MarketplaceAdmin.php';

if (!Admin::isLoggedIn()) {
	http_response_code(403);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim'], JSON_UNESCAPED_UNICODE);
	exit;
}

$token = (string) Tools::getValue('token');
$sessionToken = (string) ($_SESSION['admin_csrf_token'] ?? '');

if ($token === '' || !hash_equals($sessionToken, $token)) {
	http_response_code(403);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(['success' => false, 'message' => 'Geçersiz istek'], JSON_UNESCAPED_UNICODE);
	exit;
}

$platform = trim((string) Tools::getValue('marketplace_platform', Tools::getValue('platform', 'all')));
$startDate = trim((string) Tools::getValue('start_date', ''));
$endDate = trim((string) Tools::getValue('end_date', ''));
$orderNumber = trim((string) Tools::getValue('order_number', ''));
$customerName = trim((string) Tools::getValue('customer_name', ''));
$productQuery = trim((string) Tools::getValue('product_query', ''));
$orderStatus = trim((string) Tools::getValue('order_status', 'all'));

$orders = MarketplaceAdmin::getMarketplaceOrdersForFilters([
	'marketplace_platform' => $platform,
	'start_date' => $startDate,
	'end_date' => $endDate,
	'order_number' => $orderNumber,
	'customer_name' => $customerName,
	'product_query' => $productQuery,
	'order_status' => $orderStatus,
]);

$root = dirname(__DIR__, 2);

if (!class_exists('SimpleXLSXGen', false)) {
	require_once $root . '/libs/SimpleGEN.php';
}

if (!class_exists('SimpleXLSX', false)) {
	require_once $root . '/libs/SimpleXLSX.php';
}

$books = [[
		'<b>Pazaryeri</b>',
		'<b>Sipariş No</b>',
		'<b>Müşteri</b>',
		'<b>Ürün / Stok Kodu</b>',
		'<b>Durum</b>',
		'<b>Kargo</b>',
		'<b>Kargo Takip</b>',
		'<b>Tarih</b>',
		'<b>Satış Tutarı</b>',
	]];

foreach ($orders as $ord) {
	$items = [];
	if (!empty($ord['items']) && is_array($ord['items'])) {
		foreach ($ord['items'] as $it) {
			$name = (string) ($it['name'] ?? '');
			$qty = (int) ($it['quantity'] ?? 0);
			$items[] = $name !== '' ? ($qty > 1 ? ($name . ' x' . $qty) : $name) : '';
		}
	}

	$productStr = trim(implode('; ', array_filter($items, static fn($v) => $v !== '')));
	if ($productStr === '') {
		$productStr = '—';
	}

	$dateStr = '';
	if (!empty($ord['date_day'])) {
		$dateStr = (string) $ord['date_day'];
		if (!empty($ord['date_time'])) {
			$dateStr .= ' ' . (string) $ord['date_time'];
		}
	}

	$books[] = [
		(string) ($ord['platform_label'] ?? $ord['platform'] ?? ''),
		(string) ($ord['order_number'] ?? ''),
		(string) ($ord['customer_name'] ?? ''),
		$productStr,
		(string) ($ord['status'] ?? ''),
		(string) ($ord['cargo_provider'] ?? ''),
		(string) ($ord['cargo_tracking_number'] ?? ''),
		$dateStr !== '' ? $dateStr : (string) ($ord['sort_date'] ?? ''),
		(float) ($ord['total_price_value'] ?? 0),
	];
}

$name = 'marketplace-orders.xlsx';
header('Content-Disposition: attachment; filename="' . $name . '"');
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

SimpleXLSXGen::fromArray($books)->downloadAs($name);
exit;

