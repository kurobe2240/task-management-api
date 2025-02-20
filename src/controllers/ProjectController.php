<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\ProjectService;
use App\Exceptions\{AuthorizationException, ValidationException};
use Psr\Http\Message\{ResponseInterface as Response, ServerRequestInterface as Request};
use Psr\Log\LoggerInterface;

class ProjectController
{
    private ProjectService $projectService;
    private LoggerInterface $logger;

    public function __construct(
        ProjectService $projectService,
        LoggerInterface $logger
    ) {
        $this->projectService = $projectService;
        $this->logger = $logger;
    }

    /**
     * プロジェクトを作成
     */
    public function createProject(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            $userId = $request->getAttribute('user_id');

            $project = $this->projectService->createProject($data, $userId);

            return $this->jsonResponse($response, [
                'status' => 'success',
                'message' => 'プロジェクトが作成されました。',
                'data' => $project
            ], 201);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, [
                'status' => 'error',
                'message' => 'バリデーションエラー',
                'errors' => $e->getErrors()
            ], 422);
        } catch (\Exception $e) {
            $this->logger->error('プロジェクト作成エラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->jsonResponse($response, [
                'status' => 'error',
                'message' => '内部サーバーエラーが発生しました。'
            ], 500);
        }
    }

    /**
     * プロジェクト一覧を取得
     */
    public function getProjects(Request $request, Response $response): Response
    {
        try {
            $userId = $request->getAttribute('user_id');
            $filters = $request->getQueryParams();

            // フィルターの整形
            $validFilters = array_intersect_key($filters, array_flip([
                'status', 'active_only', 'order_by', 'order_direction'
            ]));

            $projects = $this->projectService->getUserProjects($userId, $validFilters);

            return $this->jsonResponse($response, [
                'status' => 'success',
                'data' => $projects
            ]);
        } catch (\Exception $e) {
            $this->logger->error('プロジェクト一覧取得エラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->jsonResponse($response, [
                'status' => 'error',
                'message' => '内部サーバーエラーが発生しました。'
            ], 500);
        }
    }

    /**
     * プロジェクト詳細を取得
     */
    public function getProjectDetails(Request $request, Response $response, array $args): Response
    {
        try {
            $projectId = (int)$args['id'];
            $userId = $request->getAttribute('user_id');

            $project = $this->projectService->getProjectDetails($projectId, $userId);

            return $this->jsonResponse($response, [
                'status' => 'success',
                'data' => $project
            ]);
        } catch (AuthorizationException $e) {
            return $this->jsonResponse($response, [
                'status' => 'error',
                'message' => $e->getMessage()
            ], 403);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, [
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            $this->logger->error('プロジェクト詳細取得エラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->jsonResponse($response, [
                'status' => 'error',
                'message' => '内部サーバーエラーが発生しました。'
            ], 500);
        }
    }

    /**
     * プロジェクトを更新
     */
    public function updateProject(Request $request, Response $response, array $args): Response
    {
        try {
            $projectId = (int)$args['id'];
            $userId = $request->getAttribute('user_id');
            $data = $request->getParsedBody();

            $project = $this->projectService->updateProject($projectId, $data, $userId);

            return $this->jsonResponse($response, [
                'status' => 'success',
                'message' => 'プロジェクトが更新されました。',
                'data' => $project
            ]);
        } catch (AuthorizationException $e) {
            return $this->jsonResponse($response, [
                'status' => 'error',
                'message' => $e->getMessage()
            ], 403);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, [
                'status' => 'error',
                'message' => 'バリデーションエラー',
                'errors' => $e->getErrors()
            ], 422);
        } catch (\Exception $e) {
            $this->logger->error('プロジェクト更新エラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->jsonResponse($response, [
                'status' => 'error',
                'message' => '内部サーバーエラーが発生しました。'
            ], 500);
        }
    }

    /**
     * プロジェクトを削除
     */
    public function deleteProject(Request $request, Response $response, array $args): Response
    {
        try {
            $projectId = (int)$args['id'];
            $userId = $request->getAttribute('user_id');

            $this->projectService->deleteProject($projectId, $userId);

            return $this->jsonResponse($response, [
                'status' => 'success',
                'message' => 'プロジェクトが削除されました。'
            ]);
        } catch (AuthorizationException $e) {
            return $this->jsonResponse($response, [
                'status' => 'error',
                'message' => $e->getMessage()
            ], 403);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, [
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            $this->logger->error('プロジェクト削除エラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->jsonResponse($response, [
                'status' => 'error',
                'message' => '内部サーバーエラーが発生しました。'
            ], 500);
        }
    }

    /**
     * プロジェクトメンバーを追加
     */
    public function addProjectMember(Request $request, Response $response, array $args): Response
    {
        try {
            $projectId = (int)$args['id'];
            $userId = $request->getAttribute('user_id');
            $data = $request->getParsedBody();

            $result = $this->projectService->addProjectMember($projectId, $data, $userId);

            return $this->jsonResponse($response, [
                'status' => 'success',
                'message' => 'メンバーがプロジェクトに追加されました。',
                'data' => ['success' => $result]
            ]);
        } catch (AuthorizationException $e) {
            return $this->jsonResponse($response, [
                'status' => 'error',
                'message' => $e->getMessage()
            ], 403);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, [
                'status' => 'error',
                'message' => 'バリデーションエラー',
                'errors' => $e->getErrors()
            ], 422);
        } catch (\Exception $e) {
            $this->logger->error('プロジェクトメンバー追加エラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->jsonResponse($response, [
                'status' => 'error',
                'message' => '内部サーバーエラーが発生しました。'
            ], 500);
        }
    }

    /**
     * プロジェクトメンバーを削除
     */
    public function removeProjectMember(Request $request, Response $response, array $args): Response
    {
        try {
            $projectId = (int)$args['id'];
            $memberId = (int)$args['member_id'];
            $userId = $request->getAttribute('user_id');

            $result = $this->projectService->removeProjectMember($projectId, $memberId, $userId);

            return $this->jsonResponse($response, [
                'status' => 'success',
                'message' => 'メンバーがプロジェクトから削除されました。',
                'data' => ['success' => $result]
            ]);
        } catch (AuthorizationException $e) {
            return $this->jsonResponse($response, [
                'status' => 'error',
                'message' => $e->getMessage()
            ], 403);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, [
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            $this->logger->error('プロジェクトメンバー削除エラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->jsonResponse($response, [
                'status' => 'error',
                'message' => '内部サーバーエラーが発生しました。'
            ], 500);
        }
    }

    /**
     * JSONレスポンスを生成
     */
    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
