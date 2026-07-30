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

$saleOverride = null;
$listOverride = null;
$saleRaw = Tools::getValue('sale_price', null);
$listRaw = Tools::getValue('list_price', null);

if ($saleRaw !== null && $saleRaw !== '') {
	$saleOverride = (float) str_replace(',', '.', (string) $saleRaw);
}

if ($listRaw !== null && $listRaw !== '') {
	$listOverride = (float) str_replace(',', '.', (string) $listRaw);
}

if ($platform === 'hepsiburada') {
	$result = Hepsiburada\ProductSyncService::updatePriceStock($idProduct, $saleOverride, $listOverride);
	$mapping = $result['mapping'] ?? Hepsiburada\ProductSyncService::findMapping($idProduct);
} elseif ($platform === 'n11') {
	$result = N11\ProductSyncService::updatePriceStock($idProduct, $saleOverride, $listOverride);
	$mapping = $result['mapping'] ?? N11\ProductSyncService::findMapping($idProduct);
} else {
	$result = Trendyol\ProductSyncService::updatePriceStock($idProduct, $saleOverride, $listOverride);
	$mapping = $result['mapping'] ?? Trendyol\ProductSyncService::findMapping($idProduct);
}

echo json_encode([
	'success' => $result['ok'],
	'message' => $result['message'],
	'mapping' => $mapping,
	'platform' => $platform,
], JSON_UNESCAPED_UNICODE);
exit;
