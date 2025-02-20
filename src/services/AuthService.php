<?php
declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AuthenticationException;
use App\Exceptions\ValidationException;
use App\Repository\UserRepository;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthService implements IAuthService
{
    private UserRepository $userRepository;
    private string $jwtSecret;
    private string $jwtRefreshSecret;
    private int $jwtTtl;
    private int $jwtRefreshTtl;

    public function __construct(
        UserRepository $userRepository,
        string $jwtSecret,
        string $jwtRefreshSecret,
        int $jwtTtl = 3600,
        int $jwtRefreshTtl = 604800
    ) {
        $this->userRepository = $userRepository;
        $this->jwtSecret = $jwtSecret;
        $this->jwtRefreshSecret = $jwtRefreshSecret;
        $this->jwtTtl = $jwtTtl;
        $this->jwtRefreshTtl = $jwtRefreshTtl;
    }

    /**
     * @inheritDoc
     */
    public function authenticate(string $email, string $password): array
    {
        $user = $this->userRepository->findByEmail($email);
        
        if (!$user || !password_verify($password, $user['password'])) {
            throw new AuthenticationException('メールアドレスまたはパスワードが正しくありません。');
        }

        return [
            'user' => array_diff_key($user, ['password' => '']),
            'tokens' => $this->generateTokens($user['id'])
        ];
    }

    /**
     * @inheritDoc
     */
    public function register(array $userData): array
    {
        // メールアドレスの重複チェック
        if ($this->userRepository->findByEmail($userData['email'])) {
            throw new ValidationException(['email' => 'このメールアドレスは既に登録されています。']);
        }

        // パスワードのハッシュ化
        $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);

        // ユーザーを作成
        $user = $this->userRepository->create($userData);

        return [
            'user' => array_diff_key($user, ['password' => '']),
            'tokens' => $this->generateTokens($user['id'])
        ];
    }

    /**
     * @inheritDoc
     */
    public function initiatePasswordReset(string $email): ?string
    {
        $user = $this->userRepository->findByEmail($email);
        
        if (!$user) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = time() + 3600; // 1時間有効

        // トークンを保存
        $this->userRepository->saveResetToken($user['id'], $token, $expiresAt);

        return $token;
    }

    /**
     * @inheritDoc
     */
    public function resetPassword(string $token, string $newPassword): bool
    {
        $resetInfo = $this->userRepository->findResetToken($token);

        if (!$resetInfo || $resetInfo['expires_at'] < time()) {
            throw new ValidationException(['token' => 'パスワードリセットトークンが無効または期限切れです。']);
        }

        // パスワードをハッシュ化して更新
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->userRepository->updatePassword($resetInfo['user_id'], $hashedPassword);

        // 使用済みトークンを削除
        $this->userRepository->deleteResetToken($token);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function refreshToken(string $refreshToken): array
    {
        try {
            $payload = JWT::decode($refreshToken, new Key($this->jwtRefreshSecret, 'HS256'));
            
            if (!isset($payload->user_id)) {
                throw new AuthenticationException('無効なリフレッシュトークンです。');
            }

            $user = $this->userRepository->findById($payload->user_id);
            if (!$user) {
                throw new AuthenticationException('ユーザーが見つかりません。');
            }

            return $this->generateTokens($user['id']);

        } catch (\Exception $e) {
            throw new AuthenticationException('リフレッシュトークンが無効です。');
        }
    }

    /**
     * アクセストークンとリフレッシュトークンを生成する
     */
    private function generateTokens(int $userId): array
    {
        $now = time();

        // アクセストークンを生成
        $accessToken = JWT::encode([
            'user_id' => $userId,
            'iat' => $now,
            'exp' => $now + $this->jwtTtl
        ], $this->jwtSecret, 'HS256');

        // リフレッシュトークンを生成
        $refreshToken = JWT::encode([
            'user_id' => $userId,
            'iat' => $now,
            'exp' => $now + $this->jwtRefreshTtl
        ], $this->jwtRefreshSecret, 'HS256');

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $this->jwtTtl
        ];
    }

    /**
     * トークンを検証する
     * @throws AuthenticationException トークンが無効な場合
     */
    public function verifyToken(string $token): array
    {
        try {
            $payload = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            
            if (!isset($payload->user_id)) {
                throw new AuthenticationException('無効なトークンです。');
            }

            $user = $this->userRepository->findById($payload->user_id);
            if (!$user) {
                throw new AuthenticationException('ユーザーが見つかりません。');
            }

            return array_diff_key($user, ['password' => '']);

        } catch (\Exception $e) {
            throw new AuthenticationException('トークンが無効です。');
        }
    }
}
