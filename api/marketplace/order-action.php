<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

header('Content-Type: application/json; charset=utf-8');

if (!Admin::isLoggedIn()) {
	echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim'], JSON_UNESCAPED_UNICODE);
	exit;
}

$op = strtolower(trim((string) Tools::getValue('op', '')));
$platform = strtolower(trim((string) Tools::getValue('platform', '')));
$orderNumber = trim((string) Tools::getValue('order_number', ''));
$packageId = trim((string) Tools::getValue('package_id', ''));
$stockMode = strtolower(trim((string) Tools::getValue('stock_mode', 'restore')));

if ($op === 'import') {
	$result = MarketplaceOrderOps::import($platform, $orderNumber);
} elseif ($op === 'refresh') {
	$result = MarketplaceOrderOps::refresh($platform, $orderNumber, $packageId);
} elseif ($op === 'delete') {
	$result = MarketplaceOrderOps::delete($platform, $orderNumber, $packageId);
} elseif ($op === 'cancel') {
	$result = MarketplaceOrderOps::cancel($platform, $orderNumber, $packageId, $stockMode);
} else {
	$result = ['ok' => false, 'message' => 'Geçersiz işlem'];
}

echo json_encode([
	'success' => !empty($result['ok']),
	'message' => (string) ($result['message'] ?? ''),
	'count' => $result['count'] ?? null,
], JSON_UNESCAPED_UNICODE);
exit;
