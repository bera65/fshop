<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

if (!class_exists('Admin')) {
	require_once dirname(__DIR__, 3) . '/core/Admin.php';
}

header('Content-Type: application/json; charset=utf-8');

Admin::requireModuleApiAuth();

require_once dirname(__DIR__) . '/lib/ShopierApi.php';
require_once dirname(__DIR__) . '/lib/ProductSyncService.php';

$idProduct = (int) Tools::getValue('id_product', 0);

if ($idProduct <= 0) {
	echo json_encode(['success' => false, 'message' => 'Ürün ID gerekli'], JSON_UNESCAPED_UNICODE);
	exit;
}

$result = Shopier\ProductSyncService::deleteFromShopier($idProduct);

echo json_encode([
	'success' => $result['ok'],
	'message' => $result['message'],
], JSON_UNESCAPED_UNICODE);
exit;
