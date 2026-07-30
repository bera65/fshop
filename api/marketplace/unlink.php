<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

header('Content-Type: application/json; charset=utf-8');

if (!Admin::isLoggedIn()) {
	echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim'], JSON_UNESCAPED_UNICODE);
	exit;
}

$idProduct = (int) Tools::getValue('id_product', 0);
$platform = strtolower(trim((string) Tools::getValue('platform', 'trendyol')));

if ($idProduct <= 0) {
	echo json_encode(['success' => false, 'message' => 'Ürün ID gerekli'], JSON_UNESCAPED_UNICODE);
	exit;
}

if ($platform === 'hepsiburada') {
	$result = Hepsiburada\ProductSyncService::unlinkProduct($idProduct);
} elseif ($platform === 'n11') {
	$result = N11\ProductSyncService::unlinkProduct($idProduct);
} else {
	$result = Trendyol\ProductSyncService::unlinkProduct($idProduct);
}

echo json_encode([
	'success' => $result['ok'],
	'message' => $result['message'],
	'platform' => $platform,
], JSON_UNESCAPED_UNICODE);
exit;
