<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/lib/PushSubscriptionService.php';

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

if (!Customer::isLoggedIn()) {
	http_response_code(401);
	echo json_encode(['success' => false, 'message' => 'Giriş yapmalısınız']);
	exit;
}

$raw = file_get_contents('php://input');
$payload = is_string($raw) ? json_decode($raw, true) : null;
$endpoint = '';

if (is_array($payload)) {
	$endpoint = trim((string) ($payload['endpoint'] ?? ''));
}

if ($endpoint === '') {
	$endpoint = trim((string) Tools::getValue('endpoint'));
}

if ($endpoint === '') {
	http_response_code(400);
	echo json_encode(['success' => false, 'message' => 'Endpoint gerekli']);
	exit;
}

$idUser = Customer::getId();

if (PushSubscriptionService::removeByEndpoint($endpoint, $idUser)) {
	echo json_encode(['success' => true, 'message' => 'Abonelik kaldırıldı']);
	exit;
}

echo json_encode(['success' => true, 'message' => 'Abonelik bulunamadı']);
