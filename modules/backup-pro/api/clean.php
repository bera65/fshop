<?php

if (!defined('IN_SCRIPT')) {
    exit;
}

if (!class_exists('Admin')) {
    require_once dirname(__DIR__, 3) . '/core/Admin.php';
}

Admin::requireModuleApiAuth();

use BackupPro\Repository\LogRepository;

header('Content-Type: application/json; charset=utf-8');

$type = Tools::getValue('type', 'clear_logs');

if ($type === 'clear_logs') {
    LogRepository::clear();
    if (ob_get_length()) { @ob_clean(); }
    echo json_encode(['success' => true, 'message' => 'İşlem günlükleri başarıyla temizlendi.']);
    exit;
}

if (ob_get_length()) { @ob_clean(); }
echo json_encode(['success' => false, 'message' => 'Geçersiz temizleme türü']);
exit;
