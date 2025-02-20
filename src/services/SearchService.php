<?php
declare(strict_types=1);

namespace App\Services;

use App\Repository\{TaskRepository, ProjectRepository};
use Psr\Log\LoggerInterface;

class SearchService
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
     * タスクを検索
     */
    public function searchTasks(array $params, int $userId): array
    {
        // 検索条件の構築
        $conditions = [];
        $values = [];

        // ベース条件（ソフトデリート考慮）
        $conditions[] = 'deleted_at IS NULL';
        $conditions[] = '(created_by = ? OR assigned_to = ?)';
        $values[] = $userId;
        $values[] = $userId;

        // キーワード検索
        if (!empty($params['query'])) {
            $conditions[] = '(title LIKE ? OR description LIKE ?)';
            $values[] = '%' . $params['query'] . '%';
            $values[] = '%' . $params['query'] . '%';
        }

        // ステータスフィルター
        if (!empty($params['status'])) {
            $conditions[] = 'status = ?';
            $values[] = $params['status'];
        }

        // 優先度フィルター
        if (!empty($params['priority'])) {
            $conditions[] = 'priority = ?';
            $values[] = $params['priority'];
        }

        // プロジェクトフィルター
        if (!empty($params['project_id'])) {
            $conditions[] = 'project_id = ?';
            $values[] = $params['project_id'];
        }

        // 期限範囲フィルター
        if (!empty($params['due_date_start'])) {
            $conditions[] = 'due_date >= ?';
            $values[] = $params['due_date_start'];
        }
        if (!empty($params['due_date_end'])) {
            $conditions[] = 'due_date <= ?';
            $values[] = $params['due_date_end'];
        }

        // ソート条件
        $sortBy = $params['sort_by'] ?? 'created_at';
        $sortDirection = $params['sort_direction'] ?? 'desc';
        $orderBy = "ORDER BY {$sortBy} {$sortDirection}";

        // ページネーション
        $page = max(1, $params['page'] ?? 1);
        $perPage = max(1, min(100, $params['per_page'] ?? 10));
        $offset = ($page - 1) * $perPage;

        // クエリ実行
        $whereClause = implode(' AND ', $conditions);
        $total = $this->taskRepository->count($whereClause, $values);
        $items = $this->taskRepository->findByConditions(
            $whereClause,
            $values,
            $orderBy,
            $perPage,
            $offset
        );

        return [
            'items' => $items,
            'total' => $total
        ];
    }

    /**
     * プロジェクトを検索
     */
    public function searchProjects(array $params, int $userId): array
    {
        // 検索条件の構築
        $conditions = [];
        $values = [];

        // ベース条件（ソフトデリート考慮）
        $conditions[] = 'p.deleted_at IS NULL';
        $conditions[] = 'pm.user_id = ?';
        $values[] = $userId;

        // キーワード検索
        if (!empty($params['query'])) {
            $conditions[] = '(p.name LIKE ? OR p.description LIKE ?)';
            $values[] = '%' . $params['query'] . '%';
            $values[] = '%' . $params['query'] . '%';
        }

        // ステータスフィルター
        if (!empty($params['status'])) {
            $conditions[] = 'p.status = ?';
            $values[] = $params['status'];
        }

        // 日付範囲フィルター
        if (!empty($params['start_date'])) {
            $conditions[] = 'p.start_date >= ?';
            $values[] = $params['start_date'];
        }
        if (!empty($params['end_date'])) {
            $conditions[] = 'p.end_date <= ?';
            $values[] = $params['end_date'];
        }

        // ソート条件
        $sortBy = $params['sort_by'] ?? 'p.created_at';
        $sortDirection = $params['sort_direction'] ?? 'desc';
        $orderBy = "ORDER BY {$sortBy} {$sortDirection}";

        // ページネーション
        $page = max(1, $params['page'] ?? 1);
        $perPage = max(1, min(100, $params['per_page'] ?? 10));
        $offset = ($page - 1) * $perPage;

        // クエリ実行
        $whereClause = implode(' AND ', $conditions);
        $total = $this->projectRepository->count($whereClause, $values);
        $items = $this->projectRepository->findByConditions(
            $whereClause,
            $values,
            $orderBy,
            $perPage,
            $offset
        );

        return [
            'items' => $items,
            'total' => $total
        ];
    }

    /**
     * ダッシュボードデータを取得
     */
    public function getDashboardData(int $userId): array
    {
        $taskStats = $this->taskRepository->getTaskStatistics($userId);
        $projectStats = $this->projectRepository->getProjectStatistics($userId);
        
        return [
            'tasks' => [
                'total' => $taskStats['total'] ?? 0,
                'pending' => $taskStats['pending'] ?? 0,
                'in_progress' => $taskStats['in_progress'] ?? 0,
                'completed' => $taskStats['completed'] ?? 0,
                'overdue' => $taskStats['overdue'] ?? 0
            ],
            'projects' => [
                'total' => $projectStats['total'] ?? 0,
                'active' => $projectStats['active'] ?? 0,
                'completed' => $projectStats['completed'] ?? 0,
                'on_hold' => $projectStats['on_hold'] ?? 0
            ],
            'recent_activities' => $this->getRecentActivities($userId)
        ];
    }

    /**
     * 最近のアクティビティを取得
     */
    private function getRecentActivities(int $userId): array
    {
        // タスクの更新
        $taskActivities = $this->taskRepository->getRecentActivities($userId, 5);
        
        // プロジェクトの更新
        $projectActivities = $this->projectRepository->getRecentActivities($userId, 5);

        // アクティビティを結合してソート
        $activities = array_merge($taskActivities, $projectActivities);
        usort($activities, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

        return array_slice($activities, 0, 10);
    }
}
