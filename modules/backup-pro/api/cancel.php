<?php

if (!defined('IN_SCRIPT')) {
    exit;
}

if (!class_exists('Admin')) {
    require_once dirname(__DIR__, 3) . '/core/Admin.php';
}

Admin::requireModuleApiAuth();

use BackupPro\Repository\QueueRepository;
use BackupPro\Repository\BackupRepository;
use BackupPro\Repository\LogRepository;
use BackupPro\Service\StorageService;

header('Content-Type: application/json; charset=utf-8');

$backupId = (int)Tools::getValue('backup_id', 0);

if ($backupId > 0) {
    QueueRepository::recoverLockedJobsForBackup($backupId);
    QueueRepository::deleteByBackupId($backupId);
    $backup = BackupRepository::findById($backupId);
    if ($backup) {
        $storageService = new StorageService();
        @unlink($storageService->getScanFilePath($backupId));
        @unlink($storageService->getBackupDir() . "temp_db_{$backupId}.sql");

        $backup['status'] = 'failed';
        $backup['error_message'] = 'Kullanıcı tarafından iptal edildi.';
        BackupRepository::save($backup);
    }
    LogRepository::log($backupId, 'warning', 'CANCEL', 'Yedekleme işlemi kullanıcı tarafından iptal edildi.');
} else {
    QueueRepository::clear();
}

if (ob_get_length()) { @ob_clean(); }
echo json_encode(['success' => true, 'message' => 'İşlem iptal edildi.']);
exit;
