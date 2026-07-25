<?php

if (!defined('IN_SCRIPT')) {
    exit;
}

if (!class_exists('Admin')) {
    require_once dirname(__DIR__, 3) . '/core/Admin.php';
}

Admin::requireModuleApiAuth();

use BackupPro\Core\Settings;
use BackupPro\Repository\LogRepository;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $allowed = [
        'compression_level',
        'archive_format',
        'chunk_size_files',
        'chunk_size_db_rows',
        'max_backup_size_mb',
        'exclude_folders',
        'exclude_files',
        'regex_exclusions',
        'auto_prune_keep_count',
        'email_notifications',
        'notification_email',
        'encryption_enabled',
        'encryption_password'
    ];

    $data = [];
    foreach ($allowed as $key) {
        if (isset($_POST[$key])) {
            $data[$key] = trim((string)$_POST[$key]);
        }
    }

    Settings::saveAll($data);
    LogRepository::log(null, 'info', 'SETTINGS', 'Modül ayarları güncellendi.');

    if (ob_get_length()) { @ob_clean(); }
    echo json_encode(['success' => true, 'message' => 'Ayarlar başarıyla kaydedildi.']);
    exit;
}

$all = Settings::getAll();
if (ob_get_length()) { @ob_clean(); }
echo json_encode(['success' => true, 'settings' => $all]);
exit;
