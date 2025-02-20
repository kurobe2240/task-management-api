<?php
declare(strict_types=1);

namespace App\Exceptions;

class AuthorizationException extends \Exception
{
    protected $code = 403;

    /**
     * @param string $message エラーメッセージ
     * @param int $code HTTPステータスコード
     * @param \Throwable|null $previous 前の例外
     */
    public function __construct(
        string $message = '権限がありません。',
        int $code = 403,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * エラーレスポンスの配列表現を取得
     */
    public function toArray(): array
    {
        return [
            'status' => 'error',
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'type' => 'authorization_error'
        ];
    }

    /**
     * リソースに対する権限エラーを生成
     */
    public static function forResource(string $resource, string $action): self
    {
        return new self(
            sprintf(
                '%sの%sを行う権限がありません。',
                $resource,
                $action
            )
        );
    }

    /**
     * ロールに対する権限エラーを生成
     */
    public static function forRole(string $requiredRole): self
    {
        return new self(
            sprintf(
                'この操作には%sロールが必要です。',
                $requiredRole
            )
        );
    }

    /**
     * 所有者に対する権限エラーを生成
     */
    public static function forOwnership(string $resource): self
    {
        return new self(
            sprintf(
                'この%sの所有者ではありません。',
                $resource
            )
        );
    }
}
