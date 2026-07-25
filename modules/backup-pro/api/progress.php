<?php

if (!defined('IN_SCRIPT')) {
    exit;
}

if (!class_exists('Admin')) {
    require_once dirname(__DIR__, 3) . '/core/Admin.php';
}

Admin::requireModuleApiAuth();

use BackupPro\Service\QueueService;
use BackupPro\Service\StorageService;
use BackupPro\Repository\QueueRepository;
use BackupPro\Repository\BackupRepository;

header('Content-Type: application/json; charset=utf-8');

$backupId = (int)Tools::getValue('backup_id', 0);

// If no backup_id given, find the most recent in_progress backup
if ($backupId <= 0) {
    $rows = \DB::execute("SELECT id FROM `backup_pro_backups` WHERE `status` = 'in_progress' ORDER BY id DESC LIMIT 1");
    $backupId = ($rows && isset($rows[0])) ? (int)$rows[0]['id'] : 0;
}

if ($backupId <= 0) {
    if (ob_get_length()) { @ob_clean(); }
    echo json_encode(['success' => true, 'is_running' => false, 'pct' => 0, 'remaining' => 0, 'status' => 'idle']);
    exit;
}

$backup = BackupRepository::findById($backupId);

if (!$backup) {
    if (ob_get_length()) { @ob_clean(); }
    echo json_encode(['success' => false, 'message' => 'Yedek bulunamadı']);
    exit;
}

// If already completed or failed, return that immediately
if (in_array($backup['status'], ['completed', 'failed'])) {
    $pct = $backup['status'] === 'completed' ? 100 : 0;
    if (ob_get_length()) { @ob_clean(); }
    echo json_encode([
        'success'    => true,
        'is_running' => false,
        'pct'        => $pct,
        'remaining'  => 0,
        'status'     => $backup['status'],
        'backup_id'  => $backupId,
    ]);
    exit;
}

// Check for stuck backups: in_progress with no queue jobs for this specific backup
$pendingForBackup = QueueRepository::countPendingByBackupId($backupId);
if ($pendingForBackup === 0 && $backup['status'] === 'in_progress') {
    // Stuck — mark as failed
    \DB::update('backup_pro_backups', [
        'status'        => 'failed',
        'error_message' => 'Yedekleme işlemi takıldı veya bir hata oluştu. Kuyruk beklenmedik şekilde boşaldı.',
        'updated_at'    => date('Y-m-d H:i:s'),
    ], 'id = :id', ['id' => $backupId]);

    if (ob_get_length()) { @ob_clean(); }
    echo json_encode([
        'success'    => true,
        'is_running' => false,
        'pct'        => 0,
        'remaining'  => 0,
        'status'     => 'failed',
        'backup_id'  => $backupId,
        'error'      => 'Yedekleme işlemi takıldı.',
    ]);
    exit;
}

// Kilitli kalan işleri kurtar (çökme sonrası)
QueueRepository::recoverLockedJobsForBackup($backupId);

// Process multiple batches within a single request (max 25 saniye)
// Bu sayede her 15s'de bir çağrı yerine, her çağrıda mümkün olduğu kadar çok iş yapılır
$queueService = new QueueService();
$loopStart    = microtime(true);
$maxSeconds   = 4;   // 4 sn çalış → cevap ver → tarayıcı anında günceller
$batchCount   = 0;

while (true) {
    $elapsed = microtime(true) - $loopStart;
    if ($elapsed >= $maxSeconds) break;

    // İşlenecek iş kalmadı mı kontrol et
    $pending = QueueRepository::countPendingByBackupId($backupId);
    if ($pending === 0) break;

    $batchResult = $queueService->processBatch();
    $batchCount++;

    // Tamamlandı mı ya da hata var mı?
    $backup = BackupRepository::findById($backupId);
    if (!$backup || in_array($backup['status'], ['completed', 'failed'])) break;

    // Kuyruğa iş eklendi mi kontrol et (ör. finalize adımı)
    // kısa bir bekleme ile CPU'yu rahatlatma
    usleep(50000); // 50ms
}

// Son durumu oku
$backup   = BackupRepository::findById($backupId);
$progress = QueueRepository::getProgress($backupId);
$pending  = $progress['pending'];

$isRunning = $backup && $backup['status'] === 'in_progress';
$pct       = max(5, min(99, (float)$progress['current_pct']));

if ($backup && $backup['status'] === 'completed') {
    $pct       = 100;
    $isRunning = false;
} elseif ($backup && $backup['status'] === 'failed') {
    $isRunning = false;
    $pct       = 0;
}

// Dosya boyutunu hesapla
$currentSizeFormatted = '0 B';
if ($backup && !empty($backup['file_path'])) {
    $storageService = new StorageService();
    $filePath = $storageService->resolveFilePath($backup['file_path']);
    if (file_exists($filePath)) {
        $currentSizeFormatted = \BackupPro\Core\Settings::formatBytes(filesize($filePath));
    }
}

if (ob_get_length()) { @ob_clean(); }
echo json_encode([
    'success'        => true,
    'is_running'     => $isRunning,
    'pct'            => $pct,
    'remaining'      => $pending,
    'status'         => $backup['status'] ?? 'unknown',
    'backup_id'      => $backupId,
    'batches_done'   => $batchCount,
    'elapsed_ms'     => round((microtime(true) - $loopStart) * 1000),
    'formatted_size' => $currentSizeFormatted,
]);
exit;
