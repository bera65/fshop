<?php

if (!defined('IN_SCRIPT')) {
    exit;
}

if (!class_exists('Admin')) {
    require_once dirname(__DIR__, 3) . '/core/Admin.php';
}

Admin::requireModuleApiAuth();

use BackupPro\Repository\BackupRepository;
use BackupPro\Service\StorageService;

$backupId = (int)Tools::getValue('id', 0);
$backup = BackupRepository::findById($backupId);

if (!$backup) {
    http_response_code(404);
    echo 'Yedek bulunamadı';
    exit;
}

$storageService = new StorageService();
$fullPath = $storageService->resolveFilePath($backup['file_path']);

if (!file_exists($fullPath)) {
    http_response_code(404);
    echo 'Yedek dosyası sunucuda bulunamadı';
    exit;
}

// Çıktı tamponunu (output buffer) temizle ki ZIP dosyasına ekstra karakter/boşluk eklenmesin (bozulma önleme)
while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($fullPath) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($fullPath));
readfile($fullPath);
exit;
