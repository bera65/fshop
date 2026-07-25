<?php

if (!defined('IN_SCRIPT')) {
	exit;
}

if (!class_exists('Admin')) {
	require_once dirname(__DIR__, 3) . '/core/Admin.php';
}

header('Content-Type: application/json; charset=utf-8');

Admin::requireModuleApiAuth();

require_once dirname(__DIR__) . '/lib/InvoiceService.php';

$idOrder = (int) Tools::getValue('id_order', 0);

if ($idOrder <= 0) {
	echo json_encode(['success' => false, 'message' => 'Sipariş ID gerekli']);
	exit;
}

$result = Bifatura\InvoiceService::createFromOrder($idOrder);

echo json_encode([
	'success' => $result['ok'],
	'message' => $result['message'],
	'invoice' => $result['invoice'] ?? null,
	'pdf_link' => $result['pdf_link'] ?? '',
], JSON_UNESCAPED_UNICODE);
exit;
