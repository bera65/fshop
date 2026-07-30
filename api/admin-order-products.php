<?php

define('IN_SCRIPT', true);

require_once dirname(__DIR__) . '/config/install_gate.php';

if (!fshop_is_installed()) {
	http_response_code(503);
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(['success' => false, 'message' => 'Kurulum tamamlanmamış']);
	exit;
}

require_once dirname(__DIR__) . '/config/admin_bootstrap.php';

while (ob_get_level() > 0) {
	ob_end_clean();
}

header('Content-Type: application/json; charset=utf-8');

if (!Admin::isLoggedIn()) {
	http_response_code(401);
	echo json_encode(['success' => false, 'message' => 'Oturum gerekli']);
	exit;
}

$token = (string) Tools::getValue('token');

if ($token === '' || !hash_equals((string) $adminToken, $token)) {
	http_response_code(403);
	echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
	exit;
}

$query = trim((string) Tools::getValue('q', Tools::getValue('query', '')));

if (Tools::strlen($query) < 2) {
	echo json_encode(['success' => true, 'items' => []], JSON_UNESCAPED_UNICODE);
	exit;
}

$like = '%' . $query . '%';
$rows = DB::execute(
	'SELECT p.id_product, p.product_name, p.price, p.stock, p.stock_code, p.barcode, p.active,
		i.id_image
	 FROM products p
	 LEFT JOIN images i ON p.id_product = i.id_product AND i.cover = 1
	 WHERE (p.product_name LIKE ? OR p.stock_code LIKE ? OR p.barcode LIKE ?)
	 ORDER BY p.active DESC, p.product_name ASC
	 LIMIT 12',
	[$like, $like, $like]
) ?: [];

$items = [];

foreach ($rows as $row) {
	$price = (float) ($row['price'] ?? 0);
	$items[] = [
		'id_product' => (int) $row['id_product'],
		'product_name' => (string) $row['product_name'],
		'price' => $price,
		'price_formatted' => Tools::displayPrice($price),
		'stock' => (float) ($row['stock'] ?? 0),
		'stock_code' => (string) ($row['stock_code'] ?? ''),
		'image_url' => Product::getImageUrl(isset($row['id_image']) ? (int) $row['id_image'] : null),
		'active' => (int) ($row['active'] ?? 0) === 1,
	];
}

echo json_encode(['success' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);
exit;
