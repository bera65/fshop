<?php

if (!defined('IN_SCRIPT')) {
    exit;
}

if (!class_exists('Admin')) {
    require_once dirname(__DIR__, 3) . '/core/Admin.php';
}

Admin::requireModuleApiAuth();

use BackupPro\Service\ScannerService;

header('Content-Type: application/json; charset=utf-8');

$scanner = new ScannerService();
$fileScan = $scanner->scanFiles();
$dbScan = $scanner->scanDatabase();

if (ob_get_length()) { @ob_clean(); }
echo json_encode([
    'success' => true,
    'file_scan' => [
        'total_files' => $fileScan['total_files'],
        'total_bytes' => $fileScan['total_bytes'],
        'formatted_bytes' => \BackupPro\Core\Settings::formatBytes($fileScan['total_bytes']),
        'unreadable_count' => $fileScan['unreadable_count']
    ],
    'db_scan' => [
        'total_tables' => $dbScan['total_tables'],
        'total_rows' => $dbScan['total_rows'],
        'total_bytes' => $dbScan['total_bytes'],
        'formatted_bytes' => \BackupPro\Core\Settings::formatBytes($dbScan['total_bytes']),
        'tables' => $dbScan['tables']
    ]
]);
exit;
