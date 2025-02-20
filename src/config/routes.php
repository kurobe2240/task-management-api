<?php
declare(strict_types=1);

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use App\Controllers\{AuthController, TaskController, ProjectController};
use App\Middleware\{AuthMiddleware, JsonBodyParserMiddleware};

return function (App $app) {
    // CORSプリフライトリクエスト用のオプションルート
    $app->options('/{routes:.+}', function ($request, $response) {
        return $response;
    });

    // 認証関連のルート
    $app->group('/auth', function (RouteCollectorProxy $group) {
        $group->post('/register', [AuthController::class, 'register']);
        $group->post('/login', [AuthController::class, 'login']);
        $group->post('/password/reset-request', [AuthController::class, 'requestPasswordReset']);

        // 認証が必要なルート
        $group->group('', function (RouteCollectorProxy $group) {
            $group->post('/password/change', [AuthController::class, 'changePassword']);
        })->add(AuthMiddleware::class);
    })->add(JsonBodyParserMiddleware::class);

    // 検索・ダッシュボード関連のルート（認証必須）
    $app->group('/api/search', function (RouteCollectorProxy $group) {
        $group->get('/tasks', [SearchController::class, 'searchTasks']);
        $group->get('/projects', [SearchController::class, 'searchProjects']);
        $group->get('/dashboard', [SearchController::class, 'getDashboardData']);
    })->add(AuthMiddleware::class)
      ->add(JsonBodyParserMiddleware::class);

    // APIルート（認証必須）
    $app->group('/api', function (RouteCollectorProxy $group) {
        // プロジェクト関連のルート
        $group->group('/projects', function (RouteCollectorProxy $group) {
            $group->get('', [ProjectController::class, 'getUserProjects']);
            $group->post('', [ProjectController::class, 'createProject']);
            $group->get('/{id:[0-9]+}', [ProjectController::class, 'getProjectDetails']);
            $group->put('/{id:[0-9]+}', [ProjectController::class, 'updateProject']);
            $group->delete('/{id:[0-9]+}', [ProjectController::class, 'deleteProject']);
            $group->patch('/{id:[0-9]+}/status', [ProjectController::class, 'updateProjectStatus']);

            // プロジェクトメンバー管理
            $group->post('/{id:[0-9]+}/members', [ProjectController::class, 'addProjectMember']);
            $group->delete('/{id:[0-9]+}/members/{member_id:[0-9]+}', [ProjectController::class, 'removeProjectMember']);
        });

        // タスク関連のルート
        $group->group('/tasks', function (RouteCollectorProxy $group) {
            $group->get('', [TaskController::class, 'getTasks']);
            $group->post('', [TaskController::class, 'createTask']);
            $group->get('/{id:[0-9]+}', [TaskController::class, 'getTaskDetails']);
            $group->put('/{id:[0-9]+}', [TaskController::class, 'updateTask']);
            $group->delete('/{id:[0-9]+}', [TaskController::class, 'deleteTask']);
            $group->patch('/{id:[0-9]+}/status', [TaskController::class, 'updateTaskStatus']);

            // タスクの添付ファイル関連
            $group->post('/{id:[0-9]+}/files', [TaskController::class, 'uploadFile']);
            $group->get('/{id:[0-9]+}/files', [TaskController::class, 'getFiles']);
            $group->delete('/{task_id:[0-9]+}/files/{file_id:[0-9]+}', [TaskController::class, 'deleteFile']);

            // タスクのアクティビティログ
            $group->get('/{id:[0-9]+}/activities', [TaskController::class, 'getActivities']);
        });
    })->add(AuthMiddleware::class)
      ->add(JsonBodyParserMiddleware::class);

    // 404エラーハンドラー
    $app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(404)
            ->withJson([
                'status' => 'error',
                'message' => 'リクエストされたリソースが見つかりません。'
            ]);
    });
};
