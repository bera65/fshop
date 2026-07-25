<?php

if (!defined('IN_SCRIPT')) {
    exit;
}

if (!class_exists('Admin')) {
    require_once dirname(__DIR__, 3) . '/core/Admin.php';
}

Admin::requireModuleApiAuth();

use BackupPro\Repository\ScheduleRepository;

header('Content-Type: application/json; charset=utf-8');

$action = Tools::getValue('sub_action', 'list');

if ($action === 'save') {
    $id = (int)Tools::getValue('id', 0);
    $name = trim((string)Tools::getValue('name', 'Otomatik Yedek'));
    $backupType = trim((string)Tools::getValue('backup_type', 'full'));
    $cronExp = trim((string)Tools::getValue('cron_expression', '0 2 * * *'));
    $keepCount = (int)Tools::getValue('keep_count', 7);
    $active = (int)Tools::getValue('active', 1);

    $savedId = ScheduleRepository::save([
        'id' => $id,
        'name' => $name,
        'backup_type' => $backupType,
        'cron_expression' => $cronExp,
        'keep_count' => $keepCount,
        'active' => $active
    ]);

    if (ob_get_length()) { @ob_clean(); }
    echo json_encode(['success' => true, 'message' => 'Zamanlayıcı kaydedildi.', 'id' => $savedId]);
    exit;
} elseif ($action === 'delete') {
    $id = (int)Tools::getValue('id', 0);
    ScheduleRepository::delete($id);
    if (ob_get_length()) { @ob_clean(); }
    echo json_encode(['success' => true, 'message' => 'Zamanlayıcı silindi.']);
    exit;
}

$schedules = ScheduleRepository::getAll();
if (ob_get_length()) { @ob_clean(); }
echo json_encode(['success' => true, 'schedules' => $schedules]);
exit;
