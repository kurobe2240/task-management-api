<?php
declare(strict_types=1);

namespace App\Exceptions;

class ValidationException extends \Exception
{
    private array $errors;

    /**
     * @param array $errors バリデーションエラーの配列
     * @param string $message エラーメッセージ
     * @param int $code HTTPステータスコード
     * @param \Throwable|null $previous 前の例外
     */
    public function __construct(
        array $errors,
        string $message = 'バリデーションエラーが発生しました。',
        int $code = 422,
        \Throwable $previous = null
    ) {
        $this->errors = $errors;
        parent::__construct($message, $code, $previous);
    }

    /**
     * エラー配列を取得
     */
    public function getErrors(): array
    {
        return $this->errors;
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
            'type' => 'validation_error',
            'errors' => $this->errors
        ];
    }

    /**
     * 単一のエラーメッセージから例外を生成
     */
    public static function forError(string $field, string $message): self
    {
        return new self([$field => $message]);
    }

    /**
     * 必須フィールドのエラーを生成
     */
    public static function forRequired(string $field): self
    {
        return self::forError($field, sprintf('%sは必須です。', $field));
    }

    /**
     * 無効な値のエラーを生成
     */
    public static function forInvalid(string $field, string $reason = null): self
    {
        $message = sprintf('%sが無効です。', $field);
        if ($reason) {
            $message .= ' ' . $reason;
        }
        return self::forError($field, $message);
    }

    /**
     * 重複エラーを生成
     */
    public static function forDuplicate(string $field): self
    {
        return self::forError($field, sprintf('この%sは既に使用されています。', $field));
    }

    /**
     * 文字数制限エラーを生成
     */
    public static function forLength(string $field, int $min = null, int $max = null): self
    {
        if ($min && $max) {
            $message = sprintf('%sは%d文字以上%d文字以下である必要があります。', $field, $min, $max);
        } elseif ($min) {
            $message = sprintf('%sは%d文字以上である必要があります。', $field, $min);
        } elseif ($max) {
            $message = sprintf('%sは%d文字以下である必要があります。', $field, $max);
        } else {
            $message = sprintf('%sの文字数が無効です。', $field);
        }
        return self::forError($field, $message);
    }

    /**
     * 数値範囲エラーを生成
     */
    public static function forRange(string $field, $min = null, $max = null): self
    {
        if ($min !== null && $max !== null) {
            $message = sprintf('%sは%sから%sの間である必要があります。', $field, $min, $max);
        } elseif ($min !== null) {
            $message = sprintf('%sは%s以上である必要があります。', $field, $min);
        } elseif ($max !== null) {
            $message = sprintf('%sは%s以下である必要があります。', $field, $max);
        } else {
            $message = sprintf('%sの値が無効です。', $field);
        }
        return self::forError($field, $message);
    }

    /**
     * 日付形式エラーを生成
     */
    public static function forInvalidDate(string $field): self
    {
        return self::forError($field, sprintf('%sは有効な日付形式である必要があります。', $field));
    }

    /**
     * メールアドレス形式エラーを生成
     */
    public static function forInvalidEmail(string $field = 'email'): self
    {
        return self::forError($field, '有効なメールアドレスを入力してください。');
    }

    /**
     * パスワード形式エラーを生成
     */
    public static function forInvalidPassword(string $field = 'password'): self
    {
        return self::forError(
            $field,
            'パスワードは8文字以上で、大文字、小文字、数字を含める必要があります。'
        );
    }
}
