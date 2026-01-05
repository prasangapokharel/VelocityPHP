<?php
/**
 * VelocityPhp Security Service
 * Comprehensive security layer for the framework
 * 
 * Features:
 * - Secure session management
 * - Input sanitization
 * - CSRF protection
 * - Password hashing (Argon2ID/Bcrypt)
 * - Authentication helpers
 * - Role-based access control
 * - Rate limiting / DDoS protection
 * - Security headers
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

namespace App\Core\Security;

class SecurityService
{
    private static ?SecurityService $instance = null;
    
    private array $config = [];
    private bool $sessionStarted = false;
    private string $rateLimitPath;
    
    // Rate limit settings
    private int $maxRequests = 100;
    private int $windowSeconds = 60;
    private int $blockDuration = 300; // 5 minutes block for repeat offenders
    
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
     * Constructor
     */
    private function __construct()
    {
        $this->config = [
            'session_name' => 'velocity_session',
            'session_lifetime' => (int)(getenv('SESSION_LIFETIME') ?: 120) * 60,
            'session_secure' => filter_var(getenv('SESSION_SECURE') ?: false, FILTER_VALIDATE_BOOLEAN),
            'session_httponly' => filter_var(getenv('SESSION_HTTPONLY') ?: true, FILTER_VALIDATE_BOOLEAN),
            'csrf_token_name' => 'csrf_token',
            'password_algo' => PASSWORD_ARGON2ID,
            'password_options' => [
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 3
            ]
        ];
        
        // Fallback to bcrypt if Argon2ID not available
        if (!defined('PASSWORD_ARGON2ID')) {
            $this->config['password_algo'] = PASSWORD_BCRYPT;
            $this->config['password_options'] = ['cost' => 12];
        }
        
        // Initialize rate limit storage path
        $this->initRateLimitPath();
    }
    
    /**
     * Initialize rate limit storage path (shared hosting compatible)
     */
    private function initRateLimitPath(): void
    {
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
        $defaultPath = $basePath . '/storage/security';
        
        if (!is_dir($defaultPath)) {
            @mkdir($defaultPath, 0755, true);
        }
        
        if (is_dir($defaultPath) && is_writable($defaultPath)) {
            $this->rateLimitPath = $defaultPath;
        } else {
            // Fallback to system temp
            $fallback = sys_get_temp_dir() . '/velocity_security/';
            if (!is_dir($fallback)) {
                @mkdir($fallback, 0755, true);
            }
            $this->rateLimitPath = $fallback;
        }
    }
    
    // =========================================================================
    // SESSION MANAGEMENT
    // =========================================================================
    
    /**
     * Start secure session with hardened settings
     */
    public function startSession(array $options = []): void
    {
        if ($this->sessionStarted || session_status() === PHP_SESSION_ACTIVE) {
            $this->sessionStarted = true;
            return;
        }
        
        // Check if headers already sent (CLI or test environment)
        if (headers_sent()) {
            $this->sessionStarted = true;
            return;
        }
        
        // Prevent session fixation (only set if headers not sent)
        @ini_set('session.use_strict_mode', '1');
        @ini_set('session.use_only_cookies', '1');
        @ini_set('session.use_trans_sid', '0');
        
        $defaults = [
            'name' => $this->config['session_name'],
            'cookie_lifetime' => $this->config['session_lifetime'],
            'cookie_httponly' => $this->config['session_httponly'],
            'cookie_secure' => $this->config['session_secure'] || $this->isHttps(),
            'cookie_samesite' => 'Lax',
            'gc_maxlifetime' => $this->config['session_lifetime'],
            'sid_length' => 48,
            'sid_bits_per_character' => 6
        ];
        
        $options = array_merge($defaults, $options);
        
        @session_name($options['name']);
        unset($options['name']);
        
        @session_start($options);
        $this->sessionStarted = true;
        
        // Regenerate session ID periodically to prevent fixation
        $this->regenerateSessionIfNeeded();
        
        // Initialize flash messages
        if (!isset($_SESSION['_flash'])) {
            $_SESSION['_flash'] = ['old' => [], 'new' => []];
        }
    }
    
    /**
     * Regenerate session ID if older than 30 minutes
     */
    private function regenerateSessionIfNeeded(): void
    {
        $regenerateInterval = 1800; // 30 minutes
        
        if (!isset($_SESSION['_last_regenerate'])) {
            $_SESSION['_last_regenerate'] = time();
            return;
        }
        
        if (time() - $_SESSION['_last_regenerate'] > $regenerateInterval) {
            session_regenerate_id(true);
            $_SESSION['_last_regenerate'] = time();
        }
    }
    
    /**
     * Destroy session completely
     */
    public function destroySession(): void
    {
        $_SESSION = [];
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        session_destroy();
        $this->sessionStarted = false;
    }
    
    /**
     * Get session value
     */
    public function session(string $key, $default = null)
    {
        $this->ensureSession();
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Set session value
     */
    public function setSession(string $key, $value): void
    {
        $this->ensureSession();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Remove session value
     */
    public function removeSession(string $key): void
    {
        $this->ensureSession();
        unset($_SESSION[$key]);
    }
    
    /**
     * Ensure session is started
     */
    private function ensureSession(): void
    {
        if (!$this->sessionStarted && session_status() !== PHP_SESSION_ACTIVE) {
            $this->startSession();
        }
    }
    
    // =========================================================================
    // INPUT SANITIZATION
    // =========================================================================
    
    /**
     * Clean and sanitize input value
     */
    public function clean($value, string $type = 'string')
    {
        if (is_array($value)) {
            return array_map(fn($v) => $this->clean($v, $type), $value);
        }
        
        if ($value === null) {
            return null;
        }
        
        $value = (string) $value;
        
        switch ($type) {
            case 'string':
                return trim(strip_tags($value));
                
            case 'email':
                return filter_var(trim($value), FILTER_SANITIZE_EMAIL);
                
            case 'url':
                return filter_var(trim($value), FILTER_SANITIZE_URL);
                
            case 'int':
                return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                
            case 'float':
                return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                
            case 'bool':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                
            case 'html':
                return $this->cleanXss($value);
                
            case 'alphanumeric':
                return preg_replace('/[^a-zA-Z0-9]/', '', $value);
                
            case 'slug':
                return preg_replace('/[^a-z0-9\-]/', '', strtolower($value));
                
            default:
                return $value;
        }
    }
    
    /**
     * Get sanitized POST value
     */
    public function post(string $key, $default = null, string $type = 'string')
    {
        if (!isset($_POST[$key])) {
            return $default;
        }
        return $this->clean($_POST[$key], $type);
    }
    
    /**
     * Get sanitized GET value
     */
    public function get(string $key, $default = null, string $type = 'string')
    {
        if (!isset($_GET[$key])) {
            return $default;
        }
        return $this->clean($_GET[$key], $type);
    }
    
    /**
     * Get sanitized REQUEST value
     */
    public function input(string $key, $default = null, string $type = 'string')
    {
        if (!isset($_REQUEST[$key])) {
            return $default;
        }
        return $this->clean($_REQUEST[$key], $type);
    }
    
    /**
     * Get all sanitized POST data
     */
    public function allPost(string $type = 'string'): array
    {
        return $this->clean($_POST, $type);
    }
    
    /**
     * Get all sanitized GET data
     */
    public function allGet(string $type = 'string'): array
    {
        return $this->clean($_GET, $type);
    }
    
    /**
     * Clean XSS from string
     */
    public function cleanXss(string $value): string
    {
        // Remove null bytes
        $value = str_replace(chr(0), '', $value);
        
        // Remove script tags and content
        $value = preg_replace('#<script[^>]*>.*?</script>#is', '', $value);
        
        // Remove javascript: protocols
        $value = preg_replace('#javascript\s*:#i', '', $value);
        
        // Remove vbscript: protocols
        $value = preg_replace('#vbscript\s*:#i', '', $value);
        
        // Remove on* event handlers
        $value = preg_replace('#on\w+\s*=\s*["\'][^"\']*["\']#i', '', $value);
        $value = preg_replace('#on\w+\s*=\s*[^\s>]+#i', '', $value);
        
        // Remove data: URIs in src/href (potential XSS vector)
        $value = preg_replace('#(src|href)\s*=\s*["\']?\s*data:#i', '$1="', $value);
        
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Escape output for HTML
     * @param mixed $value
     * @return string|array
     */
    public function escape($value)
    {
        if (is_array($value)) {
            return array_map([$this, 'escape'], $value);
        }
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    // =========================================================================
    // CSRF PROTECTION
    // =========================================================================
    
    /**
     * Generate CSRF token
     */
    public function generateCsrfToken(): string
    {
        $this->ensureSession();
        
        if (!isset($_SESSION[$this->config['csrf_token_name']])) {
            $_SESSION[$this->config['csrf_token_name']] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION[$this->config['csrf_token_name']];
    }
    
    /**
     * Validate CSRF token
     */
    public function validateCsrfToken(?string $token = null): bool
    {
        $this->ensureSession();
        
        if ($token === null) {
            // Check multiple sources
            $token = $_POST[$this->config['csrf_token_name']] 
                ?? $_SERVER['HTTP_X_CSRF_TOKEN'] 
                ?? $_SERVER['HTTP_X_XSRF_TOKEN']
                ?? null;
        }
        
        if ($token === null || !isset($_SESSION[$this->config['csrf_token_name']])) {
            return false;
        }
        
        return hash_equals($_SESSION[$this->config['csrf_token_name']], $token);
    }
    
    /**
     * Get CSRF hidden input field
     */
    public function csrfField(): string
    {
        $token = $this->generateCsrfToken();
        $name = $this->config['csrf_token_name'];
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            $this->escape($name),
            $this->escape($token)
        );
    }
    
    /**
     * Get CSRF meta tag
     */
    public function csrfMeta(): string
    {
        $token = $this->generateCsrfToken();
        return sprintf('<meta name="csrf-token" content="%s">', $this->escape($token));
    }
    
    /**
     * Regenerate CSRF token (after successful form submission)
     */
    public function regenerateCsrfToken(): string
    {
        $this->ensureSession();
        $_SESSION[$this->config['csrf_token_name']] = bin2hex(random_bytes(32));
        return $_SESSION[$this->config['csrf_token_name']];
    }
    
    // =========================================================================
    // PASSWORD HASHING
    // =========================================================================
    
    /**
     * Hash password using Argon2ID (or Bcrypt fallback)
     */
    public function hashPassword(string $password): string
    {
        return password_hash(
            $password,
            $this->config['password_algo'],
            $this->config['password_options']
        );
    }
    
    /**
     * Verify password against hash
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
    
    /**
     * Check if password hash needs rehashing (algorithm upgrade)
     */
    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash(
            $hash,
            $this->config['password_algo'],
            $this->config['password_options']
        );
    }
    
    // =========================================================================
    // AUTHENTICATION
    // =========================================================================
    
    /**
     * Log in a user
     */
    public function login(array $user, bool $remember = false): void
    {
        $this->ensureSession();
        
        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = $user;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if ($remember) {
            $this->setRememberToken($user['id']);
        }
    }
    
    /**
     * Log out current user
     */
    public function logout(): void
    {
        $this->ensureSession();
        
        // Clear remember token if exists
        if (isset($_SESSION['user_id'])) {
            $this->clearRememberToken($_SESSION['user_id']);
        }
        
        // Clear session data
        unset(
            $_SESSION['user_id'],
            $_SESSION['user'],
            $_SESSION['logged_in'],
            $_SESSION['login_time'],
            $_SESSION['ip'],
            $_SESSION['user_agent']
        );
        
        // Regenerate session ID
        session_regenerate_id(true);
    }
    
    /**
     * Check if user is logged in
     */
    public function isLogged(): bool
    {
        $this->ensureSession();
        
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            return false;
        }
        
        // Validate session hasn't been hijacked (optional strict check)
        if (isset($_SESSION['ip']) && $_SESSION['ip'] !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
            // IP changed - could be session hijacking, but also could be legitimate (mobile networks)
            // Log this event but don't invalidate by default
        }
        
        return true;
    }
    
    /**
     * Get current logged in user
     */
    public function user(): ?array
    {
        if (!$this->isLogged()) {
            return null;
        }
        return $_SESSION['user'] ?? null;
    }
    
    /**
     * Get current user ID
     */
    public function userId(): ?int
    {
        if (!$this->isLogged()) {
            return null;
        }
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Set remember token cookie
     */
    private function setRememberToken(int $userId): void
    {
        $token = bin2hex(random_bytes(32));
        $expires = time() + (30 * 24 * 60 * 60); // 30 days
        
        // Store token hash in session (in real app, store in database)
        $_SESSION['remember_token_hash'] = password_hash($token, PASSWORD_DEFAULT);
        
        setcookie(
            'remember_token',
            $userId . '|' . $token,
            [
                'expires' => $expires,
                'path' => '/',
                'secure' => $this->isHttps(),
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }
    
    /**
     * Clear remember token
     */
    private function clearRememberToken(int $userId): void
    {
        unset($_SESSION['remember_token_hash']);
        
        setcookie('remember_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $this->isHttps(),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    
    // =========================================================================
    // ROLE-BASED ACCESS CONTROL
    // =========================================================================
    
    /**
     * Check if user has specific role
     */
    public function hasRole(string $role): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }
        
        $userRole = $user['role'] ?? 'user';
        return $userRole === $role;
    }
    
    /**
     * Check if user has any of the specified roles
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }
    
    /**
     * Guard - require authentication
     */
    public function requireAuth(string $redirectTo = '/login'): void
    {
        if (!$this->isLogged()) {
            $this->setSession('intended_url', $_SERVER['REQUEST_URI'] ?? '/');
            header('Location: ' . $redirectTo);
            exit;
        }
    }
    
    /**
     * Guard - require specific role
     */
    public function requireRole(string $role, string $redirectTo = '/403'): void
    {
        $this->requireAuth();
        
        if (!$this->hasRole($role)) {
            header('Location: ' . $redirectTo);
            exit;
        }
    }
    
    /**
     * Guard - require guest (not logged in)
     */
    public function requireGuest(string $redirectTo = '/'): void
    {
        if ($this->isLogged()) {
            header('Location: ' . $redirectTo);
            exit;
        }
    }
    
    // =========================================================================
    // RATE LIMITING / DDOS PROTECTION
    // =========================================================================
    
    /**
     * Configure rate limiting
     */
    public function setRateLimit(int $maxRequests, int $windowSeconds, int $blockDuration = 300): self
    {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
        $this->blockDuration = $blockDuration;
        return $this;
    }
    
    /**
     * Check rate limit (returns true if allowed, false if blocked)
     */
    public function checkRateLimit(?string $key = null): bool
    {
        $key = $key ?? $this->getRateLimitKey();
        $file = $this->rateLimitPath . '/' . md5($key) . '.json';
        
        $data = $this->loadRateLimitData($file);
        $now = time();
        
        // Check if IP is blocked
        if (isset($data['blocked_until']) && $data['blocked_until'] > $now) {
            return false;
        }
        
        // Clean old entries
        $data['requests'] = array_filter(
            $data['requests'] ?? [],
            fn($timestamp) => ($now - $timestamp) < $this->windowSeconds
        );
        
        // Check if limit exceeded
        if (count($data['requests']) >= $this->maxRequests) {
            // Block the IP for repeat offenses
            $data['violations'] = ($data['violations'] ?? 0) + 1;
            
            if ($data['violations'] >= 3) {
                $data['blocked_until'] = $now + $this->blockDuration;
            }
            
            $this->saveRateLimitData($file, $data);
            return false;
        }
        
        // Record request
        $data['requests'][] = $now;
        $this->saveRateLimitData($file, $data);
        
        return true;
    }
    
    /**
     * Enforce rate limit (exits with 429 if exceeded)
     */
    public function enforceRateLimit(?string $key = null): void
    {
        if (!$this->checkRateLimit($key)) {
            $this->sendRateLimitResponse();
        }
    }
    
    /**
     * Send rate limit exceeded response
     */
    private function sendRateLimitResponse(): void
    {
        http_response_code(429);
        header('Retry-After: ' . $this->windowSeconds);
        header('X-RateLimit-Limit: ' . $this->maxRequests);
        header('X-RateLimit-Remaining: 0');
        
        if ($this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => $this->windowSeconds
            ]);
        } else {
            echo '<h1>429 - Too Many Requests</h1>';
            echo '<p>You have made too many requests. Please wait and try again.</p>';
        }
        exit;
    }
    
    /**
     * Get rate limit key based on IP
     */
    private function getRateLimitKey(): string
    {
        $ip = $this->getClientIp();
        return 'rate_' . $ip;
    }
    
    /**
     * Get client IP (handles proxies)
     */
    public function getClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Proxy
            'HTTP_X_REAL_IP',            // Nginx
            'HTTP_CLIENT_IP',            // Client
            'REMOTE_ADDR'                // Direct
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
     * Load rate limit data
     */
    private function loadRateLimitData(string $file): array
    {
        if (file_exists($file)) {
            $content = @file_get_contents($file);
            $data = json_decode($content, true);
            return is_array($data) ? $data : ['requests' => []];
        }
        return ['requests' => []];
    }
    
    /**
     * Save rate limit data
     */
    private function saveRateLimitData(string $file, array $data): void
    {
        @file_put_contents($file, json_encode($data), LOCK_EX);
    }
    
    /**
     * Clear rate limit for an IP
     */
    public function clearRateLimit(?string $key = null): void
    {
        $key = $key ?? $this->getRateLimitKey();
        $file = $this->rateLimitPath . '/' . md5($key) . '.json';
        
        if (file_exists($file)) {
            @unlink($file);
        }
    }
    
    // =========================================================================
    // SECURITY HEADERS
    // =========================================================================
    
    /**
     * Send all security headers
     */
    public function sendSecurityHeaders(): void
    {
        // Prevent clickjacking
        header('X-Frame-Options: SAMEORIGIN');
        
        // XSS Protection (legacy browsers)
        header('X-XSS-Protection: 1; mode=block');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Permissions Policy (formerly Feature-Policy)
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        
        // Remove PHP version header
        header_remove('X-Powered-By');
        
        // HTTPS enforcement (only in production with HTTPS)
        if ($this->isHttps() && $this->isProduction()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
    
    /**
     * Send Content Security Policy header
     */
    public function sendCspHeader(array $policy = []): void
    {
        $defaults = [
            "default-src" => "'self'",
            "script-src" => "'self' 'unsafe-inline'",
            "style-src" => "'self' 'unsafe-inline'",
            "img-src" => "'self' data: https:",
            "font-src" => "'self' https:",
            "connect-src" => "'self'",
            "frame-ancestors" => "'self'",
            "form-action" => "'self'",
            "base-uri" => "'self'"
        ];
        
        $policy = array_merge($defaults, $policy);
        
        $csp = [];
        foreach ($policy as $directive => $value) {
            $csp[] = "$directive $value";
        }
        
        header('Content-Security-Policy: ' . implode('; ', $csp));
    }
    
    // =========================================================================
    // HELPER METHODS
    // =========================================================================
    
    /**
     * Check if request is HTTPS
     */
    public function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['SERVER_PORT'] ?? 80) == 443
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }
    
    /**
     * Check if request is AJAX
     */
    public function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Check if running in production
     */
    public function isProduction(): bool
    {
        return getenv('APP_ENV') === 'production';
    }
    
    /**
     * Generate secure random token
     */
    public function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Generate random string
     */
    public function randomString(int $length = 16, string $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'): string
    {
        $result = '';
        $max = strlen($chars) - 1;
        
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[random_int(0, $max)];
        }
        
        return $result;
    }
    
    /**
     * Validate that request method matches expected
     */
    public function validateMethod(string $expected): bool
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Check for method spoofing
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }
        
        return strtoupper($expected) === $method;
    }
    
    /**
     * Get all security status info (for debugging)
     */
    public function getSecurityStatus(): array
    {
        return [
            'https' => $this->isHttps(),
            'production' => $this->isProduction(),
            'session_active' => session_status() === PHP_SESSION_ACTIVE,
            'csrf_token_set' => isset($_SESSION[$this->config['csrf_token_name']]),
            'user_logged_in' => $this->isLogged(),
            'password_algo' => $this->config['password_algo'] === PASSWORD_ARGON2ID ? 'Argon2ID' : 'Bcrypt',
            'rate_limit_path' => $this->rateLimitPath,
            'client_ip' => $this->getClientIp()
        ];
    }
}
