<?php

if (!defined('IN_SCRIPT')) {
    exit;
}

if (!class_exists('Admin')) {
    require_once dirname(__DIR__, 3) . '/core/Admin.php';
}

Admin::requireModuleApiAuth();

use BackupPro\Service\QueueService;

header('Content-Type: application/json; charset=utf-8');

$queueService = new QueueService();
$res = $queueService->processBatch();

if (ob_get_length()) { @ob_clean(); }
echo json_encode([
    'success' => true,
    'stats' => $res
]);
exit;
