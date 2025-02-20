<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Exceptions\AuthenticationException;
use App\Repository\UserRepository;
use Firebase\JWT\{JWT, Key};
use Psr\Http\Message\{ResponseInterface as Response, ServerRequestInterface as Request};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use Psr\Log\LoggerInterface;

class AuthMiddleware implements MiddlewareInterface
{
    private UserRepository $userRepository;
    private LoggerInterface $logger;
    private string $jwtSecret;
    private string $jwtAlgorithm;

    public function __construct(
        UserRepository $userRepository,
        LoggerInterface $logger,
        string $jwtSecret,
        string $jwtAlgorithm = 'HS256'
    ) {
        $this->userRepository = $userRepository;
        $this->logger = $logger;
        $this->jwtSecret = $jwtSecret;
        $this->jwtAlgorithm = $jwtAlgorithm;
    }

    /**
     * ミドルウェアの処理を実行
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $token = $this->extractToken($request);

        if (!$token) {
            throw new AuthenticationException('認証が必要です。');
        }

        try {
            // トークンを検証
            $payload = JWT::decode($token, new Key($this->jwtSecret, $this->jwtAlgorithm));

            // ユーザーを取得
            $user = $this->userRepository->find($payload->sub);

            if (!$user) {
                throw new AuthenticationException('無効なトークンです。');
            }

            if (!$user['is_active']) {
                throw new AuthenticationException('このアカウントは無効化されています。');
            }

            // ユーザーIDをリクエスト属性に追加
            $request = $request->withAttribute('user_id', $user['id']);
            
            // ユーザーの役割をリクエスト属性に追加
            if (isset($user['role'])) {
                $request = $request->withAttribute('user_role', $user['role']);
            }

            return $handler->handle($request);

        } catch (\Exception $e) {
            $this->logger->warning('認証エラー', [
                'error' => $e->getMessage(),
                'token' => $token
            ]);

            throw new AuthenticationException('無効なトークンです。');
        }
    }

    /**
     * リクエストからトークンを抽出
     */
    private function extractToken(Request $request): ?string
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader)) {
            return null;
        }

        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return null;
        }

        return $matches[1];
    }

    /**
     * トークンを生成
     */
    public static function generateToken(int $userId, string $secret, int $expiry = 3600, string $algorithm = 'HS256'): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $expiry;

        $payload = [
            'iat' => $issuedAt,     // 発行時刻
            'exp' => $expire,        // 有効期限
            'sub' => $userId,        // ユーザーID
        ];

        return JWT::encode($payload, $secret, $algorithm);
    }

    /**
     * リフレッシュトークンを生成
     */
    public static function generateRefreshToken(int $userId, string $secret, int $expiry = 2592000, string $algorithm = 'HS256'): string
    {
        $issuedAt = time();
        $expire = $issuedAt + $expiry;

        $payload = [
            'iat' => $issuedAt,     // 発行時刻
            'exp' => $expire,        // 有効期限（30日）
            'sub' => $userId,        // ユーザーID
            'type' => 'refresh'      // トークンタイプ
        ];

        return JWT::encode($payload, $secret, $algorithm);
    }
}
