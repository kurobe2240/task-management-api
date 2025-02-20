<?php
declare(strict_types=1);

namespace App\Models;

class Project
{
    private ?int $id = null;
    private string $name;
    private ?string $description = null;
    private string $status = 'planning';
    private ?string $startDate = null;
    private ?string $endDate = null;
    private int $progress = 0;
    private int $createdBy;
    private ?string $completedAt = null;
    private ?int $completedBy = null;
    private string $createdAt;
    private string $updatedAt;
    private ?string $deletedAt = null;

    // ステータス定義
    public const STATUS_PLANNING = 'planning';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ON_HOLD = 'on_hold';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    // メンバーロール定義
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MEMBER = 'member';
    public const ROLE_VIEWER = 'viewer';

    /**
     * 配列からモデルを生成
     */
    public static function fromArray(array $data): self
    {
        $project = new self();
        
        foreach ($data as $key => $value) {
            if (property_exists($project, $key)) {
                $project->$key = $value;
            }
        }
        
        return $project;
    }

    /**
     * モデルを配列に変換
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'progress' => $this->progress,
            'created_by' => $this->createdBy,
            'completed_at' => $this->completedAt,
            'completed_by' => $this->completedBy,
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
            'description' => [
                'type' => 'string',
                'max' => 1000
            ],
            'status' => [
                'type' => 'enum',
                'values' => [
                    self::STATUS_PLANNING,
                    self::STATUS_ACTIVE,
                    self::STATUS_ON_HOLD,
                    self::STATUS_COMPLETED,
                    self::STATUS_CANCELLED
                ]
            ],
            'start_date' => [
                'type' => 'date',
                'nullable' => true
            ],
            'end_date' => [
                'type' => 'date',
                'nullable' => true
            ]
        ];
    }

    /**
     * プロジェクトメンバーのロールバリデーションルール
     */
    public static function getMemberRoleValidationRules(): array
    {
        return [
            'role' => [
                'required' => true,
                'type' => 'enum',
                'values' => [
                    self::ROLE_ADMIN,
                    self::ROLE_MEMBER,
                    self::ROLE_VIEWER
                ]
            ]
        ];
    }

    /**
     * ステータスを更新
     */
    public function updateStatus(string $status, int $userId): void
    {
        if (!in_array($status, [
            self::STATUS_PLANNING,
            self::STATUS_ACTIVE,
            self::STATUS_ON_HOLD,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED
        ])) {
            throw new \InvalidArgumentException('無効なステータスです。');
        }

        $this->status = $status;

        if ($status === self::STATUS_COMPLETED) {
            $this->completedAt = date('Y-m-d H:i:s');
            $this->completedBy = $userId;
            $this->progress = 100;
        }
    }

    /**
     * 進捗を更新
     */
    public function updateProgress(int $progress): void
    {
        if ($progress < 0 || $progress > 100) {
            throw new \InvalidArgumentException('進捗は0から100の間である必要があります。');
        }

        $this->progress = $progress;

        // 進捗が100%の場合、ステータスを完了に設定
        if ($progress === 100 && $this->status !== self::STATUS_COMPLETED) {
            $this->status = self::STATUS_COMPLETED;
            $this->completedAt = date('Y-m-d H:i:s');
        }
        // 進捗が100%未満の場合で、ステータスが完了の場合は進行中に変更
        elseif ($progress < 100 && $this->status === self::STATUS_COMPLETED) {
            $this->status = self::STATUS_ACTIVE;
            $this->completedAt = null;
            $this->completedBy = null;
        }
    }

    /**
     * プロジェクトが有効か確認（キャンセルされていないか）
     */
    public function isActive(): bool
    {
        return $this->status !== self::STATUS_CANCELLED;
    }

    /**
     * プロジェクトが完了しているか確認
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * プロジェクトが期限切れかどうかを確認
     */
    public function isOverdue(): bool
    {
        if (!$this->endDate || $this->status === self::STATUS_COMPLETED) {
            return false;
        }

        return strtotime($this->endDate) < time();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getStartDate(): ?string
    {
        return $this->startDate;
    }

    public function getEndDate(): ?string
    {
        return $this->endDate;
    }

    public function getProgress(): int
    {
        return $this->progress;
    }

    public function getCreatedBy(): int
    {
        return $this->createdBy;
    }

    public function getCompletedAt(): ?string
    {
        return $this->completedAt;
    }

    public function getCompletedBy(): ?int
    {
        return $this->completedBy;
    }

    // Setter methods
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function setStartDate(?string $startDate): void
    {
        $this->startDate = $startDate;
    }

    public function setEndDate(?string $endDate): void
    {
        $this->endDate = $endDate;
        
        // 終了日が設定され、開始日が設定されていない場合は開始日を現在日に設定
        if ($endDate && !$this->startDate) {
            $this->startDate = date('Y-m-d');
        }
    }
}
