<?php
/**
 * Base Model
 * Provides database abstraction and common CRUD operations
 * 
 * @package NativeMVC
 */

namespace App\Models;

use PDO;
use PDOException;

abstract class BaseModel
{
    protected static $connection = null;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    
    /**
     * Get database connection
     */
    protected static function getConnection()
    {
        if (self::$connection === null) {
            $dbConfig = require CONFIG_PATH . '/database.php';
            $config = $dbConfig['connections'][$dbConfig['default']];
            
            try {
                $dsn = self::buildDsn($config);
                self::$connection = new PDO(
                    $dsn,
                    $config['username'] ?? null,
                    $config['password'] ?? null,
                    $config['options'] ?? []
                );
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                throw new \Exception("Database connection failed");
            }
        }
        
        return self::$connection;
    }
    
    /**
     * Build DSN string
     */
    private static function buildDsn($config)
    {
        switch ($config['driver']) {
            case 'mysql':
                return sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    $config['host'],
                    $config['port'],
                    $config['database'],
                    $config['charset'] ?? 'utf8mb4'
                );
                
            case 'pgsql':
                return sprintf(
                    'pgsql:host=%s;port=%s;dbname=%s',
                    $config['host'],
                    $config['port'],
                    $config['database']
                );
                
            case 'sqlite':
                return 'sqlite:' . $config['database'];
                
            default:
                throw new \Exception("Unsupported database driver: {$config['driver']}");
        }
    }
    
    /**
     * Find record by ID
     */
    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        return $stmt->fetch();
    }
    
    /**
     * Get all records
     */
    public function all($orderBy = null, $limit = null)
    {
        $sql = "SELECT * FROM {$this->table}";
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        $stmt = self::getConnection()->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Find records by criteria
     */
    public function where($conditions, $orderBy = null, $limit = null)
    {
        $sql = "SELECT * FROM {$this->table} WHERE ";
        $params = [];
        $clauses = [];
        
        foreach ($conditions as $field => $value) {
            $clauses[] = "{$field} = :{$field}";
            $params[$field] = $value;
        }
        
        $sql .= implode(' AND ', $clauses);
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Create new record
     */
    public function create($data)
    {
        // Filter data based on fillable fields
        $data = $this->filterFillable($data);
        
        $fields = array_keys($data);
        $placeholders = array_map(function($field) {
            return ":{$field}";
        }, $fields);
        
        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->table,
            implode(', ', $fields),
            implode(', ', $placeholders)
        );
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($data);
        
        return self::getConnection()->lastInsertId();
    }
    
    /**
     * Update record
     */
    public function update($id, $data)
    {
        // Filter data based on fillable fields
        $data = $this->filterFillable($data);
        
        $setClauses = [];
        foreach (array_keys($data) as $field) {
            $setClauses[] = "{$field} = :{$field}";
        }
        
        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s = :id",
            $this->table,
            implode(', ', $setClauses),
            $this->primaryKey
        );
        
        $data['id'] = $id;
        
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute($data);
    }
    
    /**
     * Delete record
     */
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = self::getConnection()->prepare($sql);
        
        return $stmt->execute(['id' => $id]);
    }
    
    /**
     * Execute custom query
     */
    public function query($sql, $params = [])
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Execute custom statement (INSERT, UPDATE, DELETE)
     */
    public function execute($sql, $params = [])
    {
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Count records
     */
    public function count($conditions = [])
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        
        if (!empty($conditions)) {
            $sql .= " WHERE ";
            $clauses = [];
            
            foreach ($conditions as $field => $value) {
                $clauses[] = "{$field} = :{$field}";
            }
            
            $sql .= implode(' AND ', $clauses);
        }
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($conditions);
        
        $result = $stmt->fetch();
        return (int) $result['total'];
    }
    
    /**
     * Paginate results
     */
    public function paginate($page = 1, $perPage = 15, $conditions = [], $orderBy = null)
    {
        $offset = ($page - 1) * $perPage;
        $total = $this->count($conditions);
        
        $sql = "SELECT * FROM {$this->table}";
        
        if (!empty($conditions)) {
            $sql .= " WHERE ";
            $clauses = [];
            
            foreach ($conditions as $field => $value) {
                $clauses[] = "{$field} = :{$field}";
            }
            
            $sql .= implode(' AND ', $clauses);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        $sql .= " LIMIT {$perPage} OFFSET {$offset}";
        
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($conditions);
        
        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }
    
    /**
     * Filter data based on fillable fields
     */
    protected function filterFillable($data)
    {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    /**
     * Sanitize input
     */
    protected function sanitize($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        
        return htmlspecialchars(strip_tags($data), ENT_QUOTES, 'UTF-8');
    }
}
