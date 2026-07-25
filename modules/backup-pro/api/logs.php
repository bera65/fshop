<?php

if (!defined('IN_SCRIPT')) {
    exit;
}

if (!class_exists('Admin')) {
    require_once dirname(__DIR__, 3) . '/core/Admin.php';
}

Admin::requireModuleApiAuth();

use BackupPro\Repository\LogRepository;

$export = Tools::getValue('export');

if ($export === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="backup_logs_' . date('Y-m-d') . '.csv"');
    $logs = LogRepository::getLogs(10000, 0);
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Backup ID', 'Level', 'Action', 'Message', 'Date']);
    foreach ($logs as $l) {
        fputcsv($out, [$l['id'], $l['backup_id'], $l['level'], $l['action'], $l['message'], $l['created_at']]);
    }
    fclose($out);
    exit;
} elseif ($export === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="backup_logs_' . date('Y-m-d') . '.json"');
    $logs = LogRepository::getLogs(10000, 0);
    echo json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
} elseif ($export === 'txt') {
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="backup_logs_' . date('Y-m-d') . '.txt"');
    $logs = LogRepository::getLogs(10000, 0);
    foreach ($logs as $l) {
        echo "[{$l['created_at']}] [{$l['level']}] [{$l['action']}] {$l['message']}\n";
    }
    exit;
}

header('Content-Type: application/json; charset=utf-8');
$limit = max(1, (int)Tools::getValue('limit', 50));
$logs = LogRepository::getLogs($limit, 0);

if (ob_get_length()) { @ob_clean(); }
echo json_encode([
    'success' => true,
    'logs' => $logs
]);
exit;
