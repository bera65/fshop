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
use BackupPro\Repository\LogRepository;
use BackupPro\Service\StorageService;

header('Content-Type: application/json; charset=utf-8');

$type = trim((string)Tools::getValue('type', 'full')); // full, db_only, files_only, custom
$archiveFormat = 'zip';
$backupName = trim((string)Tools::getValue('name'));

if ($backupName === '') {
    $backupName = 'backup_' . $type . '_' . date('Y-m-d_H-i-s');
}

$storageService = new StorageService();
$filename = $backupName . '.zip';
$filePath = $storageService->getBackupDir() . $filename;

$backupId = BackupRepository::save([
    'backup_name'    => $backupName,
    'type'           => $type,
    'archive_format' => $archiveFormat,
    'file_path'      => $filePath,   // mutlak yol — ZipArchive gerektirir
    'file_size'      => 0,
    'status'         => 'in_progress',
    'total_files'    => 0,
    'total_tables'   => 0,
]);

// Queue initial scan job
$customFolders = Tools::getValue('custom_folders', []);
if (!is_array($customFolders)) {
    $customFolders = [];
}

QueueRepository::add($backupId, 'scan_files', 1, [
    'custom_folders' => $customFolders
]);

LogRepository::log($backupId, 'info', 'CREATE', "Yeni yedekleme işlemi başlatıldı: {$backupName} ({$type})");

if (ob_get_length()) { @ob_clean(); }
echo json_encode([
    'success' => true,
    'message' => 'Yedekleme işlemi başlatıldı.',
    'backup_id' => $backupId,
    'backup_name' => $backupName
]);
exit;
