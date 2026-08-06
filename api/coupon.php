<?php
define('IN_SCRIPT', true);
require_once dirname(__DIR__) . '/config/settings.php';

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
$cart = Cart::getSummary();
$subtotal = (float) $cart['total'];

switch ($action) {
	case 'apply':
		echo json_encode(Coupon::apply((string) Tools::getValue('code'), $subtotal));
		break;

	case 'remove':
		echo json_encode(array_merge(Coupon::remove(), Coupon::getCheckoutSummary($subtotal, $cart)));
		break;

	case 'set_cargo':
		if (!class_exists('Cargo')) {
			require_once dirname(__DIR__) . '/core/Cargo.php';
		}

		$idCargo = (int) Tools::getValue('id_cargo');

		if (!Cargo::setSelectedId($idCargo)) {
			http_response_code(400);
			echo json_encode(['success' => false, 'message' => 'Geçersiz kargo seçimi']);
			break;
		}

		echo json_encode(array_merge(['success' => true], Coupon::getCheckoutSummary($subtotal, $cart)));
		break;

	case 'set_payment':
		$method = (string) Tools::getValue('payment_method');

		if (!Order::setSelectedPaymentMethod($method)) {
			http_response_code(400);
			echo json_encode(['success' => false, 'message' => 'Geçersiz ödeme yöntemi']);
			break;
		}

		echo json_encode(array_merge(['success' => true], Coupon::getCheckoutSummary($subtotal, $cart)));
		break;

	case 'set_gift_wrap':
		Order::setGiftWrapSelected((bool) Tools::getValue('gift_wrap'));
		echo json_encode(array_merge(['success' => true], Coupon::getCheckoutSummary($subtotal, $cart)));
		break;

	case 'summary':
		echo json_encode(array_merge(['success' => true], Coupon::getCheckoutSummary($subtotal, $cart)));
		break;

	default:
		http_response_code(400);
		echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
}
