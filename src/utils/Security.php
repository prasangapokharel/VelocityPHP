<?php
/**
 * VelocityPhp Security Utilities
 * CSRF, XSS prevention, password hashing, input escaping
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

namespace App\Utils;

class Security
{
    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCsrfToken($token)
    {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Get CSRF token field HTML
     */
    public static function csrfField()
    {
        $token = self::generateCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Get CSRF token for meta tag
     */
    public static function csrfMeta()
    {
        $token = self::generateCsrfToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    /**
     * Escape HTML output
     */
    public static function escape($value, $flags = ENT_QUOTES, $encoding = 'UTF-8')
    {
        if (is_array($value)) {
            return array_map(function($item) use ($flags, $encoding) {
                return self::escape($item, $flags, $encoding);
            }, $value);
        }
        
        return htmlspecialchars($value, $flags, $encoding);
    }
    
    /**
     * Clean XSS from string
     */
    public static function cleanXss($value)
    {
        if (is_array($value)) {
            return array_map([self::class, 'cleanXss'], $value);
        }
        
        // Remove null bytes
        $value = str_replace(chr(0), '', $value);
        
        // Remove script tags and their content
        $value = preg_replace('#<script[^>]*>.*?</script>#is', '', $value);
        
        // Remove javascript: protocols
        $value = preg_replace('#javascript:#i', '', $value);
        
        // Remove on* event handlers
        $value = preg_replace('#on\w+\s*=\s*["\']?[^"\']*["\']?#i', '', $value);
        
        // HTML escape
        return self::escape($value);
    }
    
    /**
     * Hash password using bcrypt
     */
    public static function hashPassword($password, $options = [])
    {
        $cost = $options['cost'] ?? 12;
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost]);
    }
    
    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }
    
    /**
     * Generate random string
     */
    public static function randomString($length = 32, $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789')
    {
        $random = '';
        $max = strlen($characters) - 1;
        
        for ($i = 0; $i < $length; $i++) {
            $random .= $characters[random_int(0, $max)];
        }
        
        return $random;
    }
    
    /**
     * Sanitize input
     */
    public static function sanitize($value, $type = 'string')
    {
        if (is_array($value)) {
            return array_map(function($item) use ($type) {
                return self::sanitize($item, $type);
            }, $value);
        }
        
        switch ($type) {
            case 'string':
                return trim(strip_tags($value));
            case 'email':
                return filter_var($value, FILTER_SANITIZE_EMAIL);
            case 'url':
                return filter_var($value, FILTER_SANITIZE_URL);
            case 'int':
                return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            case 'html':
                return self::cleanXss($value);
            default:
                return $value;
        }
    }
    
    /**
     * Validate email
     */
    public static function isValidEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate URL
     */
    public static function isValidUrl($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Check if string contains SQL injection patterns
     */
    public static function detectSqlInjection($value)
    {
        $patterns = [
            '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|EXEC|EXECUTE)\b)/i',
            '/(\b(UNION|OR|AND)\b.*\b(SELECT|INSERT|UPDATE|DELETE)\b)/i',
            '/(;\s*(DROP|DELETE|UPDATE|INSERT))/i',
            "/(['\"]\s*;\s*--)/i",
            "/(--\s*)/i",
            "/(\/\*.*?\*\/)/i"
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get secure random token
     */
    public static function generateToken($length = 32)
    {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Encrypt data (simple base64, use proper encryption in production)
     */
    public static function encrypt($data, $key = null)
    {
        if ($key === null) {
            $key = getenv('APP_KEY') ?: 'default-key-change-in-production';
        }
        
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt data
     */
    public static function decrypt($data, $key = null)
    {
        if ($key === null) {
            $key = getenv('APP_KEY') ?: 'default-key-change-in-production';
        }
        
        $data = base64_decode($data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
}

