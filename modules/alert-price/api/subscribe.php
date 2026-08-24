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

$token = Tools::getValue('token') ?: ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');

if (!Security::validateCsrf('front') && !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), (string) $token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit;
}

$idProduct 		= (int) Tools::getValue('idProduct', 0);
$email 			= trim((string) Tools::getValue('email', ''));
$targetPrice 	= (float) Tools::getValue('price', 0);

$idUser = Customer::isLoggedIn() ? Customer::getId() : null;

$result = AlertPriceModule::subscribe($idProduct, $email, $targetPrice, $idUser);

echo json_encode($result);