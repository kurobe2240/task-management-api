<?php
declare(strict_types=1);

namespace App\Models;

class User
{
    private ?int $id = null;
    private string $name;
    private string $email;
    private string $password;
    private string $status = 'active';
    private ?string $resetToken = null;
    private ?string $resetTokenExpiresAt = null;
    private ?string $lastLoginAt = null;
    private ?string $lastLoginIp = null;
    private ?string $deactivatedAt = null;
    private string $createdAt;
    private string $updatedAt;
    private ?string $deletedAt = null;

    /**
     * 配列からモデルを生成
     */
    public static function fromArray(array $data): self
    {
        $user = new self();
        
        foreach ($data as $key => $value) {
            if (property_exists($user, $key)) {
                $user->$key = $value;
            }
        }
        
        return $user;
    }

    /**
     * モデルを配列に変換
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'last_login_at' => $this->lastLoginAt,
            'last_login_ip' => $this->lastLoginIp,
            'deactivated_at' => $this->deactivatedAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt
        ];
    }

    /**
     * バリデーションルールを取得
     */
    public static function getValidationRules(): array
    {
        return [
            'name' => [
                'required' => true,
                'type' => 'string',
                'max' => 100
            ],
            'email' => [
                'required' => true,
                'type' => 'email',
                'max' => 255,
                'unique' => 'users'
            ],
            'password' => [
                'required' => true,
                'type' => 'string',
                'min' => 8,
                'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'
            ],
            'status' => [
                'type' => 'enum',
                'values' => ['active', 'inactive']
            ]
        ];
    }

    /**
     * パスワードをハッシュ化
     */
    public function setPassword(string $password): void
    {
        $this->password = password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * パスワードを検証
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    /**
     * リセットトークンを生成
     */
    public function generateResetToken(): void
    {
        $this->resetToken = bin2hex(random_bytes(32));
        $this->resetTokenExpiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
    }

    /**
     * リセットトークンを検証
     */
    public function verifyResetToken(string $token): bool
    {
        if ($this->resetToken !== $token) {
            return false;
        }

        if (strtotime($this->resetTokenExpiresAt) < time()) {
            return false;
        }

        return true;
    }

    /**
     * リセットトークンをクリア
     */
    public function clearResetToken(): void
    {
        $this->resetToken = null;
        $this->resetTokenExpiresAt = null;
    }

    /**
     * アカウントを無効化
     */
    public function deactivate(): void
    {
        $this->status = 'inactive';
        $this->deactivatedAt = date('Y-m-d H:i:s');
    }

    /**
     * アカウントを有効化
     */
    public function activate(): void
    {
        $this->status = 'active';
        $this->deactivatedAt = null;
    }

    /**
     * アカウントが有効か確認
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * 最終ログイン情報を更新
     */
    public function updateLastLogin(string $ipAddress): void
    {
        $this->lastLoginAt = date('Y-m-d H:i:s');
        $this->lastLoginIp = $ipAddress;
    }

    // Getter methods
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function getResetTokenExpiresAt(): ?string
    {
        return $this->resetTokenExpiresAt;
    }

    public function getLastLoginAt(): ?string
    {
        return $this->lastLoginAt;
    }

    public function getLastLoginIp(): ?string
    {
        return $this->lastLoginIp;
    }

    public function getDeactivatedAt(): ?string
    {
        return $this->deactivatedAt;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?string
    {
        return $this->deletedAt;
    }

    // Setter methods
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }
}
