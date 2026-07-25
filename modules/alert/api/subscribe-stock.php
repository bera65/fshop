<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode(['success' => false, 'message' => 'Method not allowed']);
	exit;
}

$csrf = Tools::getValue('token') ?: ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

if (!hash_equals($_SESSION['csrf_token'] ?? '', (string) $csrf)) {
	http_response_code(403);
	echo json_encode(['success' => false, 'message' => translate('Invalid request')]);
	exit;
}

$idProduct = (int) Tools::getValue('id_product', 0);
$email = trim((string) Tools::getValue('email', ''));
$idVariation = (int) Tools::getValue('id_variation', 0);
$idUser = Customer::isLoggedIn() ? Customer::getId() : null;

$result = AlertService::subscribeStockAlert($idProduct, $email, $idVariation, $idUser);

echo json_encode($result);
