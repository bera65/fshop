<?php

if (!defined('IN_SCRIPT')) {
    exit;
}

if (!class_exists('Admin')) {
    require_once dirname(__DIR__, 3) . '/core/Admin.php';
}

Admin::requireModuleApiAuth();

use BackupPro\Repository\BackupRepository;
use BackupPro\Repository\LogRepository;
use BackupPro\Service\StorageService;

header('Content-Type: application/json; charset=utf-8');

$backupId = (int)Tools::getValue('id', 0);
$backup = BackupRepository::findById($backupId);

if ($backup) {
    $storageService = new StorageService();
    $fullPath = $storageService->resolveFilePath($backup['file_path']);

    if (file_exists($fullPath)) {
        @unlink($fullPath);
    }

    $tempSql = $storageService->getBackupDir() . "temp_db_{$backupId}.sql";
    $scanFile = $storageService->getScanFilePath($backupId);
    if (file_exists($tempSql)) {
        @unlink($tempSql);
    }
    if (file_exists($scanFile)) {
        @unlink($scanFile);
    }

    BackupRepository::delete($backupId);
    LogRepository::log(null, 'info', 'DELETE', "Yedek dosyası ve kaydı silindi: {$backup['backup_name']}");

    if (ob_get_length()) { @ob_clean(); }
    echo json_encode(['success' => true, 'message' => 'Yedek başarıyla silindi.']);
} else {
    if (ob_get_length()) { @ob_clean(); }
    echo json_encode(['success' => false, 'message' => 'Yedek bulunamadı.']);
}
exit;
