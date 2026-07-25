<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

header('Content-Type: application/json; charset=utf-8');

if (!MobilAppService::isPushEnabled()) {
	echo json_encode(['success' => false, 'enabled' => false]);
	exit;
}

$keys = MobilAppService::getVapidKeys();

if ($keys['public'] === '') {
	echo json_encode(['success' => false, 'enabled' => false]);
	exit;
}

echo json_encode([
	'success' => true,
	'enabled' => true,
	'publicKey' => $keys['public'],
]);
