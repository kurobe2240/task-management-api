<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Exceptions\ValidationException;
use Psr\Http\Message\{ResponseInterface as Response, ServerRequestInterface as Request};
use Psr\Log\LoggerInterface;

class AuthController
{
    private AuthService $authService;
    private LoggerInterface $logger;

    public function __construct(AuthService $authService, LoggerInterface $logger)
    {
        $this->authService = $authService;
        $this->logger = $logger;
    }

    /**
     * ログイン処理
     */
    public function login(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();

            // バリデーション
            $errors = [];
            if (empty($data['email'])) {
                $errors['email'] = 'メールアドレスは必須です。';
            }
            if (empty($data['password'])) {
                $errors['password'] = 'パスワードは必須です。';
            }
            if (!empty($errors)) {
                throw new ValidationException($errors);
            }

            // 認証処理
            $result = $this->authService->authenticate(
                $data['email'],
                $data['password']
            );

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'data' => $result
            ]));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('ログインエラー', [
                'error' => $e->getMessage(),
                'email' => $data['email'] ?? null
            ]);

            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }
    }

    /**
     * ユーザー登録
     */
    public function register(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();

            // バリデーション
            $errors = [];
            if (empty($data['email'])) {
                $errors['email'] = 'メールアドレスは必須です。';
            }
            if (empty($data['password'])) {
                $errors['password'] = 'パスワードは必須です。';
            }
            if (empty($data['name'])) {
                $errors['name'] = '名前は必須です。';
            }
            if (!empty($errors)) {
                throw new ValidationException($errors);
            }

            // ユーザー登録
            $result = $this->authService->register($data);

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'data' => $result
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);

        } catch (\Exception $e) {
            $this->logger->error('ユーザー登録エラー', [
                'error' => $e->getMessage(),
                'data' => $data ?? null
            ]);

            $status = $e instanceof ValidationException ? 400 : 500;

            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($status);
        }
    }

    /**
     * パスワードリセットをリクエスト
     */
    public function requestPasswordReset(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();

            // バリデーション
            if (empty($data['email'])) {
                throw new ValidationException(['email' => 'メールアドレスは必須です。']);
            }

            // パスワードリセットトークンを生成
            $token = $this->authService->initiatePasswordReset($data['email']);

            // トークンが生成されなかった場合（ユーザーが存在しない場合など）でも
            // セキュリティのため成功レスポンスを返す
            $response->getBody()->write(json_encode([
                'status' => 'success',
                'message' => 'パスワードリセット手順をメールで送信しました。'
            ]));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('パスワードリセットリクエストエラー', [
                'error' => $e->getMessage(),
                'email' => $data['email'] ?? null
            ]);

            $status = $e instanceof ValidationException ? 400 : 500;

            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($status);
        }
    }

    /**
     * パスワードをリセット
     */
    public function resetPassword(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();

            // バリデーション
            $errors = [];
            if (empty($data['token'])) {
                $errors['token'] = 'トークンは必須です。';
            }
            if (empty($data['password'])) {
                $errors['password'] = '新しいパスワードは必須です。';
            }
            if (!empty($errors)) {
                throw new ValidationException($errors);
            }

            // パスワードをリセット
            $this->authService->resetPassword($data['token'], $data['password']);

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'message' => 'パスワードを更新しました。'
            ]));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('パスワードリセットエラー', [
                'error' => $e->getMessage()
            ]);

            $status = $e instanceof ValidationException ? 400 : 500;

            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($status);
        }
    }

    /**
     * トークンを更新
     */
    public function refreshToken(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();

            // バリデーション
            if (empty($data['refresh_token'])) {
                throw new ValidationException(['refresh_token' => 'リフレッシュトークンは必須です。']);
            }

            // トークンを更新
            $result = $this->authService->refreshToken($data['refresh_token']);

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'data' => $result
            ]));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('トークン更新エラー', [
                'error' => $e->getMessage()
            ]);

            $status = $e instanceof ValidationException ? 400 : 500;

            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($status);
        }
    }
}
