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

$uuid = trim((string) Tools::getValue('uuid', ''));
$systemType = trim((string) Tools::getValue('system_type', 'EFATURA'));

$result = Bifatura\InvoiceService::getInboxPdfLink($uuid, $systemType);

echo json_encode([
	'success' => !empty($result['ok']),
	'message' => $result['message'] ?? '',
	'pdfLink' => $result['pdfLink'] ?? '',
	'uuid' => $result['uuid'] ?? '',
], JSON_UNESCAPED_UNICODE);
exit;
