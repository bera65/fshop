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

$start = (string) Tools::getValue('start_date', date('Y-m-d', strtotime('-30 days')));
$end = (string) Tools::getValue('end_date', date('Y-m-d'));
$page = (int) Tools::getValue('page', 0);

$result = Bifatura\InvoiceService::fetchInboxInvoices($start, $end, $page);

echo json_encode([
	'success' => !empty($result['ok']),
	'message' => $result['message'] ?? '',
	'items' => $result['items'] ?? [],
], JSON_UNESCAPED_UNICODE);
exit;
