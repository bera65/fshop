<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

require_once dirname(__DIR__) . '/lib/CustomerNotifyPush.php';

header('Content-Type: application/json; charset=utf-8');

if (!Customer::isLoggedIn()) {
	echo json_encode(['success' => false, 'message' => 'login_required'], JSON_UNESCAPED_UNICODE);
	exit;
}

CustomerNotifyPush::ensureSchema();

$idUser = Customer::getId();
$action = (string) Tools::getValue('op', 'enable');
$deviceKey = (string) Tools::getValue('device_key', '');

if ($action === 'disable') {
	CustomerNotifyPush::disableDevice($idUser, $deviceKey);
	echo json_encode(['success' => true, 'message' => 'disabled'], JSON_UNESCAPED_UNICODE);
	exit;
}

$subscriptionRaw = Tools::getValue('subscription', '');
$subscription = [];

if (is_string($subscriptionRaw) && $subscriptionRaw !== '') {
	$decoded = json_decode($subscriptionRaw, true);
	$subscription = is_array($decoded) ? $decoded : [];
} elseif (is_array($subscriptionRaw)) {
	$subscription = $subscriptionRaw;
}

$ok = CustomerNotifyPush::saveDevice($idUser, $deviceKey, $subscription);

echo json_encode([
	'success' => $ok,
	'message' => $ok ? 'subscribed' : 'invalid_device',
	'enabled' => CustomerNotifyPush::isEnabled(),
], JSON_UNESCAPED_UNICODE);
exit;
