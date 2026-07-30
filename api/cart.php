<?php
define('IN_SCRIPT', true);
require_once dirname(__DIR__) . '/config/settings.php';
require_once dirname(__DIR__) . '/core/Cart.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode(['success' => false, 'message' => 'Method not allowed']);
	exit;
}

$token = Tools::getValue('token') ?: ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

if (!hash_equals($_SESSION['csrf_token'] ?? '', (string) $token)) {
	http_response_code(403);
	echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
	exit;
}

$action = Tools::getValue('action');
$idProduct = (int) Tools::getValue('id_product');
$idVariation = (int) Tools::getValue('id_variation');
$qty = (float) str_replace(',', '.', (string) Tools::getValue('qty', '1'));
$cartKey = trim((string) Tools::getValue('cart_key'));
$width = (float) str_replace(',', '.', (string) Tools::getValue('width', '0'));
$length = (float) str_replace(',', '.', (string) Tools::getValue('length', '0'));
$optionsRaw = Tools::getValue('options');
$options = [];

if (is_string($optionsRaw) && $optionsRaw !== '') {
	$decoded = json_decode($optionsRaw, true);
	$options = is_array($decoded) ? $decoded : [];
} elseif (is_array($optionsRaw)) {
	$options = $optionsRaw;
}

$measure = null;
if ($width > 0 && $length > 0) {
	$measure = [
		'sale_unit' => SaleUnit::M2,
		'width_m' => $width,
		'length_m' => $length,
	];
}

switch ($action) {
	case 'add':
		echo json_encode(Cart::add($idProduct, $qty, $idVariation, $options, $measure));
		break;
	case 'update':
		echo json_encode(Cart::update($idProduct, $qty, $idVariation, $cartKey));
		break;
	case 'remove':
		echo json_encode(Cart::remove($idProduct, $idVariation, $cartKey));
		break;
	case 'clear':
		echo json_encode(Cart::clear());
		break;
	case 'get':
		echo json_encode(array_merge(['success' => true, 'message' => ''], Cart::getSummary()));
		break;
	default:
		http_response_code(400);
		echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
}
