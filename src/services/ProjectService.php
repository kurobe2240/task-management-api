<?php
declare(strict_types=1);

namespace App\Services;

use App\Exceptions\{AuthorizationException, ValidationException};
use App\Repository\{ProjectRepository, UserRepository};
use Psr\Log\LoggerInterface;

class ProjectService
{
    private ProjectRepository $projectRepository;
    private UserRepository $userRepository;
    private LoggerInterface $logger;

    public function __construct(
        ProjectRepository $projectRepository,
        UserRepository $userRepository,
        LoggerInterface $logger
    ) {
        $this->projectRepository = $projectRepository;
        $this->userRepository = $userRepository;
        $this->logger = $logger;
    }

    /**
     * プロジェクトを作成
     */
    public function createProject(array $data, int $userId): int
    {
        try {
            // プロジェクトデータを準備
            $projectData = array_merge($data, [
                'owner_id' => $userId,
                'status' => $data['status'] ?? 'planning',
                'created_by' => $userId
            ]);

            // プロジェクトを作成
            $projectId = $this->projectRepository->create($projectData);

            // 作成者をプロジェクトメンバーとして追加（管理者権限）
            $this->projectRepository->addMember($projectId, $userId, 'admin');

            return $projectId;
        } catch (\Exception $e) {
            $this->logger->error('プロジェクト作成エラー', [
                'data' => $data,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * プロジェクトを更新
     */
    public function updateProject(int $projectId, array $data, int $userId): bool
    {
        try {
            $project = $this->getProject($projectId);
            
            if (!$project) {
                throw new ValidationException(['project' => 'プロジェクトが見つかりません。']);
            }

            // 権限チェック
            $this->checkProjectAccess($projectId, $userId, true);

            return $this->projectRepository->update($projectId, $data);
        } catch (\Exception $e) {
            $this->logger->error('プロジェクト更新エラー', [
                'project_id' => $projectId,
                'data' => $data,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * プロジェクトを削除
     */
    public function deleteProject(int $projectId, int $userId): bool
    {
        try {
            $project = $this->getProject($projectId);
            
            if (!$project) {
                throw new ValidationException(['project' => 'プロジェクトが見つかりません。']);
            }

            // 権限チェック（所有者のみ削除可能）
            $this->checkProjectOwnership($project, $userId);

            return $this->projectRepository->delete($projectId);
        } catch (\Exception $e) {
            $this->logger->error('プロジェクト削除エラー', [
                'project_id' => $projectId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * プロジェクトを取得
     */
    public function getProject(int $projectId): ?array
    {
        try {
            return $this->projectRepository->find($projectId);
        } catch (\Exception $e) {
            $this->logger->error('プロジェクト取得エラー', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * プロジェクトメンバーを追加
     */
    public function addProjectMember(int $projectId, int $memberId, string $role, int $userId): bool
    {
        try {
            // 権限チェック（管理者のみメンバー追加可能）
            $this->checkProjectAccess($projectId, $userId, true);

            // メンバーとして追加するユーザーの存在確認
            $member = $this->userRepository->find($memberId);
            if (!$member) {
                throw new ValidationException(['member' => 'ユーザーが見つかりません。']);
            }

            // メンバーを追加
            return $this->projectRepository->addMember($projectId, $memberId, $role);
        } catch (\Exception $e) {
            $this->logger->error('プロジェクトメンバー追加エラー', [
                'project_id' => $projectId,
                'member_id' => $memberId,
                'role' => $role,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * プロジェクトメンバーを削除
     */
    public function removeProjectMember(int $projectId, int $memberId, int $userId): bool
    {
        try {
            // 権限チェック（管理者のみメンバー削除可能）
            $this->checkProjectAccess($projectId, $userId, true);

            // プロジェクトオーナーは削除不可
            $project = $this->getProject($projectId);
            if ($project['owner_id'] === $memberId) {
                throw new ValidationException(['member' => 'プロジェクトオーナーは削除できません。']);
            }

            return $this->projectRepository->removeMember($projectId, $memberId);
        } catch (\Exception $e) {
            $this->logger->error('プロジェクトメンバー削除エラー', [
                'project_id' => $projectId,
                'member_id' => $memberId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * メンバーの役割を更新
     */
    public function updateMemberRole(int $projectId, int $memberId, string $role, int $userId): bool
    {
        try {
            // 権限チェック（管理者のみ役割更新可能）
            $this->checkProjectAccess($projectId, $userId, true);

            // プロジェクトオーナーの役割は変更不可
            $project = $this->getProject($projectId);
            if ($project['owner_id'] === $memberId) {
                throw new ValidationException(['member' => 'プロジェクトオーナーの役割は変更できません。']);
            }

            return $this->projectRepository->updateMemberRole($projectId, $memberId, $role);
        } catch (\Exception $e) {
            $this->logger->error('メンバー役割更新エラー', [
                'project_id' => $projectId,
                'member_id' => $memberId,
                'role' => $role,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * プロジェクトの進捗状況を取得
     */
    public function getProjectProgress(int $projectId, int $userId): array
    {
        try {
            // 権限チェック
            $this->checkProjectAccess($projectId, $userId);

            return $this->projectRepository->getProgress($projectId);
        } catch (\Exception $e) {
            $this->logger->error('プロジェクト進捗取得エラー', [
                'project_id' => $projectId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * プロジェクトの状態を更新
     */
    public function updateProjectStatus(int $projectId, string $status, int $userId): bool
    {
        try {
            // 権限チェック（管理者のみ状態更新可能）
            $this->checkProjectAccess($projectId, $userId, true);

            return $this->projectRepository->updateStatus($projectId, $status);
        } catch (\Exception $e) {
            $this->logger->error('プロジェクト状態更新エラー', [
                'project_id' => $projectId,
                'status' => $status,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * プロジェクトへのアクセス権をチェック
     */
    private function checkProjectAccess(int $projectId, int $userId, bool $requireAdmin = false): void
    {
        $members = $this->projectRepository->getProjectMembers($projectId);
        
        $userRole = null;
        foreach ($members as $member) {
            if ($member['id'] === $userId) {
                $userRole = $member['role'];
                break;
            }
        }

        if (!$userRole) {
            throw new AuthorizationException('このプロジェクトへのアクセス権がありません。');
        }

        if ($requireAdmin && $userRole !== 'admin') {
            throw new AuthorizationException('この操作を行う権限がありません。');
        }
    }

    /**
     * プロジェクトの所有権をチェック
     */
    private function checkProjectOwnership(array $project, int $userId): void
    {
        if ($project['owner_id'] !== $userId) {
            throw new AuthorizationException('この操作を行う権限がありません。');
        }
    }
}
