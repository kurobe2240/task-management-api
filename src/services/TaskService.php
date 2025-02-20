<?php
declare(strict_types=1);

namespace App\Services;

use App\Exceptions\{AuthorizationException, ValidationException};
use App\Repository\{TaskRepository, ProjectRepository};
use Psr\Log\LoggerInterface;

class TaskService
{
    private TaskRepository $taskRepository;
    private ProjectRepository $projectRepository;
    private LoggerInterface $logger;

    public function __construct(
        TaskRepository $taskRepository,
        ProjectRepository $projectRepository,
        LoggerInterface $logger
    ) {
        $this->taskRepository = $taskRepository;
        $this->projectRepository = $projectRepository;
        $this->logger = $logger;
    }

    /**
     * タスクを作成
     */
    public function createTask(array $data, int $userId): int
    {
        try {
            // プロジェクトの存在確認と権限チェック
            $this->checkProjectAccess($data['project_id'], $userId);

            // タスクデータを準備
            $taskData = array_merge($data, [
                'created_by' => $userId,
                'status' => $data['status'] ?? 'not_started',
                'priority' => $data['priority'] ?? 'medium',
                'progress' => $data['progress'] ?? 0
            ]);

            return $this->taskRepository->create($taskData);
        } catch (\Exception $e) {
            $this->logger->error('タスク作成エラー', [
                'data' => $data,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * タスクを更新
     */
    public function updateTask(int $taskId, array $data, int $userId): bool
    {
        try {
            $task = $this->getTask($taskId);
            
            // タスクの存在確認
            if (!$task) {
                throw new ValidationException(['task' => 'タスクが見つかりません。']);
            }

            // 権限チェック
            $this->checkTaskAccess($task, $userId);

            return $this->taskRepository->update($taskId, $data);
        } catch (\Exception $e) {
            $this->logger->error('タスク更新エラー', [
                'task_id' => $taskId,
                'data' => $data,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * タスクを削除
     */
    public function deleteTask(int $taskId, int $userId): bool
    {
        try {
            $task = $this->getTask($taskId);
            
            // タスクの存在確認
            if (!$task) {
                throw new ValidationException(['task' => 'タスクが見つかりません。']);
            }

            // 権限チェック
            $this->checkTaskAccess($task, $userId, true);

            return $this->taskRepository->delete($taskId);
        } catch (\Exception $e) {
            $this->logger->error('タスク削除エラー', [
                'task_id' => $taskId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * タスクを取得
     */
    public function getTask(int $taskId): ?array
    {
        try {
            return $this->taskRepository->find($taskId);
        } catch (\Exception $e) {
            $this->logger->error('タスク取得エラー', [
                'task_id' => $taskId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * プロジェクトのタスク一覧を取得
     */
    public function getProjectTasks(int $projectId, int $userId, array $filters = []): array
    {
        try {
            // プロジェクトへのアクセス権を確認
            $this->checkProjectAccess($projectId, $userId);

            // フィルター条件を構築
            $criteria = ['project_id' => $projectId];
            
            if (isset($filters['status'])) {
                $criteria['status'] = $filters['status'];
            }
            
            if (isset($filters['assignee_id'])) {
                $criteria['assignee_id'] = $filters['assignee_id'];
            }

            // ソート条件を設定
            $orderBy = [];
            if (isset($filters['sort'])) {
                foreach ($filters['sort'] as $field => $direction) {
                    if (in_array($field, ['due_date', 'priority', 'created_at'])) {
                        $orderBy[$field] = $direction;
                    }
                }
            }
            if (empty($orderBy)) {
                $orderBy['created_at'] = 'DESC';
            }

            return $this->taskRepository->findBy($criteria, $orderBy);
        } catch (\Exception $e) {
            $this->logger->error('プロジェクトタスク取得エラー', [
                'project_id' => $projectId,
                'user_id' => $userId,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * タスクの状態を更新
     */
    public function updateTaskStatus(int $taskId, string $status, int $userId): bool
    {
        try {
            $task = $this->getTask($taskId);
            
            if (!$task) {
                throw new ValidationException(['task' => 'タスクが見つかりません。']);
            }

            $this->checkTaskAccess($task, $userId);

            return $this->taskRepository->updateStatus($taskId, $status);
        } catch (\Exception $e) {
            $this->logger->error('タスク状態更新エラー', [
                'task_id' => $taskId,
                'status' => $status,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * タスクの担当者を更新
     */
    public function updateTaskAssignee(int $taskId, ?int $assigneeId, int $userId): bool
    {
        try {
            $task = $this->getTask($taskId);
            
            if (!$task) {
                throw new ValidationException(['task' => 'タスクが見つかりません。']);
            }

            $this->checkTaskAccess($task, $userId);

            // 担当者がプロジェクトメンバーであることを確認
            if ($assigneeId !== null) {
                $members = $this->projectRepository->getProjectMembers($task['project_id']);
                $memberIds = array_column($members, 'id');
                
                if (!in_array($assigneeId, $memberIds)) {
                    throw new ValidationException([
                        'assignee' => '指定されたユーザーはプロジェクトメンバーではありません。'
                    ]);
                }
            }

            return $this->taskRepository->updateAssignee($taskId, $assigneeId);
        } catch (\Exception $e) {
            $this->logger->error('タスク担当者更新エラー', [
                'task_id' => $taskId,
                'assignee_id' => $assigneeId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * プロジェクトへのアクセス権をチェック
     */
    private function checkProjectAccess(int $projectId, int $userId): void
    {
        $members = $this->projectRepository->getProjectMembers($projectId);
        $memberIds = array_column($members, 'id');

        if (!in_array($userId, $memberIds)) {
            throw new AuthorizationException('このプロジェクトへのアクセス権がありません。');
        }
    }

    /**
     * タスクへのアクセス権をチェック
     */
    private function checkTaskAccess(array $task, int $userId, bool $requireOwnership = false): void
    {
        // プロジェクトメンバーシップを確認
        $this->checkProjectAccess($task['project_id'], $userId);

        // 所有権が必要な操作の場合
        if ($requireOwnership && $task['created_by'] !== $userId) {
            $members = $this->projectRepository->getProjectMembers($task['project_id']);
            
            // プロジェクト管理者かどうかを確認
            $isAdmin = false;
            foreach ($members as $member) {
                if ($member['id'] === $userId && $member['role'] === 'admin') {
                    $isAdmin = true;
                    break;
                }
            }

            if (!$isAdmin) {
                throw new AuthorizationException('このタスクを操作する権限がありません。');
            }
        }
    }
}
