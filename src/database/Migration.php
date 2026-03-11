<?php
/**
 * VelocityPhp Migration System
 * Database migrations management — supports MySQL, PostgreSQL, SQLite
 * 
 * @package VelocityPhp
 * @version 1.1.0
 */

namespace App\Database;

use PDO;
use PDOException;

abstract class Migration
{
    protected $connection;
    protected $driver;
    
    public function __construct($connection)
    {
        $this->connection = $connection;
        try {
            $this->driver = $connection->getAttribute(PDO::ATTR_DRIVER_NAME);
        } catch (\Exception $e) {
            $this->driver = 'mysql';
        }
    }
    
    abstract public function up();
    
    abstract public function down();
    
    protected function createTable($table, callable $callback)
    {
        $builder = new TableBuilder($table, 'create', $this->driver);
        $callback($builder);
        $sql = $builder->toSql();
        return $this->execute($sql);
    }
    
    protected function dropTable($table)
    {
        $q = $this->driver === 'pgsql'
            ? '"' . str_replace('"', '""', $table) . '"'
            : '`' . str_replace('`', '', $table) . '`';
        $sql = "DROP TABLE IF EXISTS {$q}";
        return $this->execute($sql);
    }
    
    protected function table($table, callable $callback)
    {
        $builder = new TableBuilder($table, 'alter', $this->driver);
        $callback($builder);
        $sql = $builder->toSql();
        return $this->execute($sql);
    }
    
    protected function execute($sql)
    {
        try {
            return $this->connection->exec($sql);
        } catch (PDOException $e) {
            throw new \Exception("Migration failed: " . $e->getMessage() . "\nSQL: " . $sql);
        }
    }
}

class TableBuilder
{
    private $table;
    private $type;
    private $driver;
    private $columns = [];
    private $indexes = [];
    private $foreignKeys = [];
    
    public function __construct($table, $type = 'create', $driver = 'mysql')
    {
        $this->table = $table;
        $this->type = $type;
        $this->driver = $driver;
    }
    
    // ── Identifier quoting ────────────────────────────────────────────────────
    
    private function q($identifier)
    {
        if ($this->driver === 'pgsql') {
            return '"' . str_replace('"', '""', $identifier) . '"';
        }
        return '`' . str_replace('`', '', $identifier) . '`';
    }
    
    private function extractColumnName($columnDef)
    {
        // Match either backtick-quoted or double-quote-quoted identifier
        if (preg_match('/[`"]([^`"]+)[`"]/', $columnDef, $matches)) {
            return $matches[1];
        }
        return '';
    }
    
    // ── Column definitions ────────────────────────────────────────────────────
    
    public function id($column = 'id')
    {
        return $this->bigIncrements($column);
    }
    
    public function bigIncrements($column)
    {
        if ($this->driver === 'pgsql') {
            $this->columns[] = $this->q($column) . ' BIGSERIAL PRIMARY KEY';
        } else {
            $this->columns[] = $this->q($column) . ' BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY';
        }
        return $this;
    }
    
    public function increments($column)
    {
        if ($this->driver === 'pgsql') {
            $this->columns[] = $this->q($column) . ' SERIAL PRIMARY KEY';
        } else {
            $this->columns[] = $this->q($column) . ' INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY';
        }
        return $this;
    }
    
    public function string($column, $length = 255)
    {
        $this->columns[] = $this->q($column) . " VARCHAR({$length})";
        return $this;
    }
    
    public function text($column)
    {
        $this->columns[] = $this->q($column) . ' TEXT';
        return $this;
    }
    
    public function integer($column)
    {
        $this->columns[] = $this->q($column) . ' INT';
        return $this;
    }
    
    public function bigInteger($column)
    {
        $this->columns[] = $this->q($column) . ' BIGINT';
        return $this;
    }
    
    public function boolean($column)
    {
        $this->columns[] = $this->q($column) . ' BOOLEAN';
        return $this;
    }
    
    public function decimal($column, $precision = 8, $scale = 2)
    {
        $this->columns[] = $this->q($column) . " DECIMAL({$precision}, {$scale})";
        return $this;
    }
    
    public function date($column)
    {
        $this->columns[] = $this->q($column) . ' DATE';
        return $this;
    }
    
    public function dateTime($column)
    {
        // PostgreSQL uses TIMESTAMP instead of DATETIME
        $type = ($this->driver === 'pgsql') ? 'TIMESTAMP' : 'DATETIME';
        $this->columns[] = $this->q($column) . " {$type}";
        return $this;
    }
    
    public function timestamp($column)
    {
        $this->columns[] = $this->q($column) . ' TIMESTAMP';
        return $this;
    }
    
    public function timestamps()
    {
        $this->timestamp('created_at')->nullable();
        $this->timestamp('updated_at')->nullable();
        return $this;
    }
    
    public function nullable()
    {
        $lastIndex = count($this->columns) - 1;
        if ($lastIndex >= 0) {
            $this->columns[$lastIndex] = str_replace(' NOT NULL', '', $this->columns[$lastIndex]);
        }
        return $this;
    }
    
    public function default($value)
    {
        $lastIndex = count($this->columns) - 1;
        if ($lastIndex >= 0) {
            $escaped = is_string($value) ? "'" . addslashes($value) . "'" : $value;
            $this->columns[$lastIndex] .= " DEFAULT {$escaped}";
        }
        return $this;
    }
    
    public function unique()
    {
        $lastIndex = count($this->columns) - 1;
        if ($lastIndex >= 0) {
            $column = $this->extractColumnName($this->columns[$lastIndex]);
            if ($this->driver === 'pgsql') {
                $this->indexes[] = 'UNIQUE (' . $this->q($column) . ')';
            } else {
                $this->indexes[] = 'UNIQUE KEY ' . $this->q($column . '_unique') . ' (' . $this->q($column) . ')';
            }
        }
        return $this;
    }
    
    public function index($columns = null)
    {
        if ($columns === null) {
            $lastIndex = count($this->columns) - 1;
            if ($lastIndex >= 0) {
                $column = $this->extractColumnName($this->columns[$lastIndex]);
                // Inline indexes inside CREATE TABLE are MySQL-only; for pgsql they must be separate.
                // Store them separately so toSql() can handle them after the CREATE TABLE statement.
                if ($this->driver === 'pgsql') {
                    $this->indexes[] = '__SEPARATE__CREATE INDEX ON ' . $this->q($this->table) . ' (' . $this->q($column) . ')';
                } else {
                    $this->indexes[] = 'KEY ' . $this->q($column . '_index') . ' (' . $this->q($column) . ')';
                }
            }
        } else {
            $columns = is_array($columns) ? $columns : [$columns];
            $name = implode('_', $columns) . '_index';
            if ($this->driver === 'pgsql') {
                $cols = implode(', ', array_map([$this, 'q'], $columns));
                $this->indexes[] = '__SEPARATE__CREATE INDEX ON ' . $this->q($this->table) . " ({$cols})";
            } else {
                $cols = implode(', ', array_map([$this, 'q'], $columns));
                $this->indexes[] = 'KEY ' . $this->q($name) . " ({$cols})";
            }
        }
        return $this;
    }
    
    public function foreign($column)
    {
        $foreign = new ForeignKeyBuilder($column, $this->driver);
        $this->foreignKeys[] = $foreign;
        return $foreign;
    }
    
    public function dropColumn($column)
    {
        if ($this->type === 'alter') {
            $this->columns[] = 'DROP COLUMN ' . $this->q($column);
        }
        return $this;
    }
    
    public function renameColumn($from, $to)
    {
        if ($this->type === 'alter') {
            if ($this->driver === 'pgsql') {
                $this->columns[] = 'RENAME COLUMN ' . $this->q($from) . ' TO ' . $this->q($to);
            } else {
                // MySQL RENAME COLUMN (8.0+); fallback note for older versions
                $this->columns[] = 'RENAME COLUMN ' . $this->q($from) . ' TO ' . $this->q($to);
            }
        }
        return $this;
    }
    
    public function toSql()
    {
        // Separate inline vs. separate-statement indexes (pgsql CREATE INDEX)
        $inlineIndexes = [];
        $separateIndexes = [];
        foreach ($this->indexes as $idx) {
            if (strpos($idx, '__SEPARATE__') === 0) {
                $separateIndexes[] = substr($idx, strlen('__SEPARATE__'));
            } else {
                $inlineIndexes[] = $idx;
            }
        }
        
        if ($this->type === 'create') {
            $tableQ = $this->q($this->table);
            $sql = "CREATE TABLE IF NOT EXISTS {$tableQ} (";
            $sql .= implode(', ', $this->columns);
            
            if (!empty($inlineIndexes)) {
                $sql .= ', ' . implode(', ', $inlineIndexes);
            }
            
            if (!empty($this->foreignKeys)) {
                $keys = array_map(fn($fk) => $fk->toSql(), $this->foreignKeys);
                $sql .= ', ' . implode(', ', $keys);
            }
            
            $sql .= ')';
            
            // MySQL-specific table options
            if ($this->driver === 'mysql') {
                $sql .= ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
            }
            
            // Append separate CREATE INDEX statements (pgsql)
            if (!empty($separateIndexes)) {
                $sql .= '; ' . implode('; ', $separateIndexes);
            }
            
            return $sql;
        } else {
            // ALTER TABLE
            if (empty($this->columns)) {
                return '';
            }
            
            $tableQ = $this->q($this->table);
            $alterations = [];
            
            foreach ($this->columns as $column) {
                if (strpos($column, 'DROP COLUMN') === 0 || strpos($column, 'RENAME COLUMN') === 0) {
                    $alterations[] = $column;
                } else {
                    $alterations[] = 'ADD COLUMN ' . $column;
                }
            }
            
            $sql = "ALTER TABLE {$tableQ} " . implode(', ', $alterations);
            
            if (!empty($separateIndexes)) {
                $sql .= '; ' . implode('; ', $separateIndexes);
            }
            
            return $sql;
        }
    }
}

class ForeignKeyBuilder
{
    private $column;
    private $references;
    private $on;
    private $onDelete;
    private $onUpdate;
    private $driver;
    
    public function __construct($column, $driver = 'mysql')
    {
        $this->column = $column;
        $this->driver = $driver;
    }
    
    private function q($identifier)
    {
        if ($this->driver === 'pgsql') {
            return '"' . str_replace('"', '""', $identifier) . '"';
        }
        return '`' . str_replace('`', '', $identifier) . '`';
    }
    
    public function references($column)
    {
        $this->references = $column;
        return $this;
    }
    
    public function on($table)
    {
        $this->on = $table;
        return $this;
    }
    
    public function onDelete($action)
    {
        $this->onDelete = $action;
        return $this;
    }
    
    public function onUpdate($action)
    {
        $this->onUpdate = $action;
        return $this;
    }
    
    public function toSql()
    {
        $sql = 'FOREIGN KEY (' . $this->q($this->column) . ')'
             . ' REFERENCES ' . $this->q($this->on) . ' (' . $this->q($this->references) . ')';
        
        if ($this->onDelete) {
            $sql .= ' ON DELETE ' . $this->onDelete;
        }
        
        if ($this->onUpdate) {
            $sql .= ' ON UPDATE ' . $this->onUpdate;
        }
        
        return $sql;
    }
}

class MigrationManager
{
    private $connection;
    private $driver;
    private $table = 'migrations';
    
    public function __construct($connection)
    {
        $this->connection = $connection;
        try {
            $this->driver = $connection->getAttribute(PDO::ATTR_DRIVER_NAME);
        } catch (\Exception $e) {
            $this->driver = 'mysql';
        }
        $this->ensureMigrationsTable();
    }
    
    private function q($identifier)
    {
        if ($this->driver === 'pgsql') {
            return '"' . str_replace('"', '""', $identifier) . '"';
        }
        return '`' . str_replace('`', '', $identifier) . '`';
    }
    
    private function ensureMigrationsTable()
    {
        $t = $this->q($this->table);
        
        if ($this->driver === 'pgsql') {
            $sql = "CREATE TABLE IF NOT EXISTS {$t} (
                id SERIAL PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
        } elseif ($this->driver === 'sqlite') {
            $sql = "CREATE TABLE IF NOT EXISTS {$t} (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) NOT NULL,
                batch INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
        } else {
            // MySQL
            $sql = "CREATE TABLE IF NOT EXISTS {$t} (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `migration` VARCHAR(255) NOT NULL,
                `batch` INT UNSIGNED NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        }
        
        $this->connection->exec($sql);
    }
    
    public function run()
    {
        $migrations = $this->getPendingMigrations();
        
        if (empty($migrations)) {
            return ['message' => 'No pending migrations'];
        }
        
        $batch = $this->getNextBatchNumber();
        $ran = [];
        
        foreach ($migrations as $migration) {
            $instance = $this->loadMigration($migration);
            $instance->up();
            $this->recordMigration($migration, $batch);
            $ran[] = $migration;
        }
        
        return ['ran' => $ran, 'batch' => $batch];
    }
    
    public function rollback($steps = 1)
    {
        $migrations = $this->getLastBatchMigrations($steps);
        
        if (empty($migrations)) {
            return ['message' => 'No migrations to rollback'];
        }
        
        $rolledBack = [];
        
        foreach (array_reverse($migrations) as $migration) {
            $instance = $this->loadMigration($migration['migration']);
            $instance->down();
            $this->deleteMigration($migration['id']);
            $rolledBack[] = $migration['migration'];
        }
        
        return ['rolled_back' => $rolledBack];
    }
    
    public function status()
    {
        $ran = $this->getRanMigrations();
        $all = $this->getAllMigrations();
        
        $result = [];
        foreach ($all as $migration) {
            $result[] = [
                'migration' => $migration,
                'ran'       => in_array($migration, $ran),
            ];
        }
        
        return $result;
    }
    
    private function getPendingMigrations()
    {
        $ran = $this->getRanMigrations();
        $all = $this->getAllMigrations();
        
        return array_values(array_diff($all, $ran));
    }
    
    private function getRanMigrations()
    {
        $t = $this->q($this->table);
        $stmt = $this->connection->query("SELECT migration FROM {$t}");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    private function getAllMigrations()
    {
        $path = BASE_PATH . '/database/migrations';
        
        if (!is_dir($path)) {
            return [];
        }
        
        $files = scandir($path);
        $migrations = [];
        
        foreach ($files as $file) {
            // Support format: 0001_migration_name.php
            if (preg_match('/^(\d{4}_)(.+?)\.php$/', $file, $matches)) {
                $migrations[] = $matches[1] . $matches[2]; // e.g. "0001_users"
            }
        }
        
        sort($migrations);
        return $migrations;
    }
    
    private function loadMigration($migration)
    {
        // Migration name is the filename stem, e.g. "0001_users"
        $file = BASE_PATH . "/database/migrations/{$migration}.php";
        
        if (!file_exists($file)) {
            throw new \Exception("Migration file not found: {$file}");
        }
        
        require_once $file;
        
        // Convert "0001_users" → class name "Migration_0001_Users"
        $parts = explode('_', $migration);
        $className = 'Migration_' . implode('_', array_map('ucfirst', $parts));
        
        if (!class_exists($className)) {
            throw new \Exception("Migration class not found: {$className} (in {$file})");
        }
        
        return new $className($this->connection);
    }
    
    private function getNextBatchNumber()
    {
        $t = $this->q($this->table);
        $stmt = $this->connection->query("SELECT MAX(batch) as max_batch FROM {$t}");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return ((int)($result['max_batch'] ?? 0)) + 1;
    }
    
    private function recordMigration($migration, $batch)
    {
        $t = $this->q($this->table);
        $stmt = $this->connection->prepare(
            "INSERT INTO {$t} (migration, batch) VALUES (?, ?)"
        );
        $stmt->execute([$migration, $batch]);
    }
    
    private function getLastBatchMigrations($steps)
    {
        $t = $this->q($this->table);
        // Fix: use prepare() not query() so the LIMIT placeholder binds correctly
        $stmt = $this->connection->prepare(
            "SELECT id, migration FROM {$t} ORDER BY batch DESC, id DESC LIMIT ?"
        );
        $stmt->execute([(int)$steps]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function deleteMigration($id)
    {
        $t = $this->q($this->table);
        $stmt = $this->connection->prepare("DELETE FROM {$t} WHERE id = ?");
        $stmt->execute([$id]);
    }
}
