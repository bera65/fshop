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

if (!MobilAppService::isPushEnabled()) {
	echo json_encode(['success' => false, 'message' => 'Push bildirimleri kapalı']);
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

if (!is_array($payload)) {
	http_response_code(400);
	echo json_encode(['success' => false, 'message' => 'Geçersiz abonelik verisi']);
	exit;
}

$idUser = Customer::getId();
$userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');

if (PushSubscriptionService::saveForUser($idUser, $payload, $userAgent)) {
	echo json_encode(['success' => true, 'message' => 'Push aboneliği kaydedildi']);
	exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Abonelik kaydedilemedi']);
