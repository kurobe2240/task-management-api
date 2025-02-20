<?php
declare(strict_types=1);

namespace App\Repository;

use App\Exceptions\AuthenticationException;
use App\Exceptions\ValidationException;
use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

class UserRepository extends AbstractRepository
{
    protected string $table = 'users';

    /**
     * ユーザーを認証
     */
    public function authenticate(string $email, string $password): ?array
    {
        try {
            $user = $this->findOneBy(['email' => $email]);

            if (!$user) {
                throw new AuthenticationException('メールアドレスまたはパスワードが正しくありません。');
            }

            if (!password_verify($password, $user['password'])) {
                throw new AuthenticationException('メールアドレスまたはパスワードが正しくありません。');
            }

            if (!$user['is_active']) {
                throw new AuthenticationException('このアカウントは無効化されています。');
            }

            // パスワードハッシュを除外
            unset($user['password']);

            return $user;
        } catch (PDOException $e) {
            $this->logger->error('認証エラー', [
                'message' => $e->getMessage(),
                'email' => $email
            ]);
            throw $e;
        }
    }

    /**
     * メールアドレスでユーザーを検索
     */
    public function findByEmail(string $email): ?array
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * ユーザーを作成
     */
    public function create(array $data): int
    {
        // メールアドレスの重複チェック
        if ($this->findByEmail($data['email'])) {
            throw new ValidationException(['email' => 'このメールアドレスは既に使用されています。']);
        }

        // パスワードをハッシュ化
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        return parent::create($data);
    }

    /**
     * ユーザーを更新
     */
    public function update(int $id, array $data): bool
    {
        // メールアドレスの変更がある場合は重複チェック
        if (isset($data['email'])) {
            $existingUser = $this->findByEmail($data['email']);
            if ($existingUser && $existingUser['id'] !== $id) {
                throw new ValidationException(['email' => 'このメールアドレスは既に使用されています。']);
            }
        }

        // パスワードの更新がある場合はハッシュ化
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        return parent::update($id, $data);
    }

    /**
     * ユーザーのパスワードを更新
     */
    public function updatePassword(int $id, string $currentPassword, string $newPassword): bool
    {
        $user = $this->find($id);

        if (!$user) {
            throw new ValidationException(['user' => 'ユーザーが見つかりません。']);
        }

        if (!password_verify($currentPassword, $user['password'])) {
            throw new ValidationException(['current_password' => '現在のパスワードが正しくありません。']);
        }

        return $this->update($id, ['password' => $newPassword]);
    }

    /**
     * ユーザーの状態を更新
     */
    public function updateStatus(int $id, bool $isActive): bool
    {
        return $this->update($id, ['is_active' => $isActive]);
    }

    /**
     * ユーザーの最終ログイン日時を更新
     */
    public function updateLastLogin(int $id): bool
    {
        return $this->update($id, ['last_login_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * パスワードリセットトークンを生成
     */
    public function createPasswordResetToken(string $email): ?string
    {
        $user = $this->findByEmail($email);

        if (!$user) {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $this->update($user['id'], [
            'reset_token' => $token,
            'reset_token_expires_at' => $expires
        ]);

        return $token;
    }

    /**
     * パスワードリセットトークンを検証
     */
    public function verifyResetToken(string $token): ?array
    {
        $user = $this->findOneBy(['reset_token' => $token]);

        if (!$user) {
            return null;
        }

        if (strtotime($user['reset_token_expires_at']) < time()) {
            return null;
        }

        return $user;
    }

    /**
     * パスワードリセットトークンを無効化
     */
    public function clearResetToken(int $id): bool
    {
        return $this->update($id, [
            'reset_token' => null,
            'reset_token_expires_at' => null
        ]);
    }
}
