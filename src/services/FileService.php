<?php
declare(strict_types=1);

namespace App\Services;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;
use App\Exceptions\ValidationException;

class FileService
{
    private PDO $db;
    private LoggerInterface $logger;
    private string $uploadDir;

    public function __construct(PDO $db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
        $this->uploadDir = dirname(__DIR__, 2) . '/public/uploads/';
        
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * ファイルをアップロード
     */
    public function uploadTaskFile(array $file, int $taskId, int $userId): array
    {
        try {
            // ファイルのバリデーション
            $this->validateFile($file);

            // ファイル名の生成
            $filename = $this->generateUniqueFilename($file['name']);
            $filePath = $this->uploadDir . $filename;

            // ファイルの移動
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                throw new \RuntimeException('ファイルのアップロードに失敗しました。');
            }

            // データベースに記録
            $query = "
                INSERT INTO task_attachments (
                    task_id, user_id, filename, file_path,
                    file_size, mime_type, created_at
                ) VALUES (
                    :task_id, :user_id, :filename, :file_path,
                    :file_size, :mime_type, :created_at
                )
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                'task_id' => $taskId,
                'user_id' => $userId,
                'filename' => $file['name'],
                'file_path' => $filename,
                'file_size' => $file['size'],
                'mime_type' => $file['type'],
                'created_at' => date('Y-m-d H:i:s')
            ]);

            return [
                'id' => (int)$this->db->lastInsertId(),
                'filename' => $file['name'],
                'size' => $file['size'],
                'mime_type' => $file['type']
            ];
        } catch (\Exception $e) {
            $this->logger->error('ファイルアップロードエラー', [
                'error' => $e->getMessage(),
                'task_id' => $taskId,
                'user_id' => $userId
            ]);
            throw $e;
        }
    }

    /**
     * タスクの添付ファイルを取得
     */
    public function getTaskFiles(int $taskId): array
    {
        try {
            $query = "
                SELECT 
                    id, filename, file_size as size, mime_type,
                    created_at
                FROM task_attachments
                WHERE task_id = :task_id
                ORDER BY created_at DESC
            ";

            $stmt = $this->db->prepare($query);
            $stmt->execute(['task_id' => $taskId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'task_id' => $taskId
            ]);
            throw $e;
        }
    }

    /**
     * 添付ファイルを削除
     */
    public function deleteFile(int $fileId, int $userId): bool
    {
        try {
            // ファイル情報の取得
            $query = "
                SELECT * FROM task_attachments
                WHERE id = :id
            ";
            $stmt = $this->db->prepare($query);
            $stmt->execute(['id' => $fileId]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$file) {
                throw new \RuntimeException('ファイルが見つかりません。');
            }

            // 物理ファイルの削除
            $filePath = $this->uploadDir . $file['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // データベースから削除
            $query = "
                DELETE FROM task_attachments
                WHERE id = :id
            ";
            $stmt = $this->db->prepare($query);
            return $stmt->execute(['id' => $fileId]);
        } catch (\Exception $e) {
            $this->logger->error('ファイル削除エラー', [
                'error' => $e->getMessage(),
                'file_id' => $fileId,
                'user_id' => $userId
            ]);
            throw $e;
        }
    }

    /**
     * ファイルのバリデーション
     */
    private function validateFile(array $file): void
    {
        $errors = [];

        // ファイルサイズのチェック（最大10MB）
        $maxSize = 10 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            $errors['size'] = 'ファイルサイズは10MB以下である必要があります。';
        }

        // MIMEタイプのチェック
        $allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain'
        ];

        if (!in_array($file['type'], $allowedTypes)) {
            $errors['type'] = '許可されていないファイル形式です。';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    /**
     * ユニークなファイル名を生成
     */
    private function generateUniqueFilename(string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        return uniqid() . '_' . time() . '.' . $extension;
    }
}
