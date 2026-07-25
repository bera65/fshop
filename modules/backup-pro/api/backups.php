<?php

if (!defined('IN_SCRIPT')) {
    exit;
}

if (!class_exists('Admin')) {
    require_once dirname(__DIR__, 3) . '/core/Admin.php';
}

Admin::requireModuleApiAuth();

use BackupPro\Repository\BackupRepository;

header('Content-Type: application/json; charset=utf-8');

$page = max(1, (int)Tools::getValue('page', 1));
$limit = max(1, (int)Tools::getValue('limit', 10));
$offset = ($page - 1) * $limit;

$search = Tools::getValue('search');
$type = Tools::getValue('type');
$sort = Tools::getValue('sort', 'id DESC');

$backups = BackupRepository::getAll($limit, $offset, $search, $type, $sort);
$total = BackupRepository::countAll($search, $type);

foreach ($backups as &$b) {
    $b['formatted_size'] = \BackupPro\Core\Settings::formatBytes($b['file_size']);
    $b['formatted_duration'] = $b['duration_seconds'] . ' sn';
}

if (ob_get_length()) { @ob_clean(); }
echo json_encode([
    'success' => true,
    'backups' => $backups,
    'total' => $total,
    'page' => $page,
    'limit' => $limit,
    'total_pages' => ceil($total / $limit)
]);
exit;
