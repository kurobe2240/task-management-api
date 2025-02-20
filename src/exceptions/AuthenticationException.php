<?php
declare(strict_types=1);

namespace App\Exceptions;

class AuthenticationException extends \Exception
{
    protected $code = 401;

    /**
     * @param string $message エラーメッセージ
     * @param int $code HTTPステータスコード
     * @param \Throwable|null $previous 前の例外
     */
    public function __construct(
        string $message = '認証に失敗しました。',
        int $code = 401,
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
            'code' => $this->getCode()
        ];
    }
}
