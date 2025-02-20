<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\{
    TaskService,
    FileService,
    ActivityLogService
};
use App\Exceptions\{AuthorizationException, ValidationException};
use Psr\Http\Message\{
    ResponseInterface as Response,
    ServerRequestInterface as Request,
    UploadedFileInterface
};
use Psr\Log\LoggerInterface;

class TaskController
{
    private TaskService $taskService;
    private FileService $fileService;
    private ActivityLogService $activityLogService;
    private LoggerInterface $logger;

    public function __construct(
        TaskService $taskService,
        FileService $fileService,
        ActivityLogService $activityLogService,
        LoggerInterface $logger
    ) {
        $this->taskService = $taskService;
        $this->fileService = $fileService;
        $this->activityLogService = $activityLogService;
        $this->logger = $logger;
    }

    /**
     * タスクを作成
     */
    public function createTask(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            $userId = $request->getAttribute('user_id');

            $task = $this->taskService->createTask($data, $userId);

            return $this->jsonResponse($response, [
                'status' => 'success',
                'message' => 'タスクが作成されました。',
                'data' => $task
            ], 201);
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
            $this->logger->error('タスク作成エラー', [
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
     * タスク一覧を取得
     */
    public function getTasks(Request $request, Response $response): Response
    {
        try {
            $userId = $request->getAttribute('user_id');
            $filters = $request->getQueryParams();

            // フィルターの整形
            $validFilters = array_intersect_key($filters, array_flip([
                'status', 'project_id', 'priority', 'overdue',
                'order_by', 'order_direction'
            ]));

            $tasks = $this->taskService->getUserTasks($userId, $validFilters);

            return $this->jsonResponse($response, [
                'status' => 'success',
                'data' => $tasks
            ]);
        } catch (\Exception $e) {
            $this->logger->error('タスク一覧取得エラー', [
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
     * タスク詳細を取得
     */
    public function getTaskDetails(Request $request, Response $response, array $args): Response
    {
        try {
            $taskId = (int)$args['id'];
            $userId = $request->getAttribute('user_id');

            $task = $this->taskService->getTaskDetails($taskId, $userId);

            return $this->jsonResponse($response, [
                'status' => 'success',
                'data' => $task
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
            $this->logger->error('タスク詳細取得エラー', [
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
     * タスクを更新
     */
    public function updateTask(Request $request, Response $response, array $args): Response
    {
        try {
            $taskId = (int)$args['id'];
            $userId = $request->getAttribute('user_id');
            $data = $request->getParsedBody();

            $task = $this->taskService->updateTask($taskId, $data, $userId);

            return $this->jsonResponse($response, [
                'status' => 'success',
                'message' => 'タスクが更新されました。',
                'data' => $task
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
            $this->logger->error('タスク更新エラー', [
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
     * タスクを削除
     */
    public function deleteTask(Request $request, Response $response, array $args): Response
    {
        try {
            $taskId = (int)$args['id'];
            $userId = $request->getAttribute('user_id');

            $this->taskService->deleteTask($taskId, $userId);

            return $this->jsonResponse($response, [
                'status' => 'success',
                'message' => 'タスクが削除されました。'
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
            $this->logger->error('タスク削除エラー', [
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
     * タスクのステータスを更新
     */
    public function updateTaskStatus(Request $request, Response $response, array $args): Response
    {
        try {
            $taskId = (int)$args['id'];
            $userId = $request->getAttribute('user_id');
            $data = $request->getParsedBody();

            if (empty($data['status'])) {
                throw new ValidationException([
                    'status' => 'ステータスは必須です。'
                ]);
            }

            $task = $this->taskService->updateTaskStatus($taskId, $data['status'], $userId);

            return $this->jsonResponse($response, [
                'status' => 'success',
                'message' => 'タスクのステータスが更新されました。',
                'data' => $task
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
            $this->logger->error('タスクステータス更新エラー', [
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
    /**
     * タスクにファイルを添付
     */
    public function uploadFile(Request $request, Response $response, array $args): Response
    {
        try {
            $taskId = (int)$args['id'];
            $userId = $request->getAttribute('user_id');
            $uploadedFiles = $request->getUploadedFiles();

            if (empty($uploadedFiles['file'])) {
                throw new ValidationException([
                    'file' => 'ファイルは必須です。'
                ]);
            }

            /** @var UploadedFileInterface $uploadedFile */
            $uploadedFile = $uploadedFiles['file'];

            $file = $this->fileService->uploadTaskFile([
                'name' => $uploadedFile->getClientFilename(),
                'type' => $uploadedFile->getClientMediaType(),
                'tmp_name' => $uploadedFile->getStream()->getMetadata('uri'),
                'error' => $uploadedFile->getError(),
                'size' => $uploadedFile->getSize()
            ], $taskId, $userId);

            // アクティビティを記録
            $this->activityLogService->log(
                $userId,
                'file_upload',
                'task',
                $taskId,
                ['file_id' => $file['id'], 'filename' => $file['filename']]
            );

            return $this->jsonResponse($response, [
                'status' => 'success',
                'message' => 'ファイルがアップロードされました。',
                'data' => $file
            ], 201);
        } catch (ValidationException $e) {
            return $this->jsonResponse($response, [
                'status' => 'error',
                'message' => 'バリデーションエラー',
                'errors' => $e->getErrors()
            ], 422);
        } catch (\Exception $e) {
            $this->logger->error('ファイルアップロードエラー', [
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
     * タスクの添付ファイル一覧を取得
     */
    public function getFiles(Request $request, Response $response, array $args): Response
    {
        try {
            $taskId = (int)$args['id'];
            $userId = $request->getAttribute('user_id');

            $files = $this->fileService->getTaskFiles($taskId);

            return $this->jsonResponse($response, [
                'status' => 'success',
                'data' => $files
            ]);
        } catch (\Exception $e) {
            $this->logger->error('ファイル一覧取得エラー', [
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
     * タスクの添付ファイルを削除
     */
    public function deleteFile(Request $request, Response $response, array $args): Response
    {
        try {
            $taskId = (int)$args['task_id'];
            $fileId = (int)$args['file_id'];
            $userId = $request->getAttribute('user_id');

            $this->fileService->deleteFile($fileId, $userId);

            // アクティビティを記録
            $this->activityLogService->log(
                $userId,
                'file_delete',
                'task',
                $taskId,
                ['file_id' => $fileId]
            );

            return $this->jsonResponse($response, [
                'status' => 'success',
                'message' => 'ファイルが削除されました。'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('ファイル削除エラー', [
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
     * タスクのアクティビティログを取得
     */
    public function getActivities(Request $request, Response $response, array $args): Response
    {
        try {
            $taskId = (int)$args['id'];
            $userId = $request->getAttribute('user_id');
            $limit = (int)($request->getQueryParams()['limit'] ?? 10);

            $activities = $this->activityLogService->getTaskActivities($taskId, $limit);

            return $this->jsonResponse($response, [
                'status' => 'success',
                'data' => $activities
            ]);
        } catch (\Exception $e) {
            $this->logger->error('アクティビティログ取得エラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->jsonResponse($response, [
                'status' => 'error',
                'message' => '内部サーバーエラーが発生しました。'
            ], 500);
        }
    }

    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
