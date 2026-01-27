<?php
namespace App\Models;

use App\Core\Application;
use App\Database\Database;
use ArrayAccess;

/**
 * Base Model class
 * Provides common functionality for all models
 * Implements ArrayAccess to allow both $model->prop and $model['prop'] access
 */
abstract class Model implements ArrayAccess
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';
    protected Database $db;
    protected array $attributes = [];
    protected array $original = [];
    
    public function __construct(array $attributes = [])
    {
        $this->db = Application::getInstance()->getDb();
        $this->fill($attributes);
        $this->original = $this->attributes;
    }
    
    /**
     * Fill model with attributes
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
        return $this;
    }
    
    /**
     * Get attribute value
     */
    public function __get(string $name)
    {
        return $this->attributes[$name] ?? null;
    }
    
    /**
     * Set attribute value
     */
    public function __set(string $name, $value): void
    {
        $this->attributes[$name] = $value;
    }
    
    /**
     * Check if attribute exists
     */
    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }
    
    /**
     * ArrayAccess: Check if offset exists
     */
    public function offsetExists($offset): bool
    {
        return isset($this->attributes[$offset]);
    }
    
    /**
     * ArrayAccess: Get offset value
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->attributes[$offset] ?? null;
    }
    
    /**
     * ArrayAccess: Set offset value
     */
    public function offsetSet($offset, $value): void
    {
        $this->attributes[$offset] = $value;
    }
    
    /**
     * ArrayAccess: Unset offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->attributes[$offset]);
    }
    
    /**
     * Get all attributes
     */
    public function toArray(): array
    {
        return $this->attributes;
    }
    
    /**
     * Get the table name
     */
    public static function getTable(): string
    {
        return static::$table;
    }
    
    /**
     * Find a record by ID
     */
    public static function find(int $id): ?self
    {
        $db = Application::getInstance()->getDb();
        $table = static::$table;
        $pk = static::$primaryKey;
        
        $row = $db->fetchOne("SELECT * FROM {$table} WHERE {$pk} = :id", ['id' => $id]);
        
        if (!$row) {
            return null;
        }
        
        return new static($row);
    }
    
    /**
     * Find all records
     */
    public static function all(): array
    {
        $db = Application::getInstance()->getDb();
        $table = static::$table;
        
        $rows = $db->fetchAll("SELECT * FROM {$table}");
        
        return array_map(fn($row) => new static($row), $rows);
    }
    
    /**
     * Find records by conditions
     */
    public static function where(string $column, $value, string $operator = '='): array
    {
        $db = Application::getInstance()->getDb();
        $table = static::$table;
        
        $rows = $db->fetchAll(
            "SELECT * FROM {$table} WHERE {$column} {$operator} :value",
            ['value' => $value]
        );
        
        return array_map(fn($row) => new static($row), $rows);
    }
    
    /**
     * Find one record by conditions
     */
    public static function findWhere(string $column, $value, string $operator = '='): ?self
    {
        $db = Application::getInstance()->getDb();
        $table = static::$table;
        
        $row = $db->fetchOne(
            "SELECT * FROM {$table} WHERE {$column} {$operator} :value LIMIT 1",
            ['value' => $value]
        );
        
        if (!$row) {
            return null;
        }
        
        return new static($row);
    }
    
    /**
     * Save the model (insert or update)
     */
    public function save(): bool
    {
        $pk = static::$primaryKey;
        
        if (isset($this->attributes[$pk]) && $this->attributes[$pk]) {
            return $this->update();
        }
        
        return $this->insert();
    }
    
    /**
     * Insert a new record
     */
    protected function insert(): bool
    {
        $table = static::$table;
        $pk = static::$primaryKey;
        
        // Remove primary key if not set
        $data = $this->attributes;
        if (empty($data[$pk])) {
            unset($data[$pk]);
        }
        
        // Remove timestamps for auto-management
        unset($data['created_at'], $data['updated_at']);
        
        $id = $this->db->insert($table, $data);
        $this->attributes[$pk] = $id;
        $this->original = $this->attributes;
        
        return true;
    }
    
    /**
     * Update an existing record
     */
    protected function update(): bool
    {
        $table = static::$table;
        $pk = static::$primaryKey;
        
        // Get changed attributes
        $changed = [];
        foreach ($this->attributes as $key => $value) {
            if ($key === $pk || $key === 'created_at' || $key === 'updated_at') {
                continue;
            }
            if (!isset($this->original[$key]) || $this->original[$key] !== $value) {
                $changed[$key] = $value;
            }
        }
        
        if (empty($changed)) {
            return true;
        }
        
        $this->db->update($table, $changed, "{$pk} = :pk_value", ['pk_value' => $this->attributes[$pk]]);
        $this->original = $this->attributes;
        
        return true;
    }
    
    /**
     * Delete the record
     */
    public function delete(): bool
    {
        $table = static::$table;
        $pk = static::$primaryKey;
        
        if (!isset($this->attributes[$pk])) {
            return false;
        }
        
        $this->db->delete($table, "{$pk} = :id", ['id' => $this->attributes[$pk]]);
        
        return true;
    }
    
    /**
     * Create a new record
     */
    public static function create(array $attributes): self
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }
    
    /**
     * Count records
     */
    public static function count(string $where = '1=1', array $params = []): int
    {
        $db = Application::getInstance()->getDb();
        $table = static::$table;
        
        return (int) $db->fetchColumn("SELECT COUNT(*) FROM {$table} WHERE {$where}", $params);
    }
    
    /**
     * Get database instance
     */
    protected function getDb(): Database
    {
        return $this->db;
    }
    
    /**
     * Check if model has a specific role (for User model compatibility)
     */
    public function hasRole(string $role): bool
    {
        $roles = $this->attributes['roles'] ?? [];
        if (is_string($roles)) {
            $roles = explode(',', $roles);
        }
        return in_array($role, $roles);
    }
    
    /**
     * Check if model is admin (for User model compatibility)
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('ADMIN');
    }
    
    /**
     * Select a variant (for ScheduleVariant model compatibility)
     */
    public function select(): bool
    {
        $pk = static::$primaryKey;
        
        if (!isset($this->attributes[$pk])) {
            return false;
        }
        
        $this->attributes['selected_at'] = date('Y-m-d H:i:s');
        return $this->save();
    }
    
    /**
     * Check if variant is selected (for ScheduleVariant model compatibility)
     */
    public function isSelected(): bool
    {
        return !empty($this->attributes['selected_at']);
    }
}
