<?php
/**
 * VelocityPhp BaseModel
 * Ultra-fast base model with connection pooling, query caching, and optimized CRUD
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

namespace App\Models;

use PDO;
use PDOException;
use PDOStatement;

abstract class BaseModel
{
    // Connection pool for reusing connections
    protected static $connectionPool = [];
    protected static $poolSize = 10;
    protected static $activeConnections = 0;
    
    // Query cache for repeated queries
    protected static $queryCache = [];
    protected static $queryCacheEnabled = true;
    protected static $queryCacheTTL = 3600;
    
    // Model properties
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $timestamps = true;
    
    // Performance tracking
    protected static $queryCount = 0;
    protected static $cacheHits = 0;
    
    /**
     * Get optimized database connection with pooling
     */
    protected static function getConnection()
    {
        $poolKey = 'default';
        
        // Check if we have an available connection in the pool
        if (isset(self::$connectionPool[$poolKey]) && !empty(self::$connectionPool[$poolKey])) {
            $connection = array_pop(self::$connectionPool[$poolKey]);
            
            // Verify connection is still alive
            try {
                $connection->query('SELECT 1');
                self::$activeConnections++;
                return $connection;
            } catch (PDOException $e) {
                // Connection is dead, create a new one
            }
        }
        
        // Create new connection if pool is empty or connection is dead
        return self::createConnection();
    }
    
    /**
     * Create new database connection with optimizations
     */
    private static function createConnection()
    {
        $dbConfig = require CONFIG_PATH . '/database.php';
        $config = $dbConfig['connections'][$dbConfig['default']];
        
        try {
            $dsn = self::buildDsn($config);
            
            // Enhanced PDO options for maximum performance
            $baseOptions = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
            ];
            
            // Add MySQL-specific options only for MySQL
            if ($config['driver'] === 'mysql') {
                $baseOptions[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci";
            }
            
            $options = array_merge($config['options'] ?? [], $baseOptions);
            
            $connection = new PDO(
                $dsn,
                $config['username'] ?? null,
                $config['password'] ?? null,
                $options
            );
            
            // Set additional MySQL optimizations
            if ($config['driver'] === 'mysql') {
                $connection->exec("SET SESSION sql_mode='TRADITIONAL'");
                $connection->exec("SET SESSION time_zone='+00:00'");
            }
            
            self::$activeConnections++;
            return $connection;
            
        } catch (PDOException $e) {
            $errorMsg = "Database connection failed: " . $e->getMessage();
            error_log($errorMsg);
            
            // Deep error logging
            if (defined('CONFIG_PATH')) {
                $appConfig = require CONFIG_PATH . '/app.php';
                if ($appConfig['deep_errors']) {
                    error_log("Stack trace: " . $e->getTraceAsString());
                    error_log("Connection config: " . print_r([
                        'host' => $config['host'] ?? 'N/A',
                        'port' => $config['port'] ?? 'N/A',
                        'database' => $config['database'] ?? 'N/A',
                        'driver' => $config['driver'] ?? 'N/A'
                    ], true));
                }
            }
            
            throw new \Exception($errorMsg, 500, $e);
        }
    }
    
    /**
     * Return connection to pool
     */
    protected static function releaseConnection($connection)
    {
        $poolKey = 'default';
        
        if (!isset(self::$connectionPool[$poolKey])) {
            self::$connectionPool[$poolKey] = [];
        }
        
        // Only keep connections up to pool size
        if (count(self::$connectionPool[$poolKey]) < self::$poolSize) {
            self::$connectionPool[$poolKey][] = $connection;
        }
        
        self::$activeConnections = max(0, self::$activeConnections - 1);
    }
    
    /**
     * Build optimized DSN string
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
     * Execute cached query for better performance
     */
    protected function cachedQuery($sql, $params = [], $cacheTTL = null)
    {
        if (!self::$queryCacheEnabled) {
            return $this->query($sql, $params);
        }
        
        $cacheKey = md5($sql . serialize($params));
        $cacheTTL = $cacheTTL ?? self::$queryCacheTTL;
        
        // Check cache
        if (isset(self::$queryCache[$cacheKey])) {
            $cached = self::$queryCache[$cacheKey];
            if (time() - $cached['time'] < $cacheTTL) {
                self::$cacheHits++;
                return $cached['data'];
            }
        }
        
        // Execute query and cache result
        $result = $this->query($sql, $params);
        
        self::$queryCache[$cacheKey] = [
            'data' => $result,
            'time' => time()
        ];
        
        return $result;
    }
    
    /**
     * Clear query cache
     */
    public static function clearCache()
    {
        self::$queryCache = [];
    }
    
    /**
     * Find record by ID (optimized with statement caching)
     */
    public function find($id)
    {
        static $stmtCache = [];
        $cacheKey = $this->table . '_find';
        
        $connection = self::getConnection();
        
        try {
            // Use cached prepared statement for better performance
            if (!isset($stmtCache[$cacheKey])) {
                $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? LIMIT 1";
                $stmtCache[$cacheKey] = $connection->prepare($sql);
            }
            
            $stmt = $stmtCache[$cacheKey];
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            self::$queryCount++;
            return $result;
        } finally {
            self::releaseConnection($connection);
        }
    }
    
    /**
     * Get all records (with caching)
     */
    public function all($orderBy = null, $limit = null)
    {
        $sql = "SELECT * FROM {$this->table}";
        
        if ($orderBy) {
            $sql .= " ORDER BY " . $this->sanitizeOrderBy($orderBy);
        }
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        return $this->cachedQuery($sql);
    }
    
    /**
     * Find records by criteria (optimized)
     */
    public function where($conditions, $orderBy = null, $limit = null)
    {
        $sql = "SELECT * FROM {$this->table} WHERE ";
        $params = [];
        $clauses = [];
        
        foreach ($conditions as $field => $value) {
            $clauses[] = $this->escapeIdentifier($field) . " = ?";
            $params[] = $value;
        }
        
        $sql .= implode(' AND ', $clauses);
        
        if ($orderBy) {
            $sql .= " ORDER BY " . $this->sanitizeOrderBy($orderBy);
        }
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        return $this->query($sql, $params);
    }
    
    /**
     * Advanced where with operators
     */
    public function whereAdvanced($conditions, $orderBy = null, $limit = null)
    {
        $sql = "SELECT * FROM {$this->table} WHERE ";
        $params = [];
        $clauses = [];
        
        foreach ($conditions as $field => $condition) {
            if (is_array($condition)) {
                $operator = $condition[0];
                $value = $condition[1];
                $clauses[] = $this->escapeIdentifier($field) . " {$operator} ?";
                $params[] = $value;
            } else {
                $clauses[] = $this->escapeIdentifier($field) . " = ?";
                $params[] = $condition;
            }
        }
        
        $sql .= implode(' AND ', $clauses);
        
        if ($orderBy) {
            $sql .= " ORDER BY " . $this->sanitizeOrderBy($orderBy);
        }
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }
        
        return $this->query($sql, $params);
    }
    
    /**
     * Create new record (optimized with batch support)
     */
    public function create($data)
    {
        $data = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->escapeIdentifier($this->table),
            implode(', ', array_map([$this, 'escapeIdentifier'], $fields)),
            implode(', ', $placeholders)
        );
        
        $connection = self::getConnection();
        
        try {
            $stmt = $connection->prepare($sql);
            $stmt->execute(array_values($data));
            self::$queryCount++;
            
            $lastId = $connection->lastInsertId();
            
            // Clear relevant cache
            $this->clearRelevantCache();
            
            return $lastId;
        } finally {
            self::releaseConnection($connection);
        }
    }
    
    /**
     * Batch insert for maximum performance
     */
    public function batchInsert($records)
    {
        if (empty($records)) {
            return 0;
        }
        
        $first = $records[0];
        $first = $this->filterFillable($first);
        
        if ($this->timestamps) {
            $timestamp = date('Y-m-d H:i:s');
        }
        
        $fields = array_keys($first);
        $placeholders = '(' . implode(', ', array_fill(0, count($fields), '?')) . ')';
        $allPlaceholders = array_fill(0, count($records), $placeholders);
        
        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES %s",
            $this->escapeIdentifier($this->table),
            implode(', ', array_map([$this, 'escapeIdentifier'], $fields)),
            implode(', ', $allPlaceholders)
        );
        
        $values = [];
        foreach ($records as $record) {
            $record = $this->filterFillable($record);
            if ($this->timestamps) {
                $record['created_at'] = $timestamp;
                $record['updated_at'] = $timestamp;
            }
            $values = array_merge($values, array_values($record));
        }
        
        $connection = self::getConnection();
        
        try {
            $stmt = $connection->prepare($sql);
            $result = $stmt->execute($values);
            self::$queryCount++;
            
            $this->clearRelevantCache();
            
            return $stmt->rowCount();
        } finally {
            self::releaseConnection($connection);
        }
    }
    
    /**
     * Update record (optimized)
     */
    public function update($id, $data)
    {
        $data = $this->filterFillable($data);
        
        if ($this->timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        // Return early if no data to update
        if (empty($data)) {
            return true;
        }
        
        $setClauses = [];
        $values = [];
        
        foreach ($data as $field => $value) {
            $setClauses[] = $this->escapeIdentifier($field) . " = ?";
            $values[] = $value;
        }
        
        $values[] = $id;
        
        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s = ?",
            $this->escapeIdentifier($this->table),
            implode(', ', $setClauses),
            $this->escapeIdentifier($this->primaryKey)
        );
        
        $connection = self::getConnection();
        
        try {
            $stmt = $connection->prepare($sql);
            $result = $stmt->execute($values);
            self::$queryCount++;
            
            $this->clearRelevantCache();
            
            return $result;
        } finally {
            self::releaseConnection($connection);
        }
    }
    
    /**
     * Delete record (optimized)
     */
    public function delete($id)
    {
        $sql = sprintf(
            "DELETE FROM %s WHERE %s = ?",
            $this->escapeIdentifier($this->table),
            $this->escapeIdentifier($this->primaryKey)
        );
        
        $connection = self::getConnection();
        
        try {
            $stmt = $connection->prepare($sql);
            $result = $stmt->execute([$id]);
            self::$queryCount++;
            
            $this->clearRelevantCache();
            
            return $result;
        } finally {
            self::releaseConnection($connection);
        }
    }
    
    /**
     * Execute custom query
     */
    public function query($sql, $params = [])
    {
        $connection = self::getConnection();
        
        try {
            $stmt = $connection->prepare($sql);
            $stmt->execute($params);
            self::$queryCount++;
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            self::releaseConnection($connection);
        }
    }
    
    /**
     * Execute custom statement (INSERT, UPDATE, DELETE)
     */
    public function execute($sql, $params = [])
    {
        $connection = self::getConnection();
        
        try {
            $stmt = $connection->prepare($sql);
            $result = $stmt->execute($params);
            self::$queryCount++;
            
            $this->clearRelevantCache();
            
            return $result;
        } finally {
            self::releaseConnection($connection);
        }
    }
    
    /**
     * Count records (cached)
     */
    public function count($conditions = [])
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $sql .= " WHERE ";
            $clauses = [];
            
            foreach ($conditions as $field => $value) {
                $clauses[] = $this->escapeIdentifier($field) . " = ?";
                $params[] = $value;
            }
            
            $sql .= implode(' AND ', $clauses);
        }
        
        $result = $this->cachedQuery($sql, $params, 300); // Cache for 5 minutes
        return isset($result[0]) ? (int)$result[0]['total'] : 0;
    }
    
    /**
     * Paginate results (optimized)
     */
    public function paginate($page = 1, $perPage = 15, $conditions = [], $orderBy = null)
    {
        $page = max(1, (int)$page);
        $perPage = max(1, min(100, (int)$perPage)); // Max 100 per page
        $offset = ($page - 1) * $perPage;
        
        $total = $this->count($conditions);
        
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $sql .= " WHERE ";
            $clauses = [];
            
            foreach ($conditions as $field => $value) {
                $clauses[] = $this->escapeIdentifier($field) . " = ?";
                $params[] = $value;
            }
            
            $sql .= implode(' AND ', $clauses);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY " . $this->sanitizeOrderBy($orderBy);
        }
        
        $sql .= " LIMIT {$perPage} OFFSET {$offset}";
        
        $data = $this->query($sql, $params);
        
        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total)
        ];
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction()
    {
        $connection = self::getConnection();
        return $connection->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit()
    {
        $connection = self::getConnection();
        return $connection->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback()
    {
        $connection = self::getConnection();
        return $connection->rollBack();
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
     * Clear relevant cache for this table
     */
    protected function clearRelevantCache()
    {
        // Clear all query cache for safety
        self::$queryCache = [];
    }
    
    /**
     * Sanitize ORDER BY clause
     */
    protected function sanitizeOrderBy($orderBy)
    {
        // Remove any dangerous characters
        $orderBy = preg_replace('/[^a-zA-Z0-9_,. ]/', '', $orderBy);
        return $orderBy;
    }
    
    /**
     * Escape identifier (table/column name)
     */
    protected function escapeIdentifier($identifier)
    {
        // Remove backticks if present
        $identifier = str_replace('`', '', $identifier);
        
        // Add backticks for MySQL
        return "`{$identifier}`";
    }
    
    /**
     * Get performance statistics
     */
    public static function getStats()
    {
        return [
            'queries' => self::$queryCount,
            'cache_hits' => self::$cacheHits,
            'active_connections' => self::$activeConnections,
            'cache_size' => count(self::$queryCache)
        ];
    }
}
