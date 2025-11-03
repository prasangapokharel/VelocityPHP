<?php
/**
 * VelocityPhp Ultra-Secure Authentication
 * Military-grade security with persistent sessions
 * 
 * @package VelocityPhp
 */

namespace App\Utils;

use App\Models\UserModel;

class Auth
{
    private static $user = null;
    private static $sessionName = 'velocityphp_session';
    private static $rememberCookie = 'velocityphp_remember';
    
    /**
     * Login user with optional "remember me"
     */
    public static function login($email, $password, $remember = false)
    {
        $userModel = new UserModel();
        $user = $userModel->findByEmail($email);
        
        // Verify user exists and password matches
        if (!$user || !password_verify($password, $user['password'])) {
            sleep(1); // Prevent brute force attacks
            return false;
        }
        
        // Check if account is active
        if ($user['status'] !== 'active') {
            return false;
        }
        
        // Create secure session
        self::createSession($user['id']);
        
        // Create remember me token if requested
        if ($remember) {
            self::createRememberToken($user['id']);
        }
        
        // Update last login
        $userModel->update($user['id'], [
            'last_login' => date('Y-m-d H:i:s')
        ]);
        
        self::$user = $user;
        return true;
    }
    
    /**
     * Create secure session
     */
    private static function createSession($userId)
    {
        // Regenerate session ID to prevent fixation attacks
        session_regenerate_id(true);
        
        // Store user data in session
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_ip'] = self::getIp();
        $_SESSION['user_agent'] = self::getUserAgent();
        $_SESSION['created_at'] = time();
        $_SESSION['last_activity'] = time();
        
        // Generate session token for extra security
        $_SESSION['session_token'] = bin2hex(random_bytes(32));
    }
    
    /**
     * Create remember me token (persistent login)
     */
    private static function createRememberToken($userId)
    {
        // Generate secure token
        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));
        $token = $selector . ':' . $validator;
        
        // Hash validator before storing
        $hashedValidator = password_hash($validator, PASSWORD_DEFAULT);
        
        // Store in database
        $db = (new UserModel())->query(
            "INSERT INTO remember_tokens (user_id, selector, hashed_validator, expires_at) 
             VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))",
            [$userId, $selector, $hashedValidator]
        );
        
        // Set secure cookie (30 days)
        setcookie(
            self::$rememberCookie,
            $token,
            [
                'expires' => time() + (30 * 86400),
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }
    
    /**
     * Check if user is authenticated
     */
    public static function check()
    {
        // Check session first
        if (self::checkSession()) {
            return true;
        }
        
        // Check remember me cookie
        if (self::checkRememberToken()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check session validity
     */
    private static function checkSession()
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Verify IP hasn't changed (prevent session hijacking)
        if ($_SESSION['user_ip'] !== self::getIp()) {
            self::logout();
            return false;
        }
        
        // Verify user agent hasn't changed
        if ($_SESSION['user_agent'] !== self::getUserAgent()) {
            self::logout();
            return false;
        }
        
        // Check session timeout (2 hours)
        if (time() - $_SESSION['last_activity'] > 7200) {
            self::logout();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Check remember me token
     */
    private static function checkRememberToken()
    {
        if (!isset($_COOKIE[self::$rememberCookie])) {
            return false;
        }
        
        $token = $_COOKIE[self::$rememberCookie];
        list($selector, $validator) = explode(':', $token);
        
        // Get token from database
        $result = (new UserModel())->query(
            "SELECT * FROM remember_tokens 
             WHERE selector = ? AND expires_at > NOW() LIMIT 1",
            [$selector]
        );
        
        if (empty($result)) {
            return false;
        }
        
        $tokenData = $result[0];
        
        // Verify validator
        if (!password_verify($validator, $tokenData['hashed_validator'])) {
            // Invalid token - delete it
            self::deleteRememberToken($selector);
            return false;
        }
        
        // Valid token - create new session
        self::createSession($tokenData['user_id']);
        
        // Refresh token for security
        self::deleteRememberToken($selector);
        self::createRememberToken($tokenData['user_id']);
        
        return true;
    }
    
    /**
     * Get authenticated user
     */
    public static function user()
    {
        if (self::$user !== null) {
            return self::$user;
        }
        
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        $userModel = new UserModel();
        self::$user = $userModel->find($_SESSION['user_id']);
        
        return self::$user;
    }
    
    /**
     * Logout user
     */
    public static function logout()
    {
        // Delete remember token
        if (isset($_COOKIE[self::$rememberCookie])) {
            $token = $_COOKIE[self::$rememberCookie];
            list($selector) = explode(':', $token);
            self::deleteRememberToken($selector);
            
            // Delete cookie
            setcookie(self::$rememberCookie, '', time() - 3600, '/');
        }
        
        // Clear session
        $_SESSION = [];
        session_destroy();
        
        // Start new session
        session_start();
        
        self::$user = null;
    }
    
    /**
     * Delete remember token
     */
    private static function deleteRememberToken($selector)
    {
        (new UserModel())->execute(
            "DELETE FROM remember_tokens WHERE selector = ?",
            [$selector]
        );
    }
    
    /**
     * Get user ID
     */
    public static function id()
    {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Check if user has role
     */
    public static function hasRole($role)
    {
        $user = self::user();
        return $user && $user['role'] === $role;
    }
    
    /**
     * Require authentication
     */
    public static function require()
    {
        if (!self::check()) {
            if (self::isAjax()) {
                Response::unauthorized('Authentication required');
            } else {
                Response::redirect('/login');
            }
        }
    }
    
    /**
     * Require role
     */
    public static function requireRole($role)
    {
        self::require();
        
        if (!self::hasRole($role)) {
            if (self::isAjax()) {
                Response::forbidden('Insufficient permissions');
            } else {
                Response::redirect('/');
            }
        }
    }
    
    /**
     * Get client IP (secure)
     */
    private static function getIp()
    {
        $keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($keys as $key) {
            if (isset($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Get user agent hash
     */
    private static function getUserAgent()
    {
        return hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
    }
    
    /**
     * Check if AJAX request
     */
    private static function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Hash password (for registration)
     */
    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }
    
    /**
     * Clean old sessions and tokens (run via cron)
     */
    public static function cleanup()
    {
        // Delete expired remember tokens
        (new UserModel())->execute(
            "DELETE FROM remember_tokens WHERE expires_at < NOW()"
        );
    }
}

