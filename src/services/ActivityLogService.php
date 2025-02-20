<?php
declare(strict_types=1);

namespace App\Services;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

class ActivityLogService
{
    private PDO $db;
    private LoggerInterface $logger;

    public function __construct(PDO $db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * アクティビティを記録
     */
    public function log(
        int $userId,
        string $action,
        string $entityType,
        int $entityId,
        array $details = []
    ): void {
        try {
            $query = "
                INSERT INTO activity_logs (
                    user_id, action, entity_type, entity_id,
                    details, created_at
                ) VALUES (
                    :user_id, :action, :entity_type, :entity_id,
                    :details, :created_at
                )
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                'user_id' => $userId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (PDOException $e) {
            $this->logger->error('アクティビティログ記録エラー', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId
            ]);
        }
    }

    /**
     * ユーザーのアクティビティを取得
     */
    public function getUserActivities(int $userId, int $limit = 10): array
    {
        try {
            $query = "
                SELECT 
                    al.*,
                    u.name as user_name,
                    CASE 
                        WHEN al.entity_type = 'task' THEN t.title
                        WHEN al.entity_type = 'project' THEN p.name
                        ELSE NULL
                    END as entity_name
                FROM activity_logs al
                JOIN users u ON al.user_id = u.id
                LEFT JOIN tasks t ON al.entity_type = 'task' AND al.entity_id = t.id
                LEFT JOIN projects p ON al.entity_type = 'project' AND al.entity_id = p.id
                WHERE al.user_id = :user_id
                ORDER BY al.created_at DESC
                LIMIT :limit
            ";

            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error('アクティビティ取得エラー', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            throw $e;
        }
    }

    /**
     * プロジェクトのアクティビティを取得
     */
    public function getProjectActivities(int $projectId, int $limit = 10): array
    {
        try {
            $query = "
                SELECT 
                    al.*,
                    u.name as user_name,
                    CASE 
                        WHEN al.entity_type = 'task' THEN t.title
                        WHEN al.entity_type = 'project' THEN p.name
                        ELSE NULL
                    END as entity_name
                FROM activity_logs al
                JOIN users u ON al.user_id = u.id
                LEFT JOIN tasks t ON al.entity_type = 'task' AND al.entity_id = t.id
                LEFT JOIN projects p ON al.entity_type = 'project' AND al.entity_id = p.id
                WHERE (
                    (al.entity_type = 'project' AND al.entity_id = :project_id)
                    OR 
                    (al.entity_type = 'task' AND t.project_id = :project_id)
                )
                ORDER BY al.created_at DESC
                LIMIT :limit
            ";

            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':project_id', $projectId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error('プロジェクトアクティビティ取得エラー', [
                'error' => $e->getMessage(),
                'project_id' => $projectId
            ]);
            throw $e;
        }
    }

    /**
     * タスクのアクティビティを取得
     */
    public function getTaskActivities(int $taskId, int $limit = 10): array
    {
        try {
            $query = "
                SELECT 
                    al.*,
                    u.name as user_name
                FROM activity_logs al
                JOIN users u ON al.user_id = u.id
                WHERE al.entity_type = 'task'
                AND al.entity_id = :task_id
                ORDER BY al.created_at DESC
                LIMIT :limit
            ";

            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':task_id', $taskId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error('タスクアクティビティ取得エラー', [
                'error' => $e->getMessage(),
                'task_id' => $taskId
            ]);
            throw $e;
        }
    }

    /**
     * アクティビティの詳細を取得
     */
    public function getActivityDetails(int $activityId): ?array
    {
        try {
            $query = "
                SELECT 
                    al.*,
                    u.name as user_name,
                    CASE 
                        WHEN al.entity_type = 'task' THEN t.title
                        WHEN al.entity_type = 'project' THEN p.name
                        ELSE NULL
                    END as entity_name
                FROM activity_logs al
                JOIN users u ON al.user_id = u.id
                LEFT JOIN tasks t ON al.entity_type = 'task' AND al.entity_id = t.id
                LEFT JOIN projects p ON al.entity_type = 'project' AND al.entity_id = p.id
                WHERE al.id = :id
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute(['id' => $activityId]);

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            $this->logger->error('アクティビティ詳細取得エラー', [
                'error' => $e->getMessage(),
                'activity_id' => $activityId
            ]);
            throw $e;
        }
    }
}
