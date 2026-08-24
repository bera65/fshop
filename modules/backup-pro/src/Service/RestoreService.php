<?php

namespace BackupPro\Service;

use BackupPro\Repository\BackupRepository;
use DB;
use ZipArchive;

class RestoreService
{
    private const DUMP_ENTRY = 'database.sql';

    private string $rootPath;

    public function __construct()
    {
        $this->rootPath = StorageService::getProjectRoot();
    }

    public function getRootPath(): string
    {
        return $this->rootPath;
    }

    public function dryRunRestore(int $backupId): array
    {
        $backup = BackupRepository::findById($backupId);
        if (!$backup) {
            return ['success' => false, 'message' => 'Yedek bulunamadı.'];
        }

        $storage = new StorageService();
        $zipPath = $storage->resolveFilePath((string) $backup['file_path']);

        if (!$storage->isSafeBackupArchive($zipPath)) {
            return ['success' => false, 'message' => 'Yedek arşivi doğrulanamadı.'];
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['success' => false, 'message' => 'Yedek arşivi açılamadı.'];
        }

        $hasSqlDump = false;
        $sqlDumpSize = 0;
        $ignoredFiles = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = str_replace('\\', '/', (string) $zip->getNameIndex($i));
            $stat = $zip->statIndex($i);

            if ($name === self::DUMP_ENTRY) {
                $hasSqlDump = true;
                $sqlDumpSize += (int) ($stat['size'] ?? 0);
                continue;
            }

            if ($name !== '' && substr($name, -1) !== '/') {
                $ignoredFiles++;
            }
        }
        $zip->close();

        if (!$hasSqlDump) {
            return [
                'success' => false,
                'message' => 'Arşiv kökünde database.sql bulunamadı. Uygulama dosyaları geri yüklenmez.',
            ];
        }

        return [
            'success' => true,
            'backup_name' => $backup['backup_name'],
            'total_files_in_zip' => $ignoredFiles,
            'existing_conflicts' => 0,
            'has_database_dump' => true,
            'sql_dump_size' => $sqlDumpSize,
            'simulation_status' => 'Yalnızca arşiv kökündeki database.sql veritabanına uygulanır. ZIP içindeki uygulama dosyaları yazılmaz.',
        ];
    }

    /**
     * File restore into the live tree is disabled (zip-slip / RCE).
     * Kept so older callers that pass the project root cannot extract archive members.
     */
    public function restoreZipChunk(string $zipPath, int $offset, int $limit, string $extractTargetDir): int
    {
        unset($zipPath, $offset, $limit, $extractTargetDir);

        return 0;
    }

    public function extractVerifiedSqlDump(string $zipPath, string $stagingDir): ?string
    {
        $zipReal = realpath($zipPath);
        $stagingReal = realpath($stagingDir);

        if ($zipReal === false || $stagingReal === false) {
            return null;
        }

        if (!is_file($zipReal) || !is_dir($stagingReal)) {
            return null;
        }

        if (!StorageService::isApprovedRestoreStagingDir($stagingReal)) {
            return null;
        }

        $zip = new ZipArchive();
        if ($zip->open($zipReal) !== true) {
            return null;
        }

        $index = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = str_replace('\\', '/', (string) $zip->getNameIndex($i));
            if ($name === self::DUMP_ENTRY) {
                $index = $i;
                break;
            }
        }

        if ($index === null) {
            $zip->close();
            return null;
        }

        if ($this->isZipEntrySymlink($zip, $index)) {
            $zip->close();
            return null;
        }

        $contents = $zip->getFromIndex($index);
        $zip->close();

        if ($contents === false || trim($contents) === '') {
            return null;
        }

        $dest = $stagingReal . DIRECTORY_SEPARATOR . self::DUMP_ENTRY;
        if (file_put_contents($dest, $contents) === false) {
            return null;
        }

        $written = realpath($dest);
        if ($written === false || !StorageService::isPathInside($written, $stagingReal)) {
            @unlink($dest);
            return null;
        }

        if (basename($written) !== self::DUMP_ENTRY) {
            @unlink($dest);
            return null;
        }

        return $written;
    }

    public function executeSqlDumpChunk(string $sqlFilePath): bool
    {
        if (!$this->isVerifiedSqlDumpPath($sqlFilePath)) {
            return false;
        }

        $sql = file_get_contents($sqlFilePath);
        if ($sql === false || trim($sql) === '') {
            return false;
        }

        $queries = array_filter(array_map('trim', explode(";\n", $sql)));
        foreach ($queries as $query) {
            if ($query !== '' && strpos($query, '--') !== 0) {
                try {
                    DB::execute($query);
                } catch (\Throwable $e) {
                    // Ignore minor drop table errors during restore
                }
            }
        }

        return true;
    }

    public function isVerifiedSqlDumpPath(string $sqlFilePath): bool
    {
        $real = realpath($sqlFilePath);
        if ($real === false || !is_file($real) || basename($real) !== self::DUMP_ENTRY) {
            return false;
        }

        $stagingReal = realpath(dirname($real));
        if ($stagingReal === false || !StorageService::isApprovedRestoreStagingDir($stagingReal)) {
            return false;
        }

        if (!StorageService::isPathInside($real, $stagingReal)) {
            return false;
        }

        $expected = realpath($stagingReal . DIRECTORY_SEPARATOR . self::DUMP_ENTRY);

        return $expected !== false && StorageService::isPathInside($real, $expected) && StorageService::isPathInside($expected, $real);
    }

    private function isZipEntrySymlink(ZipArchive $zip, int $index): bool
    {
        if (!method_exists($zip, 'getExternalAttributesIndex')) {
            return false;
        }

        $opsys = 0;
        $attr = 0;

        if (!$zip->getExternalAttributesIndex($index, $opsys, $attr)) {
            return false;
        }

        $unix = defined('ZipArchive::OPSYS_UNIX') ? ZipArchive::OPSYS_UNIX : 3;

        if ((int) $opsys !== (int) $unix) {
            return false;
        }

        $mode = ($attr >> 16) & 0xFFFF;

        return ($mode & 0170000) === 0120000;
    }
}
