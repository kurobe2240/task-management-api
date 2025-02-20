<?php
declare(strict_types=1);

namespace App\Services;

interface IAuthService
{
    /**
     * ユーザー認証を行う
     * @param string $email メールアドレス
     * @param string $password パスワード
     * @return array アクセストークンと更新トークンを含む配列
     * @throws AuthenticationException 認証失敗時
     */
    public function authenticate(string $email, string $password): array;

    /**
     * ユーザーを登録する
     * @param array $userData ユーザーデータ
     * @return array 登録されたユーザー情報
     * @throws ValidationException バリデーション失敗時
     */
    public function register(array $userData): array;

    /**
     * パスワードリセットプロセスを開始する
     * @param string $email メールアドレス
     * @return string|null リセットトークン（メール送信用）
     */
    public function initiatePasswordReset(string $email): ?string;

    /**
     * パスワードをリセットする
     * @param string $token リセットトークン
     * @param string $newPassword 新しいパスワード
     * @return bool 成功時true
     * @throws ValidationException トークンが無効な場合
     */
    public function resetPassword(string $token, string $newPassword): bool;

    /**
     * リフレッシュトークンを使用して新しいアクセストークンを生成する
     * @param string $refreshToken リフレッシュトークン
     * @return array 新しいアクセストークンと更新トークンを含む配列
     * @throws AuthenticationException トークンが無効な場合
     */
    public function refreshToken(string $refreshToken): array;
}
