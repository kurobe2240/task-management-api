<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;
use App\Exceptions\ValidationException;

class TaskRepository extends AbstractRepository
{
    protected string $table = 'tasks';

    /**
     * タスクをプロジェクトIDで検索
     */
    public function findByProjectId(int $projectId, array $orderBy = null): array
    {
        return $this->findBy(['project_id' => $projectId], $orderBy);
    }

    /**
     * タスクを担当者IDで検索
     */
    public function findByAssigneeId(int $userId, array $orderBy = null): array
    {
        return $this->findBy(['assignee_id' => $userId], $orderBy);
    }

    /**
     * プロジェクト内のタスクを状態で検索
     */
    public function findByProjectAndStatus(int $projectId, string $status, array $orderBy = null): array
    {
        return $this->findBy([
            'project_id' => $projectId,
            'status' => $status
        ], $orderBy);
    }

    /**
     * キーワードでタスクを検索
     */
    public function searchByKeyword(string $keyword, ?int $projectId = null): array
    {
        try {
            $query = "
                SELECT * FROM {$this->table}
                WHERE (title LIKE :keyword OR description LIKE :keyword)
                AND deleted_at IS NULL
            ";
            $params = ['keyword' => "%{$keyword}%"];

            if ($projectId) {
                $query .= " AND project_id = :project_id";
                $params['project_id'] = $projectId;
            }

            return $this->fetchQuery($query, $params);
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'method' => 'searchByKeyword',
                'keyword' => $keyword
            ]);
            throw $e;
        }
    }

    /**
     * 高度な条件検索
     */
    public function advancedSearch(array $criteria): array
    {
        try {
            $query = "SELECT * FROM {$this->table} WHERE deleted_at IS NULL";
            $params = [];

            if (!empty($criteria['title'])) {
                $query .= " AND title LIKE :title";
                $params['title'] = "%{$criteria['title']}%";
            }

            if (!empty($criteria['status'])) {
                $query .= " AND status = :status";
                $params['status'] = $criteria['status'];
            }

            if (!empty($criteria['priority'])) {
                $query .= " AND priority = :priority";
                $params['priority'] = $criteria['priority'];
            }

            if (!empty($criteria['start_date'])) {
                $query .= " AND created_at >= :start_date";
                $params['start_date'] = $criteria['start_date'];
            }

            if (!empty($criteria['end_date'])) {
                $query .= " AND created_at <= :end_date";
                $params['end_date'] = $criteria['end_date'];
            }

            if (isset($criteria['progress_min'])) {
                $query .= " AND progress >= :progress_min";
                $params['progress_min'] = $criteria['progress_min'];
            }

            if (isset($criteria['progress_max'])) {
                $query .= " AND progress <= :progress_max";
                $params['progress_max'] = $criteria['progress_max'];
            }

            return $this->fetchQuery($query, $params);
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'method' => 'advancedSearch',
                'criteria' => $criteria
            ]);
            throw $e;
        }
    }

    /**
     * タスクの依存関係を追加
     */
    public function addDependency(int $taskId, int $dependsOnTaskId): bool
    {
        try {
            // 循環依存のチェック
            if ($this->hasCyclicDependency($taskId, $dependsOnTaskId)) {
                throw new ValidationException([
                    'dependency' => '循環依存は許可されていません。'
                ]);
            }

            $query = "
                INSERT INTO task_dependencies (task_id, depends_on_task_id, created_at)
                VALUES (:task_id, :depends_on_task_id, :created_at)
            ";
            return $this->executeQuery($query, [
                'task_id' => $taskId,
                'depends_on_task_id' => $dependsOnTaskId,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'method' => 'addDependency',
                'task_id' => $taskId,
                'depends_on_task_id' => $dependsOnTaskId
            ]);
            throw $e;
        }
    }

    /**
     * タスクの依存関係を削除
     */
    public function removeDependency(int $taskId, int $dependsOnTaskId): bool
    {
        try {
            $query = "
                DELETE FROM task_dependencies
                WHERE task_id = :task_id 
                AND depends_on_task_id = :depends_on_task_id
            ";
            return $this->executeQuery($query, [
                'task_id' => $taskId,
                'depends_on_task_id' => $dependsOnTaskId
            ]);
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'method' => 'removeDependency',
                'task_id' => $taskId,
                'depends_on_task_id' => $dependsOnTaskId
            ]);
            throw $e;
        }
    }

    /**
     * タスクの依存関係を取得
     */
    public function getDependencies(int $taskId): array
    {
        try {
            $query = "
                SELECT t.* FROM tasks t
                JOIN task_dependencies td ON t.id = td.depends_on_task_id
                WHERE td.task_id = :task_id
                AND t.deleted_at IS NULL
            ";
            return $this->fetchQuery($query, ['task_id' => $taskId]);
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'method' => 'getDependencies',
                'task_id' => $taskId
            ]);
            throw $e;
        }
    }

    /**
     * 循環依存をチェック
     */
    private function hasCyclicDependency(int $taskId, int $dependsOnTaskId): bool
    {
        $visited = [];
        $stack = [$dependsOnTaskId];

        while (!empty($stack)) {
            $currentTaskId = array_pop($stack);
            if ($currentTaskId === $taskId) {
                return true;
            }

            if (!isset($visited[$currentTaskId])) {
                $visited[$currentTaskId] = true;
                $dependencies = $this->getDependencies($currentTaskId);
                foreach ($dependencies as $dependency) {
                    $stack[] = $dependency['id'];
                }
            }
        }

        return false;
    }

    /**
     * タスクコメントを追加
     */
    public function addComment(int $taskId, int $userId, string $comment): int
    {
        try {
            $query = "
                INSERT INTO task_comments (task_id, user_id, comment, created_at)
                VALUES (:task_id, :user_id, :comment, :created_at)
            ";
            $this->executeQuery($query, [
                'task_id' => $taskId,
                'user_id' => $userId,
                'comment' => $comment,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'method' => 'addComment',
                'task_id' => $taskId,
                'user_id' => $userId
            ]);
            throw $e;
        }
    }

    /**
     * タスクコメントを取得
     */
    public function getComments(int $taskId): array
    {
        try {
            $query = "
                SELECT tc.*, u.name as user_name 
                FROM task_comments tc
                JOIN users u ON tc.user_id = u.id
                WHERE tc.task_id = :task_id
                ORDER BY tc.created_at DESC
            ";
            return $this->fetchQuery($query, ['task_id' => $taskId]);
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'method' => 'getComments',
                'task_id' => $taskId
            ]);
            throw $e;
        }
    }

    /**
     * 担当者の実績データを取得
     */
    public function getAssigneePerformance(int $userId): array
    {
        try {
            $query = "
                SELECT 
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    AVG(CASE 
                        WHEN status = 'completed' AND due_date >= completed_at THEN 1
                        WHEN status = 'completed' THEN 0
                    END) * 100 as on_time_completion_rate,
                    AVG(TIMESTAMPDIFF(DAY, created_at, 
                        CASE WHEN completed_at IS NOT NULL THEN completed_at 
                        ELSE CURRENT_TIMESTAMP END)) as avg_completion_days
                FROM {$this->table}
                WHERE assignee_id = :user_id
                AND deleted_at IS NULL
            ";
            return $this->fetchQuery($query, ['user_id' => $userId])[0];
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'method' => 'getAssigneePerformance',
                'user_id' => $userId
            ]);
            throw $e;
        }
    }

    /**
     * タスクを作成
     */
    public function create(array $data): int
    {
        $this->validateTaskData($data);
        return parent::create($data);
    }

    /**
     * タスクを更新
     */
    public function update(int $id, array $data): bool
    {
        $this->validateTaskData($data, false);
        return parent::update($id, $data);
    }

    /**
     * タスクの状態を更新
     */
    public function updateStatus(int $id, string $status): bool
    {
        $this->validateStatus($status);
        return $this->update($id, ['status' => $status]);
    }

    /**
     * タスクの担当者を更新
     */
    public function updateAssignee(int $id, ?int $assigneeId): bool
    {
        return $this->update($id, ['assignee_id' => $assigneeId]);
    }

    /**
     * タスクの優先度を更新
     */
    public function updatePriority(int $id, string $priority): bool
    {
        $this->validatePriority($priority);
        return $this->update($id, ['priority' => $priority]);
    }

    /**
     * タスクの進捗率を更新
     */
    public function updateProgress(int $id, int $progress): bool
    {
        if ($progress < 0 || $progress > 100) {
            throw new ValidationException([
                'progress' => '進捗率は0から100の間である必要があります。'
            ]);
        }
        return $this->update($id, ['progress' => $progress]);
    }

    /**
     * 期限切れのタスクを取得
     */
    public function findOverdueTasks(): array
    {
        try {
            $query = "
                SELECT * FROM {$this->table}
                WHERE due_date < CURRENT_DATE
                AND status != 'completed'
                AND deleted_at IS NULL
            ";
            return $this->fetchQuery($query);
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'method' => 'findOverdueTasks'
            ]);
            throw $e;
        }
    }

    /**
     * 期限が近いタスクを取得
     */
    public function findUpcomingTasks(int $days = 7): array
    {
        try {
            $query = "
                SELECT * FROM {$this->table}
                WHERE due_date BETWEEN CURRENT_DATE AND DATE_ADD(CURRENT_DATE, INTERVAL :days DAY)
                AND status != 'completed'
                AND deleted_at IS NULL
                ORDER BY due_date ASC
            ";
            return $this->fetchQuery($query, ['days' => $days]);
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'method' => 'findUpcomingTasks',
                'days' => $days
            ]);
            throw $e;
        }
    }

    /**
     * プロジェクトの進捗状況を取得
     */
    public function getProjectProgress(int $projectId): array
    {
        try {
            $query = "
                SELECT 
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    AVG(progress) as average_progress
                FROM {$this->table}
                WHERE project_id = :project_id
                AND deleted_at IS NULL
            ";
            return $this->fetchQuery($query, ['project_id' => $projectId])[0];
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'method' => 'getProjectProgress',
                'project_id' => $projectId
            ]);
            throw $e;
        }
    }

    /**
     * タスクデータを検証
     */
    private function validateTaskData(array $data, bool $isCreate = true): void
    {
        $errors = [];

        // 必須フィールドの検証（新規作成時のみ）
        if ($isCreate) {
            $requiredFields = ['title', 'project_id'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $errors[$field] = sprintf('%sは必須です。', $field);
                }
            }
        }

        // タイトルの長さを検証
        if (isset($data['title'])) {
            if (mb_strlen($data['title']) > 100) {
                $errors['title'] = 'タイトルは100文字以内である必要があります。';
            }
        }

        // 状態の検証
        if (isset($data['status'])) {
            $this->validateStatus($data['status']);
        }

        // 優先度の検証
        if (isset($data['priority'])) {
            $this->validatePriority($data['priority']);
        }

        // 進捗率の検証
        if (isset($data['progress'])) {
            if ($data['progress'] < 0 || $data['progress'] > 100) {
                $errors['progress'] = '進捗率は0から100の間である必要があります。';
            }
        }

        // 期限日の検証
        if (isset($data['due_date'])) {
            if (!strtotime($data['due_date'])) {
                $errors['due_date'] = '有効な日付形式である必要があります。';
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    /**
     * タスクの状態を検証
     */
    private function validateStatus(string $status): void
    {
        $validStatuses = ['not_started', 'in_progress', 'on_hold', 'completed', 'cancelled'];
        
        if (!in_array($status, $validStatuses)) {
            throw new ValidationException([
                'status' => '無効なステータスです。'
            ]);
        }
    }

    /**
     * タスクの優先度を検証
     */
    private function validatePriority(string $priority): void
    {
        $validPriorities = ['low', 'medium', 'high', 'urgent'];
        
        if (!in_array($priority, $validPriorities)) {
            throw new ValidationException([
                'priority' => '無効な優先度です。'
            ]);
        }
    }

    /**
     * タスクの統計情報を取得
     */
    public function getTaskStatistics(int $userId): array
    {
        try {
            $query = "
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE 
                        WHEN status != 'completed' 
                        AND due_date < CURRENT_DATE 
                        THEN 1 ELSE 0 END
                    ) as overdue
                FROM {$this->table}
                WHERE (created_by = :user_id OR assigned_to = :user_id)
                AND deleted_at IS NULL
            ";
            return $this->fetchQuery($query, ['user_id' => $userId])[0];
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'method' => 'getTaskStatistics',
                'user_id' => $userId
            ]);
            throw $e;
        }
    }

    /**
     * 最近のタスクアクティビティを取得
     */
    public function getRecentActivities(int $userId, int $limit = 5): array
    {
        try {
            $query = "
                SELECT 
                    'task' as type,
                    t.id,
                    t.title,
                    t.status,
                    t.updated_at as created_at,
                    CASE 
                        WHEN t.status = 'completed' THEN 'タスクが完了しました'
                        WHEN t.status = 'in_progress' THEN 'タスクが進行中になりました'
                        ELSE 'タスクが更新されました'
                    END as description
                FROM {$this->table} t
                WHERE (t.created_by = :user_id OR t.assigned_to = :user_id)
                AND t.deleted_at IS NULL
                ORDER BY t.updated_at DESC
                LIMIT :limit
            ";
            return $this->fetchQuery($query, [
                'user_id' => $userId,
                'limit' => $limit
            ]);
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'method' => 'getRecentActivities',
                'user_id' => $userId
            ]);
            throw $e;
        }
    }
}
