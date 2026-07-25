<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

header('Content-Type: application/json; charset=utf-8');

if (!Admin::isLoggedIn()) {
	echo json_encode(['success' => false, 'message' => 'Yetkisiz eriÅŸim'], JSON_UNESCAPED_UNICODE);
	exit;
}


$idProduct = (int) Tools::getValue('id_product', 0);

if ($idProduct <= 0) {
	echo json_encode(['success' => false, 'message' => 'ÃœrÃ¼n ID gerekli'], JSON_UNESCAPED_UNICODE);
	exit;
}

$meta = [];
$brandId = (int) Tools::getValue('brand_id', 0);
$categoryId = (int) Tools::getValue('category_id', 0);

if ($brandId > 0) {
	$meta['brand_id'] = $brandId;
}

if ($categoryId > 0) {
	$meta['category_id'] = $categoryId;
}

$saleRaw = Tools::getValue('sale_price', null);
$listRaw = Tools::getValue('list_price', null);

if ($saleRaw !== null && $saleRaw !== '') {
	$meta['sale_price'] = (float) str_replace(',', '.', (string) $saleRaw);
}

if ($listRaw !== null && $listRaw !== '') {
	$meta['list_price'] = (float) str_replace(',', '.', (string) $listRaw);
}

$attrsRaw = Tools::getValue('attributes');

if (is_string($attrsRaw) && $attrsRaw !== '') {
	$decoded = json_decode($attrsRaw, true);

	if (is_array($decoded)) {
		$meta['attributes'] = $decoded;
	}
} elseif (is_array($attrsRaw)) {
	$meta['attributes'] = $attrsRaw;
}

$result = Trendyol\ProductSyncService::sync($idProduct, $meta);
$mapping = $result['mapping'] ?? Trendyol\ProductSyncService::findMapping($idProduct);

echo json_encode([
	'success' => $result['ok'],
	'message' => $result['message'],
	'mapping' => $mapping,
], JSON_UNESCAPED_UNICODE);
exit;
