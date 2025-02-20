<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Exceptions\ValidationException;
use Psr\Http\Message\{ResponseInterface as Response, ServerRequestInterface as Request};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use Psr\Log\LoggerInterface;

class JsonBodyParserMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;
    private int $maxBodySize;

    public function __construct(
        LoggerInterface $logger,
        int $maxBodySize = 10485760 // デフォルト: 10MB
    ) {
        $this->logger = $logger;
        $this->maxBodySize = $maxBodySize;
    }

    /**
     * ミドルウェアの処理を実行
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $contentType = $request->getHeaderLine('Content-Type');

        // Content-Typeがapplication/jsonの場合のみ処理
        if (strpos($contentType, 'application/json') !== false) {
            $contents = $request->getBody()->getContents();

            // ボディサイズの検証
            if (strlen($contents) > $this->maxBodySize) {
                throw new ValidationException([
                    'message' => 'リクエストボディが大きすぎます。'
                ]);
            }

            // 空のボディは許可
            if (empty($contents)) {
                return $handler->handle($request);
            }

            // JSONデコード
            $json = json_decode($contents, true);

            // JSONデコードエラーの処理
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->warning('JSONデコードエラー', [
                    'error' => json_last_error_msg(),
                    'content' => $contents
                ]);

                throw new ValidationException([
                    'message' => '無効なJSONフォーマットです。'
                ]);
            }

            // デコードされたJSONをリクエストに設定
            $request = $request->withParsedBody($json);

            // ストリームを巻き戻し
            $request->getBody()->rewind();
        }

        return $handler->handle($request);
    }

    /**
     * 最大ボディサイズを設定
     */
    public function setMaxBodySize(int $size): void
    {
        $this->maxBodySize = $size;
    }

    /**
     * JSONデータを検証
     */
    private function validateJson($data): void
    {
        if (!is_array($data)) {
            throw new ValidationException([
                'message' => 'JSONデータは配列またはオブジェクトである必要があります。'
            ]);
        }

        // 再帰的にデータを検証
        array_walk_recursive($data, function ($value) {
            if (is_resource($value)) {
                throw new ValidationException([
                    'message' => 'JSONデータにリソースは含められません。'
                ]);
            }
        });
    }

    /**
     * エンコード時のオプションを取得
     */
    private function getEncodingOptions(): int
    {
        return JSON_UNESCAPED_SLASHES 
            | JSON_UNESCAPED_UNICODE 
            | JSON_PRESERVE_ZERO_FRACTION
            | JSON_INVALID_UTF8_IGNORE;
    }

    /**
     * JSONをエンコード
     */
    public static function encodeJson($data): string
    {
        $options = JSON_UNESCAPED_SLASHES 
            | JSON_UNESCAPED_UNICODE 
            | JSON_PRESERVE_ZERO_FRACTION
            | JSON_INVALID_UTF8_IGNORE;

        $json = json_encode($data, $options);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ValidationException([
                'message' => 'JSONエンコードエラー: ' . json_last_error_msg()
            ]);
        }

        return $json;
    }

    /**
     * JSONをデコード
     */
    public static function decodeJson(string $json, bool $assoc = true)
    {
        $data = json_decode($json, $assoc);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ValidationException([
                'message' => 'JSONデコードエラー: ' . json_last_error_msg()
            ]);
        }

        return $data;
    }
}
