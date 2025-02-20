<?php
declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;
use Psr\Log\LoggerInterface;

abstract class AbstractRepository
{
    protected PDO $db;
    protected LoggerInterface $logger;
    protected string $table;

    public function __construct(PDO $db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * レコードを取得
     */
    public function find(int $id): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id AND deleted_at IS NULL");
            $stmt->execute(['id' => $id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: null;
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'table' => $this->table,
                'id' => $id
            ]);
            throw $e;
        }
    }

    /**
     * 条件に一致するレコードを取得
     */
    public function findBy(array $criteria, array $orderBy = null, int $limit = null, int $offset = null): array
    {
        try {
            $query = "SELECT * FROM {$this->table} WHERE deleted_at IS NULL";
            $params = [];

            // WHERE句を構築
            foreach ($criteria as $key => $value) {
                $query .= " AND $key = :$key";
                $params[$key] = $value;
            }

            // ORDER BY句を追加
            if ($orderBy) {
                $query .= " ORDER BY ";
                $orders = [];
                foreach ($orderBy as $column => $direction) {
                    $orders[] = "$column $direction";
                }
                $query .= implode(', ', $orders);
            }

            // LIMIT句を追加
            if ($limit) {
                $query .= " LIMIT :limit";
                $params['limit'] = $limit;
            }

            // OFFSET句を追加
            if ($offset) {
                $query .= " OFFSET :offset";
                $params['offset'] = $offset;
            }

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'table' => $this->table,
                'criteria' => $criteria
            ]);
            throw $e;
        }
    }

    /**
     * 条件に一致する最初のレコードを取得
     */
    public function findOneBy(array $criteria): ?array
    {
        $result = $this->findBy($criteria, null, 1);
        return $result ? $result[0] : null;
    }

    /**
     * 全レコードを取得
     */
    public function findAll(array $orderBy = null, int $limit = null, int $offset = null): array
    {
        return $this->findBy([], $orderBy, $limit, $offset);
    }

    /**
     * レコードを作成
     */
    public function create(array $data): int
    {
        try {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');

            $columns = implode(', ', array_keys($data));
            $values = ':' . implode(', :', array_keys($data));

            $query = "INSERT INTO {$this->table} ($columns) VALUES ($values)";
            $stmt = $this->db->prepare($query);
            $stmt->execute($data);

            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'table' => $this->table,
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * レコードを更新
     */
    public function update(int $id, array $data): bool
    {
        try {
            $data['updated_at'] = date('Y-m-d H:i:s');

            $sets = [];
            foreach (array_keys($data) as $key) {
                $sets[] = "$key = :$key";
            }
            $setClause = implode(', ', $sets);

            $data['id'] = $id;
            $query = "UPDATE {$this->table} SET $setClause WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            return $stmt->execute($data);
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'table' => $this->table,
                'id' => $id,
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * レコードを論理削除
     */
    public function delete(int $id): bool
    {
        try {
            $query = "UPDATE {$this->table} SET deleted_at = :deleted_at WHERE id = :id";
            $stmt = $this->db->prepare($query);
            
            return $stmt->execute([
                'id' => $id,
                'deleted_at' => date('Y-m-d H:i:s')
            ]);
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'table' => $this->table,
                'id' => $id
            ]);
            throw $e;
        }
    }

    /**
     * レコードを物理削除
     */
    public function forceDelete(int $id): bool
    {
        try {
            $query = "DELETE FROM {$this->table} WHERE id = :id";
            $stmt = $this->db->prepare($query);
            
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'table' => $this->table,
                'id' => $id
            ]);
            throw $e;
        }
    }

    /**
     * トランザクションを開始
     */
    public function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    /**
     * トランザクションをコミット
     */
    public function commit(): void
    {
        $this->db->commit();
    }

    /**
     * トランザクションをロールバック
     */
    public function rollback(): void
    {
        $this->db->rollBack();
    }

    /**
     * クエリを直接実行
     */
    protected function executeQuery(string $query, array $params = []): bool
    {
        try {
            $stmt = $this->db->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'query' => $query,
                'params' => $params
            ]);
            throw $e;
        }
    }

    /**
     * クエリを実行して結果を取得
     */
    protected function fetchQuery(string $query, array $params = []): array
    {
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error('データベースエラー', [
                'message' => $e->getMessage(),
                'query' => $query,
                'params' => $params
            ]);
            throw $e;
        }
    }
}
