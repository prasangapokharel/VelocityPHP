<?php
/**
 * FileCache - Per-User/IP File-Based Caching System
 * 
 * Flow:
 *   Database → (only when data changes) → Cache (file per user/ip) → (fast read) → Client
 * 
 * Features:
 * - One cache file per user/IP for fast reads
 * - Auto-invalidation on data updates
 * - No database queries for cached data
 * - Simple JSON file storage
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

namespace App\Utils;

class FileCache
{
    private static ?FileCache $instance = null;
    private string $basePath;
    private int $defaultTtl = 3600; // 1 hour default
    
    /**
     * Cache directories
     */
    private array $directories = [
        'users' => 'users',      // Per-user cache
        'ip' => 'ip',            // Per-IP cache  
        'data' => 'data',        // General data cache
        'pages' => 'pages',      // Page cache
        'api' => 'api',          // API response cache
    ];
    
    private function __construct()
    {
        $this->basePath = defined('BASE_PATH') 
            ? BASE_PATH . '/src/velocache'
            : dirname(__DIR__) . '/velocache';
            
        $this->ensureDirectories();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Ensure all cache directories exist
     */
    private function ensureDirectories(): void
    {
        foreach ($this->directories as $dir) {
            $path = $this->basePath . '/' . $dir;
            if (!is_dir($path)) {
                @mkdir($path, 0755, true);
            }
        }
    }
    
    // =========================================================================
    // CORE READ/WRITE METHODS
    // =========================================================================
    
    /**
     * Get cached data - returns null if not exists or expired
     * 
     * @param string $key Cache key
     * @param string $type Cache type (users, ip, data, pages, api)
     * @return mixed|null Cached data or null
     */
    public function get(string $key, string $type = 'data'): mixed
    {
        $file = $this->getFilePath($key, $type);
        
        if (!file_exists($file)) {
            return null;
        }
        
        $content = @file_get_contents($file);
        if ($content === false) {
            return null;
        }
        
        $data = json_decode($content, true);
        if (!is_array($data)) {
            return null;
        }
        
        // Check expiration
        if (isset($data['_expires']) && $data['_expires'] < time()) {
            $this->delete($key, $type);
            return null;
        }
        
        return $data['_data'] ?? $data;
    }
    
    /**
     * Set cache data
     * 
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param string $type Cache type
     * @param int|null $ttl Time to live in seconds (null = default)
     * @return bool Success
     */
    public function set(string $key, mixed $data, string $type = 'data', ?int $ttl = null): bool
    {
        $file = $this->getFilePath($key, $type);
        $ttl = $ttl ?? $this->defaultTtl;
        
        $cacheData = [
            '_data' => $data,
            '_created' => time(),
            '_expires' => time() + $ttl,
        ];
        
        $result = @file_put_contents(
            $file, 
            json_encode($cacheData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            LOCK_EX
        );
        
        return $result !== false;
    }
    
    /**
     * Delete cache
     */
    public function delete(string $key, string $type = 'data'): bool
    {
        $file = $this->getFilePath($key, $type);
        
        if (file_exists($file)) {
            return @unlink($file);
        }
        
        return true;
    }
    
    /**
     * Check if cache exists and is valid
     */
    public function has(string $key, string $type = 'data'): bool
    {
        return $this->get($key, $type) !== null;
    }
    
    // =========================================================================
    // USER-SPECIFIC CACHING
    // =========================================================================
    
    /**
     * Get user cache by user ID
     */
    public function getUser(int $userId): mixed
    {
        return $this->get("user_{$userId}", 'users');
    }
    
    /**
     * Set user cache
     */
    public function setUser(int $userId, mixed $data, ?int $ttl = null): bool
    {
        return $this->set("user_{$userId}", $data, 'users', $ttl);
    }
    
    /**
     * Delete user cache (call when user data changes)
     */
    public function deleteUser(int $userId): bool
    {
        return $this->delete("user_{$userId}", 'users');
    }
    
    /**
     * Get user data with database fallback
     * 
     * @param int $userId User ID
     * @param callable $fetchCallback Callback to fetch from database if cache miss
     * @param int|null $ttl Cache TTL
     * @return mixed|null User data or null if not found
     */
    public function getUserWithFallback(int $userId, callable $fetchCallback, ?int $ttl = null): mixed
    {
        // Try cache first
        $cached = $this->getUser($userId);
        if ($cached !== null) {
            return $cached;
        }
        
        // Fallback to database
        $data = $fetchCallback($userId);
        
        // Cache the result only if found (not null/false)
        if ($data !== null && $data !== false) {
            $this->setUser($userId, $data, $ttl);
            return $data;
        }
        
        return null;
    }
    
    // =========================================================================
    // IP-SPECIFIC CACHING
    // =========================================================================
    
    /**
     * Get current client IP
     */
    public function getClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Get IP-based cache
     */
    public function getByIp(?string $ip = null): mixed
    {
        $ip = $ip ?? $this->getClientIp();
        $key = md5($ip);
        return $this->get($key, 'ip');
    }
    
    /**
     * Set IP-based cache
     */
    public function setByIp(mixed $data, ?string $ip = null, ?int $ttl = null): bool
    {
        $ip = $ip ?? $this->getClientIp();
        $key = md5($ip);
        return $this->set($key, $data, 'ip', $ttl);
    }
    
    /**
     * Delete IP-based cache
     */
    public function deleteByIp(?string $ip = null): bool
    {
        $ip = $ip ?? $this->getClientIp();
        $key = md5($ip);
        return $this->delete($key, 'ip');
    }
    
    /**
     * Get IP data with database fallback
     */
    public function getByIpWithFallback(callable $fetchCallback, ?string $ip = null, ?int $ttl = null): mixed
    {
        $ip = $ip ?? $this->getClientIp();
        
        // Try cache first
        $cached = $this->getByIp($ip);
        if ($cached !== null) {
            return $cached;
        }
        
        // Fallback to database
        $data = $fetchCallback($ip);
        
        // Cache the result only if found (not null/false)
        if ($data !== null && $data !== false) {
            $this->setByIp($data, $ip, $ttl);
            return $data;
        }
        
        return null;
    }
    
    // =========================================================================
    // REMEMBER PATTERN (Laravel-style)
    // =========================================================================
    
    /**
     * Get from cache or execute callback and cache result
     * Note: Will NOT cache null/false results - only caches truthy data
     * 
     * @param string $key Cache key
     * @param callable $callback Callback to generate data if cache miss
     * @param int|null $ttl Cache TTL
     * @param string $type Cache type
     * @return mixed Cached or fresh data
     */
    public function remember(string $key, callable $callback, ?int $ttl = null, string $type = 'data'): mixed
    {
        $cached = $this->get($key, $type);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $data = $callback();
        
        // Only cache non-null, non-false values
        if ($data !== null && $data !== false) {
            $this->set($key, $data, $type, $ttl);
        }
        
        return $data;
    }
    
    /**
     * Remember forever (no expiration)
     */
    public function rememberForever(string $key, callable $callback, string $type = 'data'): mixed
    {
        return $this->remember($key, $callback, 86400 * 365, $type); // 1 year
    }
    
    // =========================================================================
    // CACHE INVALIDATION
    // =========================================================================
    
    /**
     * Invalidate all caches for a user
     * Call this when user data changes in database
     */
    public function invalidateUser(int $userId): void
    {
        $this->deleteUser($userId);
        
        // Also delete any related caches
        $pattern = $this->basePath . '/data/user_' . $userId . '_*.json';
        foreach (glob($pattern) as $file) {
            @unlink($file);
        }
    }
    
    /**
     * Invalidate cache by pattern
     */
    public function invalidatePattern(string $pattern, string $type = 'data'): int
    {
        $dir = $this->basePath . '/' . ($this->directories[$type] ?? 'data');
        $fullPattern = $dir . '/' . $pattern . '.json';
        
        $count = 0;
        foreach (glob($fullPattern) as $file) {
            if (@unlink($file)) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Clear all cache of a specific type
     */
    public function clearType(string $type): int
    {
        $dir = $this->basePath . '/' . ($this->directories[$type] ?? $type);
        
        if (!is_dir($dir)) {
            return 0;
        }
        
        $count = 0;
        foreach (glob($dir . '/*.json') as $file) {
            if (@unlink($file)) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Clear all caches
     */
    public function clearAll(): int
    {
        $count = 0;
        foreach ($this->directories as $type => $dir) {
            $count += $this->clearType($type);
        }
        return $count;
    }
    
    /**
     * Clean expired caches
     */
    public function cleanExpired(): int
    {
        $count = 0;
        
        foreach ($this->directories as $type => $dir) {
            $path = $this->basePath . '/' . $dir;
            if (!is_dir($path)) continue;
            
            foreach (glob($path . '/*.json') as $file) {
                $content = @file_get_contents($file);
                if ($content === false) continue;
                
                $data = json_decode($content, true);
                if (isset($data['_expires']) && $data['_expires'] < time()) {
                    if (@unlink($file)) {
                        $count++;
                    }
                }
            }
        }
        
        return $count;
    }
    
    // =========================================================================
    // UTILITY METHODS
    // =========================================================================
    
    /**
     * Get file path for cache key
     */
    private function getFilePath(string $key, string $type): string
    {
        $dir = $this->directories[$type] ?? 'data';
        $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        return $this->basePath . '/' . $dir . '/' . $safeKey . '.json';
    }
    
    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        $stats = [
            'total_files' => 0,
            'total_size' => 0,
            'by_type' => [],
        ];
        
        foreach ($this->directories as $type => $dir) {
            $path = $this->basePath . '/' . $dir;
            if (!is_dir($path)) continue;
            
            $files = glob($path . '/*.json');
            $count = count($files);
            $size = 0;
            
            foreach ($files as $file) {
                $size += filesize($file);
            }
            
            $stats['by_type'][$type] = [
                'files' => $count,
                'size' => $size,
                'size_human' => $this->formatBytes($size),
            ];
            
            $stats['total_files'] += $count;
            $stats['total_size'] += $size;
        }
        
        $stats['total_size_human'] = $this->formatBytes($stats['total_size']);
        
        return $stats;
    }
    
    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Set default TTL
     */
    public function setDefaultTtl(int $seconds): self
    {
        $this->defaultTtl = $seconds;
        return $this;
    }
}
