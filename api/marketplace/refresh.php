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

if ($idProduct <= 0) {
	echo json_encode(['success' => false, 'message' => 'Ürün ID gerekli'], JSON_UNESCAPED_UNICODE);
	exit;
}

$result = Trendyol\ProductSyncService::refreshFromTrendyol($idProduct);

echo json_encode([
	'success' => $result['ok'],
	'message' => $result['message'],
	'mapping' => $result['mapping'] ?? Trendyol\ProductSyncService::findMapping($idProduct),
], JSON_UNESCAPED_UNICODE);
exit;
