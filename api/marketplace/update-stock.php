<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

header('Content-Type: application/json; charset=utf-8');

$idProduct = (int) Tools::getValue('id_product');
$stock = (int) Tools::getValue('stock');
$syncMarketplaces = !empty(Tools::getValue('sync_marketplaces'));

if ($idProduct <= 0) {
	http_response_code(400);
	echo json_encode(['success' => false, 'message' => 'Geçersiz ürün ID'], JSON_UNESCAPED_UNICODE);
	exit;
}

if ($stock < 0) {
	http_response_code(400);
	echo json_encode(['success' => false, 'message' => 'Stok miktarı 0 veya daha büyük olmalıdır'], JSON_UNESCAPED_UNICODE);
	exit;
}

$product = Product::getByIdAdmin($idProduct);

if (!$product) {
	http_response_code(404);
	echo json_encode(['success' => false, 'message' => 'Ürün bulunamadı'], JSON_UNESCAPED_UNICODE);
	exit;
}

$oldStock = Product::getStock($product);
DB::execute('UPDATE products SET stock = ? WHERE id_product = ?', [$stock, $idProduct]);

$ref = trim((string) ($product['stock_code'] ?? ''));

if ($ref === '') {
	$ref = trim((string) ($product['barcode'] ?? ''));
}

if ($ref === '') {
	$ref = (string) $idProduct;
}

if ((int) $oldStock !== (int) $stock) {
	MarketplaceLog::stockChange('', $ref, $oldStock, $stock, 'STOCK_UPDATE', $idProduct);
}

$mpResults = [];
$mpMessage = '';

if ($syncMarketplaces) {
	$mpResults = Marketplace::syncProductStockAcrossPlatforms($idProduct, null);
	$messages = [];
	foreach ($mpResults as $plat => $res) {
		if (!empty($res['ok'])) {
			$messages[] = ucfirst($plat) . ': Stok güncellendi';
		} else {
			$messages[] = ucfirst($plat) . ': ' . ($res['message'] ?? 'Hata');
		}
	}
	if ($messages !== []) {
		$mpMessage = ' (' . implode(', ', $messages) . ')';
	}
}

echo json_encode([
	'success' => true,
	'message' => 'Ürün stoğu güncellendi' . $mpMessage,
	'stock' => $stock,
	'id_product' => $idProduct,
	'marketplace_results' => $mpResults,
], JSON_UNESCAPED_UNICODE);
