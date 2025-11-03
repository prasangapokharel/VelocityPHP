<?php
/**
 * VelocityPhp Migration System
 * Database migrations management
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

namespace App\Database;

use PDO;
use PDOException;

abstract class Migration
{
    protected $connection;
    
    public function __construct($connection)
    {
        $this->connection = $connection;
    }
    
    abstract public function up();
    
    abstract public function down();
    
    protected function createTable($table, callable $callback)
    {
        $builder = new TableBuilder($table, 'create');
        $callback($builder);
        $sql = $builder->toSql();
        return $this->execute($sql);
    }
    
    protected function dropTable($table)
    {
        $sql = "DROP TABLE IF EXISTS `{$table}`";
        return $this->execute($sql);
    }
    
    protected function table($table, callable $callback)
    {
        $builder = new TableBuilder($table, 'alter');
        $callback($builder);
        $sql = $builder->toSql();
        return $this->execute($sql);
    }
    
    protected function execute($sql)
    {
        try {
            return $this->connection->exec($sql);
        } catch (PDOException $e) {
            throw new \Exception("Migration failed: " . $e->getMessage());
        }
    }
}

class TableBuilder
{
    private $table;
    private $type;
    private $columns = [];
    private $indexes = [];
    private $foreignKeys = [];
    
    public function __construct($table, $type = 'create')
    {
        $this->table = $table;
        $this->type = $type;
    }
    
    public function id($column = 'id')
    {
        return $this->bigIncrements($column);
    }
    
    public function bigIncrements($column)
    {
        $this->columns[] = "`{$column}` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY";
        return $this;
    }
    
    public function increments($column)
    {
        $this->columns[] = "`{$column}` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY";
        return $this;
    }
    
    public function string($column, $length = 255)
    {
        $this->columns[] = "`{$column}` VARCHAR({$length})";
        return $this;
    }
    
    public function text($column)
    {
        $this->columns[] = "`{$column}` TEXT";
        return $this;
    }
    
    public function integer($column)
    {
        $this->columns[] = "`{$column}` INT";
        return $this;
    }
    
    public function bigInteger($column)
    {
        $this->columns[] = "`{$column}` BIGINT";
        return $this;
    }
    
    public function boolean($column)
    {
        $this->columns[] = "`{$column}` BOOLEAN";
        return $this;
    }
    
    public function decimal($column, $precision = 8, $scale = 2)
    {
        $this->columns[] = "`{$column}` DECIMAL({$precision}, {$scale})";
        return $this;
    }
    
    public function date($column)
    {
        $this->columns[] = "`{$column}` DATE";
        return $this;
    }
    
    public function dateTime($column)
    {
        $this->columns[] = "`{$column}` DATETIME";
        return $this;
    }
    
    public function timestamp($column)
    {
        $this->columns[] = "`{$column}` TIMESTAMP";
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
            $this->indexes[] = "UNIQUE KEY `{$column}_unique` (`{$column}`)";
        }
        return $this;
    }
    
    public function index($columns = null)
    {
        if ($columns === null) {
            $lastIndex = count($this->columns) - 1;
            if ($lastIndex >= 0) {
                $column = $this->extractColumnName($this->columns[$lastIndex]);
                $this->indexes[] = "KEY `{$column}_index` (`{$column}`)";
            }
        } else {
            $columns = is_array($columns) ? $columns : [$columns];
            $name = implode('_', $columns) . '_index';
            $cols = '`' . implode('`, `', $columns) . '`';
            $this->indexes[] = "KEY `{$name}` ({$cols})";
        }
        return $this;
    }
    
    public function foreign($column)
    {
        $foreign = new ForeignKeyBuilder($column);
        $this->foreignKeys[] = $foreign;
        return $foreign;
    }
    
    public function dropColumn($column)
    {
        if ($this->type === 'alter') {
            $this->columns[] = "DROP COLUMN `{$column}`";
        }
        return $this;
    }
    
    public function renameColumn($from, $to)
    {
        if ($this->type === 'alter') {
            // This is simplified - full implementation would need column type
            $this->columns[] = "CHANGE COLUMN `{$from}` `{$to}`";
        }
        return $this;
    }
    
    public function toSql()
    {
        if ($this->type === 'create') {
            $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (";
            $sql .= implode(', ', $this->columns);
            
            if (!empty($this->indexes)) {
                $sql .= ', ' . implode(', ', $this->indexes);
            }
            
            if (!empty($this->foreignKeys)) {
                $keys = [];
                foreach ($this->foreignKeys as $fk) {
                    $keys[] = $fk->toSql();
                }
                $sql .= ', ' . implode(', ', $keys);
            }
            
            $sql .= ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
            
            return $sql;
        } else {
            // ALTER TABLE
            $alterations = [];
            
            foreach ($this->columns as $column) {
                if (strpos($column, 'DROP COLUMN') === 0) {
                    $alterations[] = $column;
                } elseif (strpos($column, 'CHANGE COLUMN') === 0) {
                    $alterations[] = $column;
                } else {
                    $alterations[] = "ADD COLUMN " . $column;
                }
            }
            
            if (empty($alterations)) {
                return '';
            }
            
            return "ALTER TABLE `{$this->table}` " . implode(', ', $alterations);
        }
    }
    
    private function extractColumnName($columnDef)
    {
        if (preg_match('/`([^`]+)`/', $columnDef, $matches)) {
            return $matches[1];
        }
        return '';
    }
}

class ForeignKeyBuilder
{
    private $column;
    private $references;
    private $on;
    private $onDelete;
    private $onUpdate;
    
    public function __construct($column)
    {
        $this->column = $column;
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
        $sql = "FOREIGN KEY (`{$this->column}`) REFERENCES `{$this->on}` (`{$this->references}`)";
        
        if ($this->onDelete) {
            $sql .= " ON DELETE {$this->onDelete}";
        }
        
        if ($this->onUpdate) {
            $sql .= " ON UPDATE {$this->onUpdate}";
        }
        
        return $sql;
    }
}

class MigrationManager
{
    private $connection;
    private $table = 'migrations';
    
    public function __construct($connection)
    {
        $this->connection = $connection;
        $this->ensureMigrationsTable();
    }
    
    private function ensureMigrationsTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `migration` VARCHAR(255) NOT NULL,
            `batch` INT UNSIGNED NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
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
    
    private function getPendingMigrations()
    {
        $ran = $this->getRanMigrations();
        $all = $this->getAllMigrations();
        
        return array_diff($all, $ran);
    }
    
    private function getRanMigrations()
    {
        $stmt = $this->connection->query("SELECT migration FROM `{$this->table}`");
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
            if (preg_match('/^(\d{4}_\d{2}_\d{2}_\d{6}_)(.+?)\.php$/', $file, $matches)) {
                $migrations[] = $matches[2];
            }
        }
        
        sort($migrations);
        return $migrations;
    }
    
    private function loadMigration($migration)
    {
        $files = glob(BASE_PATH . "/database/migrations/*_{$migration}.php");
        
        if (empty($files)) {
            throw new \Exception("Migration file not found: {$migration}");
        }
        
        require_once $files[0];
        
        $class = "App\\Database\\Migrations\\{$migration}";
        
        if (!class_exists($class)) {
            throw new \Exception("Migration class not found: {$class}");
        }
        
        return new $class($this->connection);
    }
    
    private function getNextBatchNumber()
    {
        $stmt = $this->connection->query("SELECT MAX(batch) as max_batch FROM `{$this->table}`");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($result['max_batch'] ?? 0) + 1;
    }
    
    private function recordMigration($migration, $batch)
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO `{$this->table}` (migration, batch) VALUES (?, ?)"
        );
        $stmt->execute([$migration, $batch]);
    }
    
    private function getLastBatchMigrations($steps)
    {
        $stmt = $this->connection->query(
            "SELECT id, migration FROM `{$this->table}` ORDER BY batch DESC, id DESC LIMIT ?"
        );
        $stmt->execute([$steps]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function deleteMigration($id)
    {
        $stmt = $this->connection->prepare("DELETE FROM `{$this->table}` WHERE id = ?");
        $stmt->execute([$id]);
    }
}

