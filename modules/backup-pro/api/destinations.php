<?php

if (!defined('IN_SCRIPT')) {
    exit;
}

if (!class_exists('Admin')) {
    require_once dirname(__DIR__, 3) . '/core/Admin.php';
}

Admin::requireModuleApiAuth();

use BackupPro\Repository\DestinationRepository;

header('Content-Type: application/json; charset=utf-8');

$action = Tools::getValue('sub_action', 'list');

if ($action === 'save') {
    $id = (int)Tools::getValue('id', 0);
    $name = trim((string)Tools::getValue('name', 'Uzak Sunucu'));
    $driver = trim((string)Tools::getValue('driver', 'ftp'));
    $active = (int)Tools::getValue('active', 1);

    $config = [
        'host' => trim((string)Tools::getValue('host')),
        'port' => (int)Tools::getValue('port', 21),
        'user' => trim((string)Tools::getValue('user')),
        'password' => trim((string)Tools::getValue('password')),
        'remote_path' => trim((string)Tools::getValue('remote_path', '/backups')),
    ];

    $savedId = DestinationRepository::save([
        'id' => $id,
        'name' => $name,
        'driver' => $driver,
        'config' => json_encode($config),
        'active' => $active
    ]);

    if (ob_get_length()) { @ob_clean(); }
    echo json_encode(['success' => true, 'message' => 'Depolama sürücüsü kaydedildi.', 'id' => $savedId]);
    exit;
} elseif ($action === 'delete') {
    $id = (int)Tools::getValue('id', 0);
    DestinationRepository::delete($id);
    if (ob_get_length()) { @ob_clean(); }
    echo json_encode(['success' => true, 'message' => 'Depolama sürücüsü silindi.']);
    exit;
}

$destinations = DestinationRepository::getAll();
if (ob_get_length()) { @ob_clean(); }
echo json_encode(['success' => true, 'destinations' => $destinations]);
exit;
