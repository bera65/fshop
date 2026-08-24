<?php

namespace BackupPro\Repository;

use DB;

class BackupRepository
{
    public static function save(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        if (isset($data['id']) && (int)$data['id'] > 0) {
            $id = (int)$data['id'];
            unset($data['id']);
            $data['updated_at'] = $now;
            DB::update('backup_pro_backups', $data, 'id = :id', ['id' => $id]);
            return $id;
        } else {
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
            return (int) DB::insert('backup_pro_backups', $data);
        }
    }

    public static function findById(int $id): ?array
    {
        return DB::getRowSafe('backup_pro_backups', 'id = ?', [$id]) ?: null;
    }

    public static function findByName(string $name): ?array
    {
        return DB::getRowSafe('backup_pro_backups', 'backup_name = ?', [$name]) ?: null;
    }

    public static function getAll(int $limit = 20, int $offset = 0, ?string $search = null, ?string $type = null, string $sort = 'id DESC'): array
    {
        $where = ["1=1"];
        $params = [];

        if ($type !== null && $type !== '') {
            $where[] = "`type` = ?";
            $params[] = $type;
        }

        if ($search !== null && $search !== '') {
            $where[] = "(`backup_name` LIKE ? OR `file_path` LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $whereClause = implode(' AND ', $where);
        $sortSql = self::sanitizeSort($sort);
        $sql = "SELECT * FROM `backup_pro_backups` WHERE {$whereClause} ORDER BY {$sortSql} LIMIT {$limit} OFFSET {$offset}";
        return DB::execute($sql, $params) ?: [];
    }

    private static function sanitizeSort(string $sort): string
    {
        $sort = trim($sort);
        $allowed = [
            'id ASC',
            'id DESC',
            'created_at ASC',
            'created_at DESC',
            'updated_at ASC',
            'updated_at DESC',
            'file_size ASC',
            'file_size DESC',
            'backup_name ASC',
            'backup_name DESC',
            'type ASC',
            'type DESC',
        ];

        foreach ($allowed as $ok) {
            if (strcasecmp($sort, $ok) === 0) {
                return $ok;
            }
        }

        return 'id DESC';
    }

    public static function countAll(?string $search = null, ?string $type = null): int
    {
        $where = ["1=1"];
        $params = [];

        if ($type !== null && $type !== '') {
            $where[] = "`type` = ?";
            $params[] = $type;
        }

        if ($search !== null && $search !== '') {
            $where[] = "(`backup_name` LIKE ? OR `file_path` LIKE ?)";
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $whereClause = implode(' AND ', $where);
        return (int) DB::getValue("SELECT COUNT(*) FROM `backup_pro_backups` WHERE {$whereClause}", $params);
    }

    public static function delete(int $id): bool
    {
        QueueRepository::deleteByBackupId($id);
        return DB::execute("DELETE FROM `backup_pro_backups` WHERE `id` = ?", [$id]) !== false;
    }

    public static function getStats(): array
    {
        $total = (int) DB::getValue("SELECT COUNT(*) FROM `backup_pro_backups`");
        $successful = (int) DB::getValue("SELECT COUNT(*) FROM `backup_pro_backups` WHERE status = 'completed'");
        $failed = (int) DB::getValue("SELECT COUNT(*) FROM `backup_pro_backups` WHERE status = 'failed'");
        $inProgress = (int) DB::getValue("SELECT COUNT(*) FROM `backup_pro_backups` WHERE status = 'in_progress'");
        
        $totalBytes = (float) DB::getValue("SELECT SUM(file_size) FROM `backup_pro_backups` WHERE status = 'completed'");
        $avgDuration = (float) DB::getValue("SELECT AVG(duration_seconds) FROM `backup_pro_backups` WHERE status = 'completed'");

        $largest = DB::getRowSafe('backup_pro_backups', "status = 'completed'", [], 'file_size DESC');
        $smallest = DB::getRowSafe('backup_pro_backups', "status = 'completed'", [], 'file_size ASC');

        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'in_progress' => $inProgress,
            'total_bytes' => $totalBytes,
            'avg_duration' => round($avgDuration, 1),
            'largest' => $largest,
            'smallest' => $smallest
        ];
    }
}
