<?php
/**
 * VelocityPhp Configuration Manager
 * Environment-based config with caching
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

namespace App\Config;

class Config
{
    private static $config = [];
    private static $loaded = [];
    private static $cacheEnabled = true;
    private static $cachePath = null;
    
    public static function init()
    {
        // Try default location first
        $cachePath = BASE_PATH . '/storage/config';
        
        // Create directory if it doesn't exist (shared hosting compatible)
        if (!is_dir($cachePath)) {
            @mkdir($cachePath, 0777, true);
        }
        
        // Check if default location is writable
        if (is_dir($cachePath) && is_writable($cachePath)) {
            self::$cachePath = $cachePath;
        } else {
            // Fallback to system temp directory (shared hosting compatible)
            $fallbackPath = sys_get_temp_dir() . '/velocity_config/';
            if (!is_dir($fallbackPath)) {
                @mkdir($fallbackPath, 0777, true);
            }
            if (is_dir($fallbackPath) && is_writable($fallbackPath)) {
                self::$cachePath = $fallbackPath;
            } else {
                // Last resort: use system temp directly
                self::$cachePath = sys_get_temp_dir() . '/';
            }
        }
        
        // Load environment variables
        self::loadEnv();
    }
    
    public static function loadEnv($file = null)
    {
        $file = $file ?? BASE_PATH . '/.env';
        
        if (!file_exists($file)) {
            return;
        }
        
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse key=value
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                // Remove quotes if present
                $value = trim($value, '"\' ');
                
                // Set environment variable
                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    putenv(sprintf('%s=%s', $name, $value));
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }
    
    public static function get($key, $default = null)
    {
        $keys = explode('.', $key);
        $file = array_shift($keys);
        
        $config = self::loadFile($file);
        
        foreach ($keys as $segment) {
            if (!isset($config[$segment])) {
                return $default;
            }
            $config = $config[$segment];
        }
        
        return $config;
    }
    
    public static function set($key, $value)
    {
        $keys = explode('.', $key);
        $file = array_shift($keys);
        
        $config = self::loadFile($file);
        
        $current = &$config;
        foreach ($keys as $segment) {
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }
        
        $current = $value;
        
        self::$config[$file] = $config;
    }
    
    public static function has($key)
    {
        return self::get($key) !== null;
    }
    
    public static function all($file = null)
    {
        if ($file === null) {
            return self::$config;
        }
        
        return self::loadFile($file);
    }
    
    private static function loadFile($file)
    {
        if (isset(self::$config[$file])) {
            return self::$config[$file];
        }
        
        // Check cache
        if (self::$cacheEnabled) {
            $cached = self::loadFromCache($file);
            if ($cached !== null) {
                self::$config[$file] = $cached;
                return $cached;
            }
        }
        
        // Load from file
        $path = CONFIG_PATH . '/' . $file . '.php';
        
        if (!file_exists($path)) {
            self::$config[$file] = [];
            return [];
        }
        
        $config = require $path;
        self::$config[$file] = $config;
        
        // Cache it
        if (self::$cacheEnabled) {
            self::saveToCache($file, $config);
        }
        
        return $config;
    }
    
    private static function loadFromCache($file)
    {
        $cacheFile = self::$cachePath . '/' . $file . '.cache';
        
        if (!file_exists($cacheFile)) {
            return null;
        }
        
        $data = unserialize(file_get_contents($cacheFile));
        
        // Check if cache is still valid (compare with source file)
        $sourceFile = CONFIG_PATH . '/' . $file . '.php';
        if (file_exists($sourceFile) && filemtime($sourceFile) > $data['time']) {
            return null; // Cache expired
        }
        
        return $data['config'];
    }
    
    private static function saveToCache($file, $config)
    {
        $cacheFile = self::$cachePath . '/' . $file . '.cache';
        
        $data = [
            'time' => time(),
            'config' => $config
        ];
        
        file_put_contents($cacheFile, serialize($data));
    }
    
    public static function clearCache()
    {
        if (self::$cachePath && is_dir(self::$cachePath)) {
            $files = glob(self::$cachePath . '/*.cache');
            foreach ($files as $file) {
                unlink($file);
            }
        }
        
        self::$config = [];
        self::$loaded = [];
    }
    
    public static function enableCache($enabled = true)
    {
        self::$cacheEnabled = $enabled;
    }
    
    public static function env($key, $default = null)
    {
        $value = getenv($key);
        
        if ($value === false) {
            return $default;
        }
        
        // Convert string booleans
        if (in_array(strtolower($value), ['true', '1', 'yes', 'on'])) {
            return true;
        }
        
        if (in_array(strtolower($value), ['false', '0', 'no', 'off'])) {
            return false;
        }
        
        // Convert numeric strings
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float)$value : (int)$value;
        }
        
        return $value;
    }
}

