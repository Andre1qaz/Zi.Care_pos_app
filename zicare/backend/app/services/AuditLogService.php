<?php

declare(strict_types=1);

namespace App\Services;

use Phalcon\Di\Di;

class AuditLogService
{
    public function getLogs(int $page = 1, int $limit = 50, array $filters = []): array
    {
        $db = Di::getDefault()->getShared('db');
        $offset = ($page - 1) * $limit;

        $where = ['1=1'];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = 'al.user_id = ?';
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['entity_type'])) {
            $where[] = 'al.entity_type = ?';
            $params[] = $filters['entity_type'];
        }

        if (!empty($filters['activity'])) {
            $where[] = 'al.activity LIKE ?';
            $params[] = '%' . $filters['activity'] . '%';
        }

        if (!empty($filters['start_date'])) {
            $where[] = 'al.created_at >= ?';
            $params[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $where[] = 'al.created_at <= ?';
            $params[] = $filters['end_date'];
        }

        $whereClause = implode(' AND ', $where);

        $logs = $db->query(
            "SELECT al.*, u.name as user_name, u.email as user_email
             FROM audit_logs al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE $whereClause
             ORDER BY al.created_at DESC
             LIMIT $limit OFFSET $offset",
            $params
        )->fetchAll();

        $count = $db->query(
            "SELECT COUNT(*) as total FROM audit_logs al WHERE $whereClause",
            $params
        )->fetch();

        return [
            'data' => $logs,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int) $count['total'],
                'pages' => ceil($count['total'] / $limit)
            ]
        ];
    }

    public function getLogById(int $id): ?array
    {
        $db = Di::getDefault()->getShared('db');

        $log = $db->query(
            "SELECT al.*, u.name as user_name, u.email as user_email
             FROM audit_logs al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE al.id = ?",
            [$id]
        )->fetch();

        if ($log && $log['metadata']) {
            $log['metadata'] = json_decode($log['metadata'], true);
        }

        return $log ?: null;
    }

    public function getStats(): array
    {
        $db = Di::getDefault()->getShared('db');

        $totalLogs = $db->query("SELECT COUNT(*) as total FROM audit_logs")->fetch();
        $todayLogs = $db->query("SELECT COUNT(*) as total FROM audit_logs WHERE DATE(created_at) = CURDATE()")->fetch();
        $topUsers = $db->query(
            "SELECT u.name, u.email, COUNT(al.id) as activity_count
             FROM audit_logs al
             JOIN users u ON u.id = al.user_id
             GROUP BY u.id, u.name, u.email
             ORDER BY activity_count DESC
             LIMIT 5"
        )->fetchAll();

        $activityBreakdown = $db->query(
            "SELECT activity, COUNT(*) as count
             FROM audit_logs
             GROUP BY activity
             ORDER BY count DESC
             LIMIT 10"
        )->fetchAll();

        return [
            'total_logs' => (int) $totalLogs['total'],
            'today_logs' => (int) $todayLogs['total'],
            'top_users' => $topUsers,
            'activity_breakdown' => $activityBreakdown
        ];
    }
}
