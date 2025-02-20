<?php
declare(strict_types=1);

namespace App\Models;

class Task
{
    private ?int $id = null;
    private int $projectId;
    private string $title;
    private ?string $description = null;
    private string $status = 'pending';
    private string $priority = 'medium';
    private ?int $assignedTo = null;
    private ?string $dueDate = null;
    private int $progress = 0;
    private int $createdBy;
    private ?int $statusChangedBy = null;
    private ?string $statusChangedAt = null;
    private ?string $assignedAt = null;
    private string $createdAt;
    private string $updatedAt;
    private ?string $deletedAt = null;

    // ステータス定義
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ON_HOLD = 'on_hold';

    // 優先度定義
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';

    /**
     * 配列からモデルを生成
     */
    public static function fromArray(array $data): self
    {
        $task = new self();
        
        foreach ($data as $key => $value) {
            if (property_exists($task, $key)) {
                $task->$key = $value;
            }
        }
        
        return $task;
    }

    /**
     * モデルを配列に変換
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->projectId,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'assigned_to' => $this->assignedTo,
            'due_date' => $this->dueDate,
            'progress' => $this->progress,
            'created_by' => $this->createdBy,
            'status_changed_by' => $this->statusChangedBy,
            'status_changed_at' => $this->statusChangedAt,
            'assigned_at' => $this->assignedAt,
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
            'project_id' => [
                'required' => true,
                'type' => 'integer'
            ],
            'title' => [
                'required' => true,
                'type' => 'string',
                'max' => 255
            ],
            'description' => [
                'type' => 'string',
                'max' => 1000
            ],
            'status' => [
                'type' => 'enum',
                'values' => [
                    self::STATUS_PENDING,
                    self::STATUS_IN_PROGRESS,
                    self::STATUS_COMPLETED,
                    self::STATUS_ON_HOLD
                ]
            ],
            'priority' => [
                'type' => 'enum',
                'values' => [
                    self::PRIORITY_LOW,
                    self::PRIORITY_MEDIUM,
                    self::PRIORITY_HIGH
                ]
            ],
            'assigned_to' => [
                'type' => 'integer',
                'nullable' => true
            ],
            'due_date' => [
                'type' => 'date',
                'nullable' => true
            ],
            'progress' => [
                'type' => 'integer',
                'min' => 0,
                'max' => 100
            ]
        ];
    }

    /**
     * ステータスを更新
     */
    public function updateStatus(string $status, int $userId): void
    {
        if (!in_array($status, [
            self::STATUS_PENDING,
            self::STATUS_IN_PROGRESS,
            self::STATUS_COMPLETED,
            self::STATUS_ON_HOLD
        ])) {
            throw new \InvalidArgumentException('無効なステータスです。');
        }

        $this->status = $status;
        $this->statusChangedBy = $userId;
        $this->statusChangedAt = date('Y-m-d H:i:s');

        // タスクが完了した場合、進捗を100%に設定
        if ($status === self::STATUS_COMPLETED) {
            $this->progress = 100;
        }
    }

    /**
     * 担当者を設定
     */
    public function assignTo(?int $userId): void
    {
        $this->assignedTo = $userId;
        $this->assignedAt = $userId ? date('Y-m-d H:i:s') : null;
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
        }
        // 進捗が100%未満の場合で、ステータスが完了の場合は進行中に変更
        elseif ($progress < 100 && $this->status === self::STATUS_COMPLETED) {
            $this->status = self::STATUS_IN_PROGRESS;
        }
    }

    /**
     * 優先度を設定
     */
    public function setPriority(string $priority): void
    {
        if (!in_array($priority, [
            self::PRIORITY_LOW,
            self::PRIORITY_MEDIUM,
            self::PRIORITY_HIGH
        ])) {
            throw new \InvalidArgumentException('無効な優先度です。');
        }

        $this->priority = $priority;
    }

    /**
     * 期限切れかどうかを確認
     */
    public function isOverdue(): bool
    {
        if (!$this->dueDate || $this->status === self::STATUS_COMPLETED) {
            return false;
        }

        return strtotime($this->dueDate) < time();
    }

    /**
     * 完了しているかどうかを確認
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    // Getter methods
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function getAssignedTo(): ?int
    {
        return $this->assignedTo;
    }

    public function getDueDate(): ?string
    {
        return $this->dueDate;
    }

    public function getProgress(): int
    {
        return $this->progress;
    }

    public function getCreatedBy(): int
    {
        return $this->createdBy;
    }

    public function getStatusChangedBy(): ?int
    {
        return $this->statusChangedBy;
    }

    public function getStatusChangedAt(): ?string
    {
        return $this->statusChangedAt;
    }

    public function getAssignedAt(): ?string
    {
        return $this->assignedAt;
    }

    // Setter methods
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function setDueDate(?string $dueDate): void
    {
        $this->dueDate = $dueDate;
    }
}
