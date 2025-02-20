<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;
use App\Exceptions\ValidationException;

class ProjectRepository extends AbstractRepository
{
    protected string $table = 'projects';

    /**
     * プロジェクトをユーザーIDで検索
     */
    public function findByUserId(int $userId, array $orderBy = null): array
    {
        try {
            $query = "
                SELECT p.* 
                FROM {$this->table} p
                JOIN project_members pm ON p.id = pm.project_id
                WHERE pm.user_id = :user_id
                AND p.deleted_at IS NULL
                " . ($orderBy ? "ORDER BY " . implode(', ', array_map(
                    fn($k, $v) => "$k $v",
                    array_keys($orderBy),
                    $orderBy
                )) : "");

            return $this->fetchQuery($query, ['user_id' => $userId]);
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'method' => 'findByUserId',
                'user_id' => $userId
            ]);
            throw $e;
        }
    }

    /**
     * プロジェクトメンバーを取得
     */
    public function getProjectMembers(int $projectId): array
    {
        try {
            $query = "
                SELECT u.* 
                FROM users u
                JOIN project_members pm ON u.id = pm.user_id
                WHERE pm.project_id = :project_id
                AND u.deleted_at IS NULL
            ";
            return $this->fetchQuery($query, ['project_id' => $projectId]);
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'method' => 'getProjectMembers',
                'project_id' => $projectId
            ]);
            throw $e;
        }
    }

    /**
     * プロジェクトメンバーを追加
     */
    public function addMember(int $projectId, int $userId, string $role = 'member'): bool
    {
        try {
            // メンバーの重複チェック
            $query = "
                SELECT COUNT(*) 
                FROM project_members 
                WHERE project_id = :project_id 
                AND user_id = :user_id
            ";
            $count = $this->db->prepare($query);
            $count->execute([
                'project_id' => $projectId,
                'user_id' => $userId
            ]);

            if ($count->fetchColumn() > 0) {
                throw new ValidationException([
                    'member' => 'このユーザーは既にプロジェクトメンバーです。'
                ]);
            }

            // メンバーを追加
            $query = "
                INSERT INTO project_members (project_id, user_id, role, created_at)
                VALUES (:project_id, :user_id, :role, :created_at)
            ";
            return $this->executeQuery($query, [
                'project_id' => $projectId,
                'user_id' => $userId,
                'role' => $role,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'method' => 'addMember',
                'project_id' => $projectId,
                'user_id' => $userId
            ]);
            throw $e;
        }
    }

    /**
     * プロジェクトメンバーを削除
     */
    public function removeMember(int $projectId, int $userId): bool
    {
        try {
            $query = "
                DELETE FROM project_members 
                WHERE project_id = :project_id 
                AND user_id = :user_id
            ";
            return $this->executeQuery($query, [
                'project_id' => $projectId,
                'user_id' => $userId
            ]);
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'method' => 'removeMember',
                'project_id' => $projectId,
                'user_id' => $userId
            ]);
            throw $e;
        }
    }

    /**
     * メンバーの役割を更新
     */
    public function updateMemberRole(int $projectId, int $userId, string $role): bool
    {
        try {
            $query = "
                UPDATE project_members 
                SET role = :role
                WHERE project_id = :project_id 
                AND user_id = :user_id
            ";
            return $this->executeQuery($query, [
                'project_id' => $projectId,
                'user_id' => $userId,
                'role' => $role
            ]);
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'method' => 'updateMemberRole',
                'project_id' => $projectId,
                'user_id' => $userId,
                'role' => $role
            ]);
            throw $e;
        }
    }

    /**
     * プロジェクトの進捗状況を取得
     */
    public function getProgress(int $projectId): array
    {
        try {
            $query = "
                SELECT 
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    AVG(t.progress) as average_progress,
                    MIN(t.due_date) as next_due_date
                FROM tasks t
                WHERE t.project_id = :project_id
                AND t.deleted_at IS NULL
            ";
            $result = $this->fetchQuery($query, ['project_id' => $projectId])[0];

            // 進捗率を計算
            $result['progress_rate'] = $result['total_tasks'] > 0
                ? ($result['completed_tasks'] / $result['total_tasks']) * 100
                : 0;

            return $result;
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'method' => 'getProgress',
                'project_id' => $projectId
            ]);
            throw $e;
        }
    }

    /**
     * プロジェクトの状態を更新
     */
    public function updateStatus(int $id, string $status): bool
    {
        $this->validateStatus($status);
        return $this->update($id, ['status' => $status]);
    }

    /**
     * プロジェクトを作成
     */
    public function create(array $data): int
    {
        $this->validateProjectData($data);
        return parent::create($data);
    }

    /**
     * プロジェクトを更新
     */
    public function update(int $id, array $data): bool
    {
        $this->validateProjectData($data, false);
        return parent::update($id, $data);
    }

    /**
     * プロジェクトをテンプレートとして保存
     */
    public function saveAsTemplate(int $projectId, string $templateName): int
    {
        try {
            $this->beginTransaction();

            // プロジェクトデータを取得
            $project = $this->find($projectId);
            if (!$project) {
                throw new ValidationException(['project' => 'プロジェクトが見つかりません。']);
            }

            // テンプレートとして保存
            $templateData = [
                'name' => $templateName,
                'description' => $project['description'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $query = "
                INSERT INTO project_templates (name, description, created_at)
                VALUES (:name, :description, :created_at)
            ";
            $this->executeQuery($query, $templateData);
            $templateId = (int)$this->db->lastInsertId();

            // タスクテンプレートを保存
            $query = "
                INSERT INTO task_templates (
                    template_id, title, description, estimated_hours, priority
                )
                SELECT 
                    :template_id, title, description, estimated_hours, priority
                FROM tasks
                WHERE project_id = :project_id
                AND deleted_at IS NULL
            ";
            $this->executeQuery($query, [
                'template_id' => $templateId,
                'project_id' => $projectId
            ]);

            $this->commit();
            return $templateId;
        } catch (PDOException $e) {
            $this->rollback();
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'method' => 'saveAsTemplate',
                'project_id' => $projectId
            ]);
            throw $e;
        }
    }

    /**
     * テンプレートからプロジェクトを作成
     */
    public function createFromTemplate(int $templateId, array $data): int
    {
        try {
            $this->beginTransaction();

            // プロジェクトを作成
            $projectId = $this->create($data);

            // テンプレートのタスクを複製
            $query = "
                INSERT INTO tasks (
                    project_id, title, description, estimated_hours, 
                    priority, status, created_at, updated_at
                )
                SELECT 
                    :project_id, title, description, estimated_hours,
                    priority, 'not_started', :created_at, :updated_at
                FROM task_templates
                WHERE template_id = :template_id
            ";
            $now = date('Y-m-d H:i:s');
            $this->executeQuery($query, [
                'project_id' => $projectId,
                'template_id' => $templateId,
                'created_at' => $now,
                'updated_at' => $now
            ]);

            $this->commit();
            return $projectId;
        } catch (PDOException $e) {
            $this->rollback();
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'method' => 'createFromTemplate',
                'template_id' => $templateId
            ]);
            throw $e;
        }
    }

    /**
     * プロジェクトのマイルストーンを作成
     */
    public function createMilestone(int $projectId, array $data): int
    {
        try {
            $query = "
                INSERT INTO project_milestones (
                    project_id, title, description, due_date, created_at
                ) VALUES (
                    :project_id, :title, :description, :due_date, :created_at
                )
            ";
            $this->executeQuery($query, [
                'project_id' => $projectId,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'due_date' => $data['due_date'],
                'created_at' => date('Y-m-d H:i:s')
            ]);

            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'method' => 'createMilestone',
                'project_id' => $projectId
            ]);
            throw $e;
        }
    }

    /**
     * プロジェクトのマイルストーンを取得
     */
    public function getMilestones(int $projectId): array
    {
        try {
            $query = "
                SELECT * FROM project_milestones
                WHERE project_id = :project_id
                ORDER BY due_date ASC
            ";
            return $this->fetchQuery($query, ['project_id' => $projectId]);
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'method' => 'getMilestones',
                'project_id' => $projectId
            ]);
            throw $e;
        }
    }

    /**
     * プロジェクトの予算を更新
     */
    public function updateBudget(int $projectId, array $data): bool
    {
        try {
            $query = "
                UPDATE project_budgets
                SET 
                    amount = :amount,
                    currency = :currency,
                    updated_at = :updated_at
                WHERE project_id = :project_id
            ";
            return $this->executeQuery($query, [
                'project_id' => $projectId,
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'method' => 'updateBudget',
                'project_id' => $projectId
            ]);
            throw $e;
        }
    }

    /**
     * プロジェクトの予算情報を取得
     */
    public function getBudgetInfo(int $projectId): array
    {
        try {
            $query = "
                SELECT 
                    pb.amount as total_budget,
                    pb.currency,
                    SUM(pe.amount) as total_expenses,
                    (pb.amount - COALESCE(SUM(pe.amount), 0)) as remaining_budget
                FROM project_budgets pb
                LEFT JOIN project_expenses pe ON pb.project_id = pe.project_id
                WHERE pb.project_id = :project_id
                GROUP BY pb.project_id, pb.amount, pb.currency
            ";
            return $this->fetchQuery($query, ['project_id' => $projectId])[0];
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'method' => 'getBudgetInfo',
                'project_id' => $projectId
            ]);
            throw $e;
        }
    }

    /**
     * プロジェクトの経費を記録
     */
    public function recordExpense(int $projectId, array $data): int
    {
        try {
            $query = "
                INSERT INTO project_expenses (
                    project_id, description, amount, date, category,
                    recorded_by, created_at
                ) VALUES (
                    :project_id, :description, :amount, :date, :category,
                    :recorded_by, :created_at
                )
            ";
            $this->executeQuery($query, [
                'project_id' => $projectId,
                'description' => $data['description'],
                'amount' => $data['amount'],
                'date' => $data['date'],
                'category' => $data['category'],
                'recorded_by' => $data['user_id'],
                'created_at' => date('Y-m-d H:i:s')
            ]);

            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'method' => 'recordExpense',
                'project_id' => $projectId
            ]);
            throw $e;
        }
    }

    /**
     * プロジェクトの活動ログを記録
     */
    public function logActivity(int $projectId, string $action, array $data = []): int
    {
        try {
            $query = "
                INSERT INTO project_activities (
                    project_id, action, data, user_id, created_at
                ) VALUES (
                    :project_id, :action, :data, :user_id, :created_at
                )
            ";
            $this->executeQuery($query, [
                'project_id' => $projectId,
                'action' => $action,
                'data' => json_encode($data),
                'user_id' => $data['user_id'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'method' => 'logActivity',
                'project_id' => $projectId,
                'action' => $action
            ]);
            throw $e;
        }
    }

    /**
     * プロジェクトの活動ログを取得
     */
    public function getActivityLog(int $projectId, ?int $limit = null): array
    {
        try {
            $query = "
                SELECT 
                    pa.*,
                    u.name as user_name
                FROM project_activities pa
                LEFT JOIN users u ON pa.user_id = u.id
                WHERE pa.project_id = :project_id
                ORDER BY pa.created_at DESC
            ";
            if ($limit) {
                $query .= " LIMIT :limit";
            }

            $params = ['project_id' => $projectId];
            if ($limit) {
                $params['limit'] = $limit;
            }

            return $this->fetchQuery($query, $params);
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'method' => 'getActivityLog',
                'project_id' => $projectId
            ]);
            throw $e;
        }
    }

    /**
     * プロジェクトを複製
     */
    public function cloneProject(int $projectId, array $newData): int
    {
        try {
            $this->beginTransaction();

            // 元のプロジェクトデータを取得
            $project = $this->find($projectId);
            if (!$project) {
                throw new ValidationException(['project' => 'プロジェクトが見つかりません。']);
            }

            // 新しいプロジェクトを作成
            $projectData = array_merge($project, $newData);
            unset($projectData['id']); // IDは自動生成
            $projectData['created_at'] = date('Y-m-d H:i:s');
            $projectData['updated_at'] = date('Y-m-d H:i:s');

            $newProjectId = $this->create($projectData);

            // タスクを複製
            $query = "
                INSERT INTO tasks (
                    project_id, title, description, status, priority,
                    estimated_hours, created_at, updated_at
                )
                SELECT 
                    :new_project_id, title, description, 'not_started', priority,
                    estimated_hours, :created_at, :updated_at
                FROM tasks
                WHERE project_id = :old_project_id
                AND deleted_at IS NULL
            ";
            $now = date('Y-m-d H:i:s');
            $this->executeQuery($query, [
                'new_project_id' => $newProjectId,
                'old_project_id' => $projectId,
                'created_at' => $now,
                'updated_at' => $now
            ]);

            // マイルストーンを複製
            $query = "
                INSERT INTO project_milestones (
                    project_id, title, description, due_date, created_at
                )
                SELECT 
                    :new_project_id, title, description, due_date, :created_at
                FROM project_milestones
                WHERE project_id = :old_project_id
            ";
            $this->executeQuery($query, [
                'new_project_id' => $newProjectId,
                'old_project_id' => $projectId,
                'created_at' => $now
            ]);

            $this->commit();
            return $newProjectId;
        } catch (PDOException $e) {
            $this->rollback();
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'method' => 'cloneProject',
                'project_id' => $projectId
            ]);
            throw $e;
        }
    }

    /**
     * プロジェクトデータを検証
     */
    private function validateProjectData(array $data, bool $isCreate = true): void
    {
        $errors = [];

        // 必須フィールドの検証（新規作成時のみ）
        if ($isCreate) {
            $requiredFields = ['name', 'owner_id'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $errors[$field] = sprintf('%sは必須です。', $field);
                }
            }
        }

        // 名前の長さを検証
        if (isset($data['name'])) {
            if (mb_strlen($data['name']) > 100) {
                $errors['name'] = 'プロジェクト名は100文字以内である必要があります。';
            }
        }

        // 状態の検証
        if (isset($data['status'])) {
            $this->validateStatus($data['status']);
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    /**
     * プロジェクトの状態を検証
     */
    private function validateStatus(string $status): void
    {
        $validStatuses = ['planning', 'active', 'on_hold', 'completed', 'cancelled'];
        
        if (!in_array($status, $validStatuses)) {
            throw new ValidationException([
                'status' => '無効なステータスです。'
            ]);
        }
    }

    /**
     * プロジェクトの統計情報を取得
     */
    public function getProjectStatistics(int $userId): array
    {
        try {
            $query = "
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN p.status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN p.status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN p.status = 'on_hold' THEN 1 ELSE 0 END) as on_hold
                FROM {$this->table} p
                INNER JOIN project_members pm ON p.id = pm.project_id
                WHERE pm.user_id = :user_id
                AND p.deleted_at IS NULL
            ";
            return $this->fetchQuery($query, ['user_id' => $userId])[0];
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'method' => 'getProjectStatistics',
                'user_id' => $userId
            ]);
            throw $e;
        }
    }

    /**
     * 最近のプロジェクトアクティビティを取得
     */
    public function getRecentActivities(int $userId, int $limit = 5): array
    {
        try {
            $query = "
                SELECT 
                    'project' as type,
                    p.id,
                    p.name as title,
                    p.status,
                    p.updated_at as created_at,
                    CASE 
                        WHEN p.status = 'completed' THEN 'プロジェクトが完了しました'
                        WHEN p.status = 'active' THEN 'プロジェクトが開始されました'
                        WHEN p.status = 'on_hold' THEN 'プロジェクトが一時停止されました'
                        ELSE 'プロジェクトが更新されました'
                    END as description
                FROM {$this->table} p
                INNER JOIN project_members pm ON p.id = pm.project_id
                WHERE pm.user_id = :user_id
                AND p.deleted_at IS NULL
                ORDER BY p.updated_at DESC
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
