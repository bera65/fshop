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

    $storageService = new StorageService();
    $fullZipPath = $storageService->resolveFilePath($backup['file_path']);

    if (!$storageService->isSafeBackupArchive($fullZipPath)) {
        if (ob_get_length()) { @ob_clean(); }
        echo json_encode(['success' => false, 'message' => 'Yedek arşivi doğrulanamadı.']);
        exit;
    }

    $staging = $storageService->createRestoreStagingDir();
    if ($staging === null) {
        if (ob_get_length()) { @ob_clean(); }
        echo json_encode(['success' => false, 'message' => 'Geri yükleme klasörü oluşturulamadı.']);
        exit;
    }

    $ok = false;
    try {
        $dumpPath = $restoreService->extractVerifiedSqlDump($fullZipPath, $staging);
        if ($dumpPath === null) {
            if (ob_get_length()) { @ob_clean(); }
            echo json_encode(['success' => false, 'message' => 'Arşiv kökünde geçerli database.sql bulunamadı.']);
            exit;
        }

        $ok = $restoreService->executeSqlDumpChunk($dumpPath);
        if (!$ok) {
            LogRepository::log($backupId, 'error', 'RESTORE', 'Veritabanı geri yüklemesi başarısız.');
            if (ob_get_length()) { @ob_clean(); }
            echo json_encode(['success' => false, 'message' => 'Veritabanı geri yüklemesi başarısız.']);
            exit;
        }

        LogRepository::log($backupId, 'success', 'RESTORE', 'Veritabanı yedeği geri yüklendi. ZIP içindeki uygulama dosyaları yazılmadı.');

        if (ob_get_length()) { @ob_clean(); }
        echo json_encode([
            'success' => true,
            'message' => 'Veritabanı yedeği geri yüklendi. Uygulama dosyaları ZIP içinden yazılmaz.',
            'extracted_files' => 0
        ]);
        exit;
    } catch (\Throwable $e) {
        LogRepository::log($backupId, 'error', 'RESTORE', 'Veritabanı geri yüklemesi başarısız.');
        if (ob_get_length()) { @ob_clean(); }
        echo json_encode(['success' => false, 'message' => 'Veritabanı geri yüklemesi başarısız.']);
        exit;
    } finally {
        $storageService->removeRestoreStagingDir($staging);
    }
}
