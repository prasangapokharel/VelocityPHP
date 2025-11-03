<?php
/**
 * VelocityPhp Session Manager
 * Session handling wrapper with flash messages
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

namespace App\Utils;

class Session
{
    private static $started = false;
    
    public static function start(array $options = [])
    {
        if (self::$started) {
            return;
        }
        
        $defaultOptions = [
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'use_strict_mode' => true,
            'use_only_cookies' => true,
            'cookie_secure' => isset($_SERVER['HTTPS']),
            'sid_length' => 48,
            'sid_bits_per_character' => 6
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start($options);
            self::$started = true;
        }
        
        // Initialize flash messages storage
        if (!isset($_SESSION['_flash'])) {
            $_SESSION['_flash'] = ['old' => [], 'new' => []];
        }
    }
    
    public static function get($key, $default = null)
    {
        self::ensureStarted();
        return $_SESSION[$key] ?? $default;
    }
    
    public static function set($key, $value)
    {
        self::ensureStarted();
        $_SESSION[$key] = $value;
    }
    
    public static function has($key)
    {
        self::ensureStarted();
        return isset($_SESSION[$key]);
    }
    
    public static function remove($key)
    {
        self::ensureStarted();
        unset($_SESSION[$key]);
    }
    
    public static function forget($keys)
    {
        self::ensureStarted();
        $keys = is_array($keys) ? $keys : [$keys];
        
        foreach ($keys as $key) {
            unset($_SESSION[$key]);
        }
    }
    
    public static function flush()
    {
        self::ensureStarted();
        $_SESSION = [];
    }
    
    public static function all()
    {
        self::ensureStarted();
        return $_SESSION;
    }
    
    public static function flash($key, $value)
    {
        self::ensureStarted();
        $_SESSION['_flash']['new'][$key] = $value;
    }
    
    public static function getFlash($key, $default = null)
    {
        self::ensureStarted();
        
        // Check old flash data
        if (isset($_SESSION['_flash']['old'][$key])) {
            $value = $_SESSION['_flash']['old'][$key];
            unset($_SESSION['_flash']['old'][$key]);
            return $value;
        }
        
        return $default;
    }
    
    public static function hasFlash($key)
    {
        self::ensureStarted();
        return isset($_SESSION['_flash']['old'][$key]) || isset($_SESSION['_flash']['new'][$key]);
    }
    
    public static function keep($keys)
    {
        self::ensureStarted();
        $keys = is_array($keys) ? $keys : [$keys];
        
        foreach ($keys as $key) {
            if (isset($_SESSION['_flash']['old'][$key])) {
                $_SESSION['_flash']['new'][$key] = $_SESSION['_flash']['old'][$key];
            }
        }
    }
    
    public static function reflash()
    {
        self::ensureStarted();
        $_SESSION['_flash']['new'] = array_merge(
            $_SESSION['_flash']['new'],
            $_SESSION['_flash']['old']
        );
        $_SESSION['_flash']['old'] = [];
    }
    
    public static function ageFlashData()
    {
        self::ensureStarted();
        $_SESSION['_flash']['old'] = $_SESSION['_flash']['new'];
        $_SESSION['_flash']['new'] = [];
    }
    
    public static function id()
    {
        self::ensureStarted();
        return session_id();
    }
    
    public static function regenerate($deleteOldSession = false)
    {
        self::ensureStarted();
        return session_regenerate_id($deleteOldSession);
    }
    
    public static function destroy()
    {
        self::ensureStarted();
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        return session_destroy();
    }
    
    public static function pull($key, $default = null)
    {
        $value = self::get($key, $default);
        self::remove($key);
        return $value;
    }
    
    public static function put($key, $value)
    {
        return self::set($key, $value);
    }
    
    private static function ensureStarted()
    {
        if (!self::$started && session_status() === PHP_SESSION_NONE) {
            self::start();
        }
    }
}

