<?php
declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\{ResponseInterface as Response, ServerRequestInterface as Request};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

class CorsMiddleware implements MiddlewareInterface
{
    private array $allowedOrigins;
    private array $allowedMethods;
    private array $allowedHeaders;
    private int $maxAge;
    private bool $allowCredentials;

    public function __construct(
        array $allowedOrigins = ['*'],
        array $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        array $allowedHeaders = ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept'],
        int $maxAge = 3600,
        bool $allowCredentials = true
    ) {
        $this->allowedOrigins = $allowedOrigins;
        $this->allowedMethods = $allowedMethods;
        $this->allowedHeaders = $allowedHeaders;
        $this->maxAge = $maxAge;
        $this->allowCredentials = $allowCredentials;
    }

    /**
     * ミドルウェアの処理を実行
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $response = $handler->handle($request);

        // リクエスト元のオリジンを取得
        $origin = $request->getHeaderLine('Origin');

        // オリジンが許可されているか確認
        if ($this->isOriginAllowed($origin)) {
            $response = $response
                ->withHeader('Access-Control-Allow-Origin', $origin)
                ->withHeader('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods))
                ->withHeader('Access-Control-Allow-Headers', implode(', ', $this->allowedHeaders))
                ->withHeader('Access-Control-Max-Age', (string)$this->maxAge);

            if ($this->allowCredentials) {
                $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
            }

            // Vary ヘッダーを追加
            $response = $response->withAddedHeader('Vary', 'Origin');
        }

        // プリフライトリクエストの場合は、空のレスポンスを返す
        if ($request->getMethod() === 'OPTIONS') {
            return $response->withStatus(204);
        }

        return $response;
    }

    /**
     * オリジンが許可されているか確認
     */
    private function isOriginAllowed(string $origin): bool
    {
        // ワイルドカードが許可されている場合
        if (in_array('*', $this->allowedOrigins)) {
            return true;
        }

        // 具体的なオリジンをチェック
        return in_array($origin, $this->allowedOrigins);
    }

    /**
     * 許可するオリジンを設定
     */
    public function setAllowedOrigins(array $origins): void
    {
        $this->allowedOrigins = $origins;
    }

    /**
     * 許可するメソッドを設定
     */
    public function setAllowedMethods(array $methods): void
    {
        $this->allowedMethods = $methods;
    }

    /**
     * 許可するヘッダーを設定
     */
    public function setAllowedHeaders(array $headers): void
    {
        $this->allowedHeaders = $headers;
    }

    /**
     * キャッシュ時間を設定
     */
    public function setMaxAge(int $maxAge): void
    {
        $this->maxAge = $maxAge;
    }

    /**
     * クレデンシャルの許可を設定
     */
    public function setAllowCredentials(bool $allow): void
    {
        $this->allowCredentials = $allow;
    }

    /**
     * セキュリティヘッダーを追加
     */
    private function addSecurityHeaders(Response $response): Response
    {
        return $response
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('X-XSS-Protection', '1; mode=block')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
