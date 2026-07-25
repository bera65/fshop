<?php

if (!defined('IN_SCRIPT')) {
    exit;
}

if (!class_exists('Admin')) {
    require_once dirname(__DIR__, 3) . '/core/Admin.php';
}

Admin::requireModuleApiAuth();

use BackupPro\Repository\BackupRepository;
use BackupPro\Repository\QueueRepository;
use BackupPro\Service\StorageService;

header('Content-Type: application/json; charset=utf-8');

$stats = BackupRepository::getStats();
$stats['pending_queue'] = QueueRepository::countPending();

$storageService = new StorageService();
$backupDir = $storageService->getBackupDir();
$freeDisk = @disk_free_space($backupDir);
$totalDisk = @disk_total_space($backupDir);

$stats['formatted_total_bytes'] = \BackupPro\Core\Settings::formatBytes($stats['total_bytes']);
$stats['formatted_free_disk'] = $freeDisk !== false ? \BackupPro\Core\Settings::formatBytes($freeDisk) : 'Bilinmiyor';
$stats['formatted_total_disk'] = $totalDisk !== false ? \BackupPro\Core\Settings::formatBytes($totalDisk) : 'Bilinmiyor';
$stats['disk_usage_pct'] = ($totalDisk > 0 && $freeDisk !== false) ? round((($totalDisk - $freeDisk) / $totalDisk) * 100, 1) : 0;

if ($stats['largest']) {
    $stats['largest']['formatted_size'] = \BackupPro\Core\Settings::formatBytes($stats['largest']['file_size']);
}
if ($stats['smallest']) {
    $stats['smallest']['formatted_size'] = \BackupPro\Core\Settings::formatBytes($stats['smallest']['file_size']);
}

// System Health Checks
$health = [
    'php_version' => PHP_VERSION,
    'zip_supported' => class_exists('ZipArchive'),
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
];

if (ob_get_length()) { @ob_clean(); }
echo json_encode([
    'success' => true,
    'stats' => $stats,
    'health' => $health
]);
exit;
