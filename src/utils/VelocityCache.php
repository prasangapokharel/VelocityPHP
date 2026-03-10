<?php
/**
 * VelocityCache - SQLite-based Caching System
 * Implements cache workflow: Check cache first for GET, invalidate on POST/PUT/DELETE
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

namespace App\Utils;

use App\Config\Config;
use PDO;
use PDOException;

class VelocityCache
{
    private static $instance = null;
    private $pdo = null;
    private $cacheLocation = null;
    private $enabled = true;
    
    private function __construct()
    {
        $cachePath = Config::env('CACHE_LOCATION', BASE_PATH . '/src/velocache/velocity.db');
        
        if (!empty($cachePath)) {
            $isAbsolute = (
                (strlen($cachePath) > 1 && $cachePath[1] === ':') ||
                $cachePath[0] === '/' ||
                $cachePath[0] === '\\'
            );
            
            if (!$isAbsolute) {
                $this->cacheLocation = BASE_PATH . '/' . ltrim(str_replace('\\', '/', $cachePath), '/');
            } else {
                $this->cacheLocation = str_replace('\\', '/', $cachePath);
            }
        } else {
            $this->cacheLocation = BASE_PATH . '/src/velocache/velocity.db';
        }
        
        $this->enabled = Config::env('CACHE_ENABLED', true) !== false;
        
        if ($this->enabled) {
            $this->initDatabase();
        }
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function initDatabase()
    {
        try {
            $dir = dirname($this->cacheLocation);
            
            // Create directory if it doesn't exist (shared hosting compatible)
            if (!is_dir($dir)) {
                // Use default umask (shared hosting compatible - no explicit chmod)
                if (!@mkdir($dir, 0777, true)) {
                    // Try alternative location if default fails (shared hosting fallback)
                    $fallbackDir = sys_get_temp_dir() . '/velocity_cache';
                    if (!is_dir($fallbackDir)) {
                        @mkdir($fallbackDir, 0777, true);
                    }
                    if (is_dir($fallbackDir) && is_writable($fallbackDir)) {
                        $this->cacheLocation = $fallbackDir . '/velocity_' . md5(BASE_PATH) . '.db';
                        $dir = $fallbackDir;
                    } else {
                        throw new \Exception("Failed to create cache directory");
                    }
                }
            }
            
            // Check if directory is writable (shared hosting may have restrictions)
            if (!is_writable($dir)) {
                // Try alternative location
                $fallbackDir = sys_get_temp_dir() . '/velocity_cache';
                if (!is_dir($fallbackDir)) {
                    @mkdir($fallbackDir, 0777, true);
                }
                if (is_dir($fallbackDir) && is_writable($fallbackDir)) {
                    $this->cacheLocation = $fallbackDir . '/velocity_' . md5(BASE_PATH) . '.db';
                } else {
                    throw new \Exception("Cache directory is not writable");
                }
            }
            
            // Initialize SQLite database
            $this->pdo = new PDO('sqlite:' . $this->cacheLocation);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->createTables();
        } catch (PDOException $e) {
            $this->enabled = false;
            Logger::error('Cache initialization failed', ['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            $this->enabled = false;
            Logger::error('Cache initialization failed', ['error' => $e->getMessage()]);
        }
    }
    
    private function createTables()
    {
        $sql = "CREATE TABLE IF NOT EXISTS cache_entries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            cache_key TEXT UNIQUE NOT NULL,
            cache_value TEXT NOT NULL,
            expires_at INTEGER NOT NULL,
            created_at INTEGER NOT NULL,
            updated_at INTEGER NOT NULL
        )";
        
        $this->pdo->exec($sql);
        
        $sql = "CREATE INDEX IF NOT EXISTS idx_cache_key ON cache_entries(cache_key)";
        $this->pdo->exec($sql);
        
        $sql = "CREATE INDEX IF NOT EXISTS idx_expires_at ON cache_entries(expires_at)";
        $this->pdo->exec($sql);
    }
    
    public function get($key, $default = null)
    {
        if (!$this->enabled || $this->pdo === null) {
            return $default;
        }
        
        static $getStmt = null;
        
        try {
            // Use cached prepared statement
            if ($getStmt === null) {
                $getStmt = $this->pdo->prepare("SELECT cache_value, expires_at FROM cache_entries WHERE cache_key = ? LIMIT 1");
            }
            
            $getStmt->execute([$key]);
            $row = $getStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$row) {
                return $default;
            }
            
            $now = time();
            if ($row['expires_at'] < $now) {
                // Async cleanup - don't block request
                $this->forget($key);
                return $default;
            }
            
            return json_decode($row['cache_value'], true);
        } catch (PDOException $e) {
            Logger::error('Cache get error', ['key' => $key, 'error' => $e->getMessage()]);
            return $default;
        }
    }
    
    public function put($key, $value, $ttl = 3600)
    {
        if (!$this->enabled || $this->pdo === null) {
            return false;
        }
        
        static $putStmt = null;
        
        try {
            // Use cached prepared statement
            if ($putStmt === null) {
                $putStmt = $this->pdo->prepare("
                    INSERT OR REPLACE INTO cache_entries 
                    (cache_key, cache_value, expires_at, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?)
                ");
            }
            
            $now = time();
            $expiresAt = $now + $ttl;
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            
            $putStmt->execute([$key, $encoded, $expiresAt, $now, $now]);
            return true;
        } catch (PDOException $e) {
            Logger::error('Cache put error', ['key' => $key, 'error' => $e->getMessage()]);
            return false;
        }
    }
    
    public function forget($key)
    {
        if (!$this->enabled || $this->pdo === null) {
            return false;
        }
        
        try {
            $stmt = $this->pdo->prepare("DELETE FROM cache_entries WHERE cache_key = ?");
            $stmt->execute([$key]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function forgetPattern($pattern)
    {
        if (!$this->enabled || $this->pdo === null) {
            return false;
        }
        
        try {
            $pattern = str_replace('*', '%', $pattern);
            if (substr($pattern, -1) !== '%') {
                $pattern = $pattern . '%';
            }
            $stmt = $this->pdo->prepare("DELETE FROM cache_entries WHERE cache_key LIKE ?");
            $stmt->execute([$pattern]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function flush()
    {
        if (!$this->enabled || $this->pdo === null) {
            return false;
        }
        
        try {
            $this->pdo->exec("DELETE FROM cache_entries");
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function clean()
    {
        if (!$this->enabled || $this->pdo === null) {
            return false;
        }
        
        try {
            $now = time();
            $stmt = $this->pdo->prepare("DELETE FROM cache_entries WHERE expires_at < ?");
            $stmt->execute([$now]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function generateKey($uri, $method = 'GET', $params = [])
    {
        $key = strtoupper($method) . ':' . $uri;
        
        if (!empty($params)) {
            $filteredParams = [];
            foreach ($params as $k => $v) {
                if ($k !== '_' && strpos($k, '_') !== 0) {
                    $filteredParams[$k] = $v;
                }
            }
            if (!empty($filteredParams)) {
                ksort($filteredParams);
                $key .= ':' . md5(http_build_query($filteredParams));
            }
        }
        
        return $key;
    }
    
    public function getStats()
    {
        if (!$this->enabled || $this->pdo === null) {
            return ['enabled' => false];
        }
        
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM cache_entries WHERE expires_at > " . time());
            $active = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM cache_entries");
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            return [
                'enabled' => true,
                'active_entries' => (int)$active,
                'total_entries' => (int)$total,
                'expired_entries' => (int)$total - (int)$active
            ];
        } catch (PDOException $e) {
            return ['enabled' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get an item from cache or compute and store it.
     *
     * If the key does not exist (or has expired), the callback is invoked,
     * its return value is stored for $ttl seconds, and then returned.
     *
     * @param string   $key      Cache key
     * @param int      $ttl      Time-to-live in seconds (default 3600)
     * @param callable $callback Value factory; called only on cache miss
     * @return mixed
     */
    public function remember(string $key, int $ttl, callable $callback)
    {
        $cached = $this->get($key);

        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();
        $this->put($key, $value, $ttl);

        return $value;
    }

    /**
     * Get an item from cache or compute and store it forever (no expiry).
     *
     * @param string   $key
     * @param callable $callback
     * @return mixed
     */
    public function rememberForever(string $key, callable $callback)
    {
        return $this->remember($key, PHP_INT_MAX, $callback);
    }

    /**
     * Increment a numeric cache value by the given amount.
     *
     * @param string $key
     * @param int    $by
     * @return int The new value, or 0 on failure
     */
    public function increment(string $key, int $by = 1): int
    {
        $current = (int) ($this->get($key) ?? 0);
        $new     = $current + $by;
        $this->put($key, $new, 86400);
        return $new;
    }

    /**
     * Decrement a numeric cache value by the given amount.
     *
     * @param string $key
     * @param int    $by
     * @return int The new value
     */
    public function decrement(string $key, int $by = 1): int
    {
        return $this->increment($key, -$by);
    }

    public function isEnabled()
    {
        return $this->enabled;
    }
}

