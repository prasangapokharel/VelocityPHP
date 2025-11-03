<?php
/**
 * VelocityPhp Query Builder
 * Fluent query builder for SELECT, INSERT, UPDATE, DELETE
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

namespace App\Database;

use PDO;
use PDOException;

class QueryBuilder
{
    private $connection;
    private $table;
    private $select = ['*'];
    private $from;
    private $joins = [];
    private $where = [];
    private $groupBy = [];
    private $having = [];
    private $orderBy = [];
    private $limit = null;
    private $offset = null;
    private $bindings = [];
    private $type = 'select';
    private $insertData = [];
    private $updateData = [];
    
    public function __construct($connection, $table = null)
    {
        $this->connection = $connection;
        $this->table = $table;
        $this->from = $table;
    }
    
    public static function table($connection, $table)
    {
        return new self($connection, $table);
    }
    
    public function select($columns = ['*'])
    {
        $this->select = is_array($columns) ? $columns : func_get_args();
        return $this;
    }
    
    public function from($table)
    {
        $this->from = $table;
        return $this;
    }
    
    public function join($table, $first, $operator = null, $second = null, $type = 'INNER')
    {
        if (is_callable($table)) {
            $join = new JoinBuilder();
            $table($join);
            $this->joins[] = ['type' => 'INNER', 'clause' => $join->build()];
        } else {
            $this->joins[] = [
                'type' => strtoupper($type),
                'table' => $table,
                'first' => $first,
                'operator' => $operator,
                'second' => $second
            ];
        }
        return $this;
    }
    
    public function leftJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }
    
    public function rightJoin($table, $first, $operator = null, $second = null)
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }
    
    public function where($column, $operator = null, $value = null, $boolean = 'AND')
    {
        if (is_array($column)) {
            foreach ($column as $key => $val) {
                $this->where($key, '=', $val, $boolean);
            }
            return $this;
        }
        
        if (is_callable($column)) {
            $query = new self($this->connection);
            $column($query);
            $this->where[] = [
                'type' => 'nested',
                'query' => $query,
                'boolean' => $boolean
            ];
            return $this;
        }
        
        if ($operator === null) {
            $operator = '=';
        }
        
        if ($value === null && $operator !== 'IS NULL' && $operator !== 'IS NOT NULL') {
            $value = $operator;
            $operator = '=';
        }
        
        $this->where[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean
        ];
        
        if ($value !== null && !in_array($operator, ['IS NULL', 'IS NOT NULL'])) {
            $this->bindings[] = $value;
        }
        
        return $this;
    }
    
    public function orWhere($column, $operator = null, $value = null)
    {
        return $this->where($column, $operator, $value, 'OR');
    }
    
    public function whereIn($column, $values, $boolean = 'AND')
    {
        $this->where[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean
        ];
        
        foreach ($values as $value) {
            $this->bindings[] = $value;
        }
        
        return $this;
    }
    
    public function whereNull($column, $boolean = 'AND')
    {
        return $this->where($column, 'IS NULL', null, $boolean);
    }
    
    public function whereNotNull($column, $boolean = 'AND')
    {
        return $this->where($column, 'IS NOT NULL', null, $boolean);
    }
    
    public function groupBy($columns)
    {
        $this->groupBy = is_array($columns) ? $columns : func_get_args();
        return $this;
    }
    
    public function having($column, $operator, $value, $boolean = 'AND')
    {
        $this->having[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean
        ];
        $this->bindings[] = $value;
        return $this;
    }
    
    public function orderBy($column, $direction = 'ASC')
    {
        $this->orderBy[] = [
            'column' => $column,
            'direction' => strtoupper($direction)
        ];
        return $this;
    }
    
    public function limit($limit)
    {
        $this->limit = (int)$limit;
        return $this;
    }
    
    public function offset($offset)
    {
        $this->offset = (int)$offset;
        return $this;
    }
    
    public function insert(array $data)
    {
        $this->type = 'insert';
        $this->insertData = is_array($data[0] ?? null) ? $data : [$data];
        return $this->execute();
    }
    
    public function update(array $data)
    {
        $this->type = 'update';
        $this->updateData = $data;
        return $this->execute();
    }
    
    public function delete()
    {
        $this->type = 'delete';
        return $this->execute();
    }
    
    public function get()
    {
        $this->type = 'select';
        $sql = $this->toSql();
        return $this->executeQuery($sql);
    }
    
    public function first()
    {
        $results = $this->limit(1)->get();
        return $results[0] ?? null;
    }
    
    public function count()
    {
        $this->select = ['COUNT(*) as count'];
        $result = $this->first();
        return $result ? (int)$result['count'] : 0;
    }
    
    public function toSql()
    {
        switch ($this->type) {
            case 'insert':
                return $this->compileInsert();
            case 'update':
                return $this->compileUpdate();
            case 'delete':
                return $this->compileDelete();
            default:
                return $this->compileSelect();
        }
    }
    
    private function compileSelect()
    {
        $sql = 'SELECT ' . implode(', ', $this->select) . ' FROM ' . $this->escapeIdentifier($this->from);
        
        // Joins
        foreach ($this->joins as $join) {
            $sql .= ' ' . $join['type'] . ' JOIN ' . $this->escapeIdentifier($join['table']);
            $sql .= ' ON ' . $this->escapeIdentifier($join['first']) . ' ' . $join['operator'] . ' ' . $this->escapeIdentifier($join['second']);
        }
        
        // Where
        if (!empty($this->where)) {
            $sql .= ' WHERE ' . $this->compileWhere();
        }
        
        // Group By
        if (!empty($this->groupBy)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBy);
        }
        
        // Having
        if (!empty($this->having)) {
            $sql .= ' HAVING ' . $this->compileHaving();
        }
        
        // Order By
        if (!empty($this->orderBy)) {
            $orders = [];
            foreach ($this->orderBy as $order) {
                $orders[] = $this->escapeIdentifier($order['column']) . ' ' . $order['direction'];
            }
            $sql .= ' ORDER BY ' . implode(', ', $orders);
        }
        
        // Limit
        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }
        
        // Offset
        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . $this->offset;
        }
        
        return $sql;
    }
    
    private function compileInsert()
    {
        if (empty($this->insertData)) {
            return '';
        }
        
        $first = $this->insertData[0];
        $columns = array_keys($first);
        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        
        $values = [];
        foreach ($this->insertData as $row) {
            foreach ($columns as $col) {
                $values[] = $row[$col];
            }
        }
        
        $this->bindings = array_merge($this->bindings, $values);
        
        $sql = 'INSERT INTO ' . $this->escapeIdentifier($this->table);
        $sql .= ' (' . implode(', ', array_map([$this, 'escapeIdentifier'], $columns)) . ')';
        $sql .= ' VALUES ' . implode(', ', array_fill(0, count($this->insertData), $placeholders));
        
        return $sql;
    }
    
    private function compileUpdate()
    {
        $set = [];
        $values = [];
        
        foreach ($this->updateData as $column => $value) {
            $set[] = $this->escapeIdentifier($column) . ' = ?';
            $values[] = $value;
        }
        
        $this->bindings = array_merge($values, $this->bindings);
        
        $sql = 'UPDATE ' . $this->escapeIdentifier($this->table);
        $sql .= ' SET ' . implode(', ', $set);
        
        if (!empty($this->where)) {
            $sql .= ' WHERE ' . $this->compileWhere();
        }
        
        return $sql;
    }
    
    private function compileDelete()
    {
        $sql = 'DELETE FROM ' . $this->escapeIdentifier($this->table);
        
        if (!empty($this->where)) {
            $sql .= ' WHERE ' . $this->compileWhere();
        }
        
        return $sql;
    }
    
    private function compileWhere()
    {
        $where = [];
        
        foreach ($this->where as $index => $condition) {
            if ($index > 0) {
                $where[] = $condition['boolean'];
            }
            
            if ($condition['type'] === 'basic') {
                if (in_array($condition['operator'], ['IS NULL', 'IS NOT NULL'])) {
                    $where[] = $this->escapeIdentifier($condition['column']) . ' ' . $condition['operator'];
                } else {
                    $where[] = $this->escapeIdentifier($condition['column']) . ' ' . $condition['operator'] . ' ?';
                }
            } elseif ($condition['type'] === 'in') {
                $placeholders = implode(', ', array_fill(0, count($condition['values']), '?'));
                $where[] = $this->escapeIdentifier($condition['column']) . ' IN (' . $placeholders . ')';
            } elseif ($condition['type'] === 'nested') {
                $where[] = '(' . $condition['query']->compileWhere() . ')';
                $this->bindings = array_merge($this->bindings, $condition['query']->bindings);
            }
        }
        
        return implode(' ', $where);
    }
    
    private function compileHaving()
    {
        $having = [];
        
        foreach ($this->having as $index => $condition) {
            if ($index > 0) {
                $having[] = $condition['boolean'];
            }
            $having[] = $this->escapeIdentifier($condition['column']) . ' ' . $condition['operator'] . ' ?';
        }
        
        return implode(' ', $having);
    }
    
    private function execute()
    {
        $sql = $this->toSql();
        
        try {
            $stmt = $this->connection->prepare($sql);
            $result = $stmt->execute($this->bindings);
            
            if ($this->type === 'insert') {
                return $this->connection->lastInsertId();
            }
            
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new \Exception("Query execution failed: " . $e->getMessage());
        }
    }
    
    private function executeQuery($sql)
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($this->bindings);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \Exception("Query execution failed: " . $e->getMessage());
        }
    }
    
    private function escapeIdentifier($identifier)
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }
}

class JoinBuilder
{
    private $clauses = [];
    
    public function on($first, $operator, $second)
    {
        $this->clauses[] = [
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
            'boolean' => 'AND'
        ];
        return $this;
    }
    
    public function orOn($first, $operator, $second)
    {
        $this->clauses[] = [
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
            'boolean' => 'OR'
        ];
        return $this;
    }
    
    public function build()
    {
        $on = [];
        foreach ($this->clauses as $clause) {
            if (count($on) > 0) {
                $on[] = $clause['boolean'];
            }
            $on[] = $clause['first'] . ' ' . $clause['operator'] . ' ' . $clause['second'];
        }
        return implode(' ', $on);
    }
}

