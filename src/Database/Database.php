<?php
namespace App\Database;

use PDO;
use PDOException;

/**
 * Database Connection and Query Handler
 */
class Database
{
    private ?PDO $pdo = null;
    private array $config;
    
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    /**
     * Get PDO connection (lazy initialization)
     */
    public function getConnection(): PDO
    {
        if ($this->pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $this->config['host'],
                $this->config['port'],
                $this->config['name'],
                $this->config['charset']
            );
            
            try {
                $this->pdo = new PDO($dsn, $this->config['user'], $this->config['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'"
                ]);
            } catch (PDOException $e) {
                throw new \Exception("Database connection failed: " . $e->getMessage());
            }
        }
        
        return $this->pdo;
    }
    
    /**
     * Execute a query and return the statement
     */
    public function execute(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Fetch all rows
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->execute($sql, $params)->fetchAll();
    }
    
    /**
     * Fetch one row
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $result = $this->execute($sql, $params)->fetch();
        return $result ?: null;
    }
    
    /**
     * Fetch a single column value
     */
    public function fetchColumn(string $sql, array $params = [])
    {
        return $this->execute($sql, $params)->fetchColumn();
    }
    
    /**
     * Insert a row and return last insert ID
     */
    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->execute($sql, $data);
        
        return (int) $this->getConnection()->lastInsertId();
    }
    
    /**
     * Update rows
     * @param string $table Table name
     * @param array $data Data to update
     * @param string|array<string,mixed> $where Where clause (string) or conditions array (e.g., ['id' => 5])
     * @param array $whereParams Parameters for where clause (only used when $where is a string)
     * @return int Number of affected rows
     */
    public function update(string $table, array $data, $where, array $whereParams = []): int
    {
        $set = [];
        foreach (array_keys($data) as $column) {
            $set[] = "{$column} = :{$column}";
        }
        
        // Support array-based where clause
        if (is_array($where)) {
            $conditions = [];
            foreach ($where as $key => $value) {
                $conditions[] = "{$key} = :where_{$key}";
                $whereParams["where_{$key}"] = $value;
            }
            $where = implode(' AND ', $conditions);
        }
        
        $sql = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE {$where}";
        $stmt = $this->execute($sql, array_merge($data, $whereParams));
        
        return $stmt->rowCount();
    }
    
    /**
     * Delete rows
     * @param string $table Table name
     * @param string|array<string,mixed> $where Where clause (string) or conditions array (e.g., ['id' => 5])
     * @param array $params Parameters for where clause (only used when $where is a string)
     * @return int Number of affected rows
     */
    public function delete(string $table, $where, array $params = []): int
    {
        // Support array-based where clause
        if (is_array($where)) {
            $conditions = [];
            foreach ($where as $key => $value) {
                $conditions[] = "{$key} = :{$key}";
                $params[$key] = $value;
            }
            $where = implode(' AND ', $conditions);
        }
        
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->execute($sql, $params);
        
        return $stmt->rowCount();
    }
    
    /**
     * Begin a transaction
     */
    public function beginTransaction(): void
    {
        $this->getConnection()->beginTransaction();
    }
    
    /**
     * Commit a transaction
     */
    public function commit(): void
    {
        $this->getConnection()->commit();
    }
    
    /**
     * Rollback a transaction
     */
    public function rollback(): void
    {
        $this->getConnection()->rollBack();
    }
    
    /**
     * Check if in transaction
     */
    public function inTransaction(): bool
    {
        return $this->getConnection()->inTransaction();
    }
    
    /**
     * Execute a raw query (alias for execute)
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        return $this->execute($sql, $params);
    }
    
    /**
     * Fetch one row (alias for fetchOne)
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        return $this->fetchOne($sql, $params);
    }
}
