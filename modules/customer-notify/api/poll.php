<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

require_once dirname(__DIR__) . '/lib/CustomerNotifyPush.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!Customer::isLoggedIn()) {
	echo json_encode(['success' => false, 'items' => []], JSON_UNESCAPED_UNICODE);
	exit;
}

if (!CustomerNotifyPush::isEnabled()) {
	echo json_encode(['success' => true, 'items' => [], 'enabled' => false], JSON_UNESCAPED_UNICODE);
	exit;
}

$items = CustomerNotifyPush::claimPending(Customer::getId(), 10);

echo json_encode([
	'success' => true,
	'enabled' => true,
	'items' => $items,
], JSON_UNESCAPED_UNICODE);
exit;
