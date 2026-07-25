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
$barcode = trim((string) Tools::getValue('barcode', ''));

if ($idProduct <= 0) {
	echo json_encode(['success' => false, 'message' => 'Ürün ID gerekli'], JSON_UNESCAPED_UNICODE);
	exit;
}

$result = Trendyol\ProductSyncService::linkExistingProduct($idProduct, $barcode);

echo json_encode([
	'success' => $result['ok'],
	'message' => $result['message'],
	'mapping' => $result['mapping'] ?? Trendyol\ProductSyncService::findMapping($idProduct),
], JSON_UNESCAPED_UNICODE);
exit;
