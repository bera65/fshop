<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

header('Content-Type: application/json; charset=utf-8');

if (!Admin::isLoggedIn()) {
	echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim'], JSON_UNESCAPED_UNICODE);
	exit;
}

if ((string) Tools::getValue('send', '') === '0') {
	Marketplace::setAllowMarketplaceStockPush(false);
}

$platform = strtolower(trim((string) Tools::getValue('platform', 'trendyol')));
$start = trim((string) Tools::getValue('start_date', ''));
$end = trim((string) Tools::getValue('end_date', ''));

if ($platform === 'hepsiburada') {
	$result = Hepsiburada\OrderService::syncOrders(
		$start !== '' ? $start : null,
		$end !== '' ? $end : null
	);
} elseif ($platform === 'n11') {
	$result = N11\OrderService::syncOrders();
} else {
	$result = Trendyol\OrderService::syncOrders(
		$start !== '' ? $start : null,
		$end !== '' ? $end : null
	);
}

echo json_encode([
	'success' => $result['ok'],
	'message' => $result['message'],
	'count' => $result['count'] ?? 0,
	'orders' => $result['orders'] ?? [],
	'platform' => $platform,
], JSON_UNESCAPED_UNICODE);
exit;
