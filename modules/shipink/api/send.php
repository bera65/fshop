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
	$sessionToken = (string) ($_SESSION['csrf_token'] ?? '');
	$adminOk = !empty($_SESSION['admin_id']) && $sessionToken !== '' && hash_equals($sessionToken, (string) $token);

	if (!$adminOk) {
		http_response_code(403);
		echo json_encode(['success' => false, 'message' => 'Yetkisiz']);
		exit;
	}

	/** @var ShipinkModule $module */
	$idOrder = (int) Tools::getValue('id_order');
	$error = $module->submitCargo($idOrder, true);

	if ($error !== null) {
		http_response_code(400);
		echo json_encode(['success' => false, 'message' => $error]);
		exit;
	}

	$row = $module->getCargoRow($idOrder);

	echo json_encode([
		'success' => true,
		'message' => 'Shipink gönderisi oluşturuldu',
		'cargo' => $row,
	], JSON_UNESCAPED_UNICODE);
