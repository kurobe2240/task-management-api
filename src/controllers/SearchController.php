<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\SearchService;
use Psr\Http\Message\{ResponseInterface as Response, ServerRequestInterface as Request};
use Psr\Log\LoggerInterface;

class SearchController
{
    private SearchService $searchService;
    private LoggerInterface $logger;

    public function __construct(
        SearchService $searchService,
        LoggerInterface $logger
    ) {
        $this->searchService = $searchService;
        $this->logger = $logger;
    }

    /**
     * タスクを検索
     */
    public function searchTasks(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $userId = $request->getAttribute('user_id');

            // 検索パラメータの設定
            $searchParams = [
                'query' => $params['q'] ?? '',
                'status' => $params['status'] ?? null,
                'priority' => $params['priority'] ?? null,
                'project_id' => isset($params['project_id']) ? (int)$params['project_id'] : null,
                'due_date_start' => $params['due_date_start'] ?? null,
                'due_date_end' => $params['due_date_end'] ?? null,
                'tags' => isset($params['tags']) ? explode(',', $params['tags']) : [],
                'page' => isset($params['page']) ? (int)$params['page'] : 1,
                'per_page' => isset($params['per_page']) ? (int)$params['per_page'] : 10,
                'sort_by' => $params['sort_by'] ?? 'created_at',
                'sort_direction' => $params['sort_direction'] ?? 'desc'
            ];

            $result = $this->searchService->searchTasks($searchParams, $userId);

            return $this->jsonResponse($response, [
                'status' => 'success',
                'data' => $result['items'],
                'meta' => [
                    'total' => $result['total'],
                    'page' => $searchParams['page'],
                    'per_page' => $searchParams['per_page'],
                    'total_pages' => ceil($result['total'] / $searchParams['per_page'])
                ]
            ]);
        } catch (\Exception $e) {
            $this->logger->error('タスク検索エラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->jsonResponse($response, [
                'status' => 'error',
                'message' => '検索中にエラーが発生しました。'
            ], 500);
        }
    }

    /**
     * プロジェクトを検索
     */
    public function searchProjects(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $userId = $request->getAttribute('user_id');

            // 検索パラメータの設定
            $searchParams = [
                'query' => $params['q'] ?? '',
                'status' => $params['status'] ?? null,
                'start_date' => $params['start_date'] ?? null,
                'end_date' => $params['end_date'] ?? null,
                'page' => isset($params['page']) ? (int)$params['page'] : 1,
                'per_page' => isset($params['per_page']) ? (int)$params['per_page'] : 10,
                'sort_by' => $params['sort_by'] ?? 'created_at',
                'sort_direction' => $params['sort_direction'] ?? 'desc'
            ];

            $result = $this->searchService->searchProjects($searchParams, $userId);

            return $this->jsonResponse($response, [
                'status' => 'success',
                'data' => $result['items'],
                'meta' => [
                    'total' => $result['total'],
                    'page' => $searchParams['page'],
                    'per_page' => $searchParams['per_page'],
                    'total_pages' => ceil($result['total'] / $searchParams['per_page'])
                ]
            ]);
        } catch (\Exception $e) {
            $this->logger->error('プロジェクト検索エラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->jsonResponse($response, [
                'status' => 'error',
                'message' => '検索中にエラーが発生しました。'
            ], 500);
        }
    }

    /**
     * ダッシュボードデータを取得
     */
    public function getDashboardData(Request $request, Response $response): Response
    {
        try {
            $userId = $request->getAttribute('user_id');
            $result = $this->searchService->getDashboardData($userId);

            return $this->jsonResponse($response, [
                'status' => 'success',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            $this->logger->error('ダッシュボードデータ取得エラー', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->jsonResponse($response, [
                'status' => 'error',
                'message' => 'データ取得中にエラーが発生しました。'
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
