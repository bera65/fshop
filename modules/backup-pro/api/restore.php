<?php

if (!defined('IN_SCRIPT')) {
    exit;
}

if (!class_exists('Admin')) {
    require_once dirname(__DIR__, 3) . '/core/Admin.php';
}

Admin::requireModuleApiAuth();

use BackupPro\Service\RestoreService;
use BackupPro\Service\StorageService;
use BackupPro\Repository\BackupRepository;
use BackupPro\Repository\LogRepository;

header('Content-Type: application/json; charset=utf-8');

$backupId = (int)Tools::getValue('backup_id', 0);
$mode = Tools::getValue('mode', 'dry_run'); // dry_run or execute

$restoreService = new RestoreService();

if ($mode === 'dry_run') {
    $res = $restoreService->dryRunRestore($backupId);
    if (ob_get_length()) { @ob_clean(); }
    echo json_encode($res);
    exit;
} elseif ($mode === 'execute') {
    $backup = BackupRepository::findById($backupId);
    if (!$backup) {
        if (ob_get_length()) { @ob_clean(); }
        echo json_encode(['success' => false, 'message' => 'Yedek bulunamadı.']);
        exit;
    }

    $rootPath = $restoreService->getRootPath();
    $storageService = new StorageService();
    $fullZipPath = $storageService->resolveFilePath($backup['file_path']);

    // Extract zip files
    $count = $restoreService->restoreZipChunk($fullZipPath, 0, 100000, $rootPath);

    // Extract & execute database.sql if exists
    $zip = new \ZipArchive();
    if ($zip->open($fullZipPath) === true) {
        $tempDb = $storageService->getBackupDir() . 'restore_temp_db.sql';
        if ($zip->extractTo(dirname($tempDb), 'database.sql')) {
            rename(dirname($tempDb) . '/database.sql', $tempDb);
            $restoreService->executeSqlDumpChunk($tempDb);
            @unlink($tempDb);
        }
        $zip->close();
    }

    LogRepository::log($backupId, 'success', 'RESTORE', "Geri yükleme işlemi başarıyla tamamlandı. Dosya sayısı: {$count}");

    if (ob_get_length()) { @ob_clean(); }
    echo json_encode([
        'success' => true,
        'message' => 'Geri yükleme işlemi başarıyla tamamlandı.',
        'extracted_files' => $count
    ]);
    exit;
}
