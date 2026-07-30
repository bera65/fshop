<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

header('Content-Type: application/json; charset=utf-8');

if (!Admin::isLoggedIn()) {
	http_response_code(401);
	echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim'], JSON_UNESCAPED_UNICODE);
	exit;
}

$idProduct = (int) Tools::getValue('id_product', 0);
$platform = strtolower(trim((string) Tools::getValue('platform', 'trendyol')));
$barcode = trim((string) Tools::getValue('barcode', Tools::getValue('merchant_sku', Tools::getValue('stock_code', ''))));

if ($idProduct <= 0) {
	echo json_encode(['success' => false, 'message' => 'Ürün ID gerekli'], JSON_UNESCAPED_UNICODE);
	exit;
}

if ($platform === 'hepsiburada') {
	$result = Hepsiburada\ProductSyncService::linkExistingProduct($idProduct, $barcode);
	$mapping = $result['mapping'] ?? Hepsiburada\ProductSyncService::findMapping($idProduct);
} elseif ($platform === 'n11') {
	$result = N11\ProductSyncService::linkExistingProduct($idProduct, $barcode);
	$mapping = $result['mapping'] ?? N11\ProductSyncService::findMapping($idProduct);
} else {
	$result = Trendyol\ProductSyncService::linkExistingProduct($idProduct, $barcode);
	$mapping = $result['mapping'] ?? Trendyol\ProductSyncService::findMapping($idProduct);
}

echo json_encode([
	'success' => $result['ok'],
	'message' => $result['message'],
	'mapping' => $mapping,
	'platform' => $platform,
], JSON_UNESCAPED_UNICODE);
exit;
