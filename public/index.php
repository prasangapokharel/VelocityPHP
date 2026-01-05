<?php
/**
 * VelocityPhp - Ultra-Fast PHP Framework
 * Single Entry Point with Turbo Performance
 * 
 * @package VelocityPhp
 * @version 1.0.0
 * @author VelocityPhp Team
 */

// ============================================================================
// Performance Optimizations Start
// ============================================================================

// Track request start time for performance monitoring
define('APP_START', microtime(true));

// Enable OPcache acceleration (if available)
if (function_exists('opcache_get_status')) {
    $opcacheStatus = opcache_get_status();
    define('OPCACHE_ENABLED', $opcacheStatus !== false);
} else {
    define('OPCACHE_ENABLED', false);
}

// Enable output buffering for better performance
// Only use manual buffering if zlib compression is not available
// zlib compression handles buffering automatically
if (!extension_loaded('zlib') || !ini_get('zlib.output_compression')) {
    ob_start();
}

// Compression for faster responses (only if not already enabled)
if (!headers_sent() && extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
    ini_set('zlib.output_compression', '1');
    ini_set('zlib.output_compression_level', '6');
}

// ============================================================================
// Core Initialization
// ============================================================================

// Note: Session is started by SecurityService with hardened settings
// Start session early for CSRF protection (will be managed by SecurityService later)
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
        'use_only_cookies' => true,
        'sid_length' => 48,
        'sid_bits_per_character' => 6
    ]);
}

// Define base paths
define('BASE_PATH', dirname(__DIR__));
define('SRC_PATH', BASE_PATH . '/src');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('VIEW_PATH', SRC_PATH . '/views');
define('CONFIG_PATH', SRC_PATH . '/config');

// ============================================================================
// Environment Configuration
// ============================================================================

// Load environment variables from .env file
if (file_exists(BASE_PATH . '/.env')) {
    $lines = file(BASE_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) continue;
        
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

// ============================================================================
// Autoloader & Dependencies
// ============================================================================

// Use Composer autoloader if available, otherwise use custom autoloader
$composerAutoload = BASE_PATH . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
} else {
    // Fallback to custom PSR-4 autoloader (for shared hosting without Composer)
    require_once SRC_PATH . '/utils/Autoloader.php';
    \App\Utils\Autoloader::register();
}

// Initialize Config (needed for cache)
\App\Config\Config::init();

// Initialize logger
\App\Utils\Logger::init();

// Initialize cache (auto-creates database and tables)
\App\Utils\VelocityCache::getInstance();

// Load configuration
$config = require_once CONFIG_PATH . '/app.php';
$dbConfig = require_once CONFIG_PATH . '/database.php';

// Set timezone
date_default_timezone_set($config['timezone'] ?? 'UTC');

// ============================================================================
// Error Handling Configuration
// ============================================================================

if ($config['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', BASE_PATH . '/logs/error.log');
}

// Set custom error handler for deep error tracking
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// Set custom exception handler (catches both Exception and Error)
set_exception_handler(function(\Throwable $exception) use ($config) {
    \App\Utils\Logger::exception($exception);
    
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    http_response_code(500);
    if (!headers_sent()) {
        if ($isAjax) {
            header('Content-Type: application/json; charset=UTF-8');
        } else {
            header('Content-Type: text/html; charset=UTF-8');
        }
    }
    echo \App\Utils\Debug::showError($exception, $isAjax);
});

// ============================================================================
// Security Service Initialization
// ============================================================================

// Initialize the SecurityService (singleton)
$security = \App\Core\Security\SecurityService::getInstance();

// Send comprehensive security headers
if (!headers_sent()) {
    $security->sendSecurityHeaders();
    
    // Additional CSP in production
    if ($config['env'] === 'production') {
        $security->sendCspHeader();
    }
}

// Rate limiting / DDoS protection (configurable)
// Default: 100 requests per minute, 5 minute block for repeat offenders
$security->setRateLimit(
    (int)($config['rate_limit_requests'] ?? 100),
    (int)($config['rate_limit_window'] ?? 60),
    (int)($config['rate_limit_block'] ?? 300)
);

// Enforce rate limit (exits with 429 if exceeded)
$security->enforceRateLimit();

// ============================================================================
// Request Processing
// ============================================================================

// Initialize Router
$router = new \App\Utils\Router();

// Get request URI and method
// Remove query string for routing (important for refresh)
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestUri = $requestUri ?: '/'; // Ensure root route is handled
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Check if this is an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Add appropriate headers
if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
}

// ============================================================================
// CSRF Protection
// ============================================================================

// Handle HTTP method spoofing for PUT/DELETE from forms
if ($requestMethod === 'POST' && isset($_POST['_method'])) {
    $spoofedMethod = strtoupper($_POST['_method']);
    if (in_array($spoofedMethod, ['PUT', 'PATCH', 'DELETE'])) {
        $requestMethod = $spoofedMethod;
    }
}

// CSRF Protection for all state-changing requests (using SecurityService)
// Skip CSRF for API routes (they use Bearer token authentication)
$isApiRequest = strpos($requestUri, '/api/') === 0;

if (!$isApiRequest && in_array($requestMethod, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
    if (!$security->validateCsrfToken()) {
        http_response_code(403);
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'CSRF token validation failed']);
        } else {
            echo '<h1>403 Forbidden</h1><p>CSRF token validation failed. <a href="javascript:history.back()">Go back</a></p>';
        }
        exit;
    }
}

// Generate CSRF token if not exists (using SecurityService)
$security->generateCsrfToken();

// ============================================================================
// Route Dispatching
// ============================================================================

try {
    // Set performance headers
    if (!headers_sent()) {
        // Cache control headers for static assets
        if ($requestMethod === 'GET' && strpos($requestUri, '/assets/') === 0) {
            header('Cache-Control: public, max-age=31536000, immutable');
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
        } else {
            // Cache control for dynamic content
            header('Cache-Control: private, no-cache, must-revalidate');
            header('Pragma: no-cache');
        }
        
        // Performance headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
    }
    
    // ========================================================================
    // API V1 Routing (handle before web routes)
    // ========================================================================
    if (strpos($requestUri, '/api/v1/') === 0) {
        // Load API routes
        $apiRouter = require SRC_PATH . '/Api/V1/routes.php';
        
        // Dispatch API request (this will exit after response)
        $apiRouter->dispatch($requestUri, $requestMethod);
        exit;
    }
    
    // Route the request
    $response = $router->dispatch($requestUri, $requestMethod, $isAjax);
    
    // Handle response
    if ($isAjax) {
        // AJAX request - return JSON
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
        }
        if (is_array($response)) {
            echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            echo $response;
        }
    } else {
        // Regular request - return full HTML
        // Ensure Content-Type is set for HTML responses
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }
        echo $response;
    }
    
} catch (\Throwable $e) {
    // Log error with full details (catches both Exception and Error)
    \App\Utils\Logger::exception($e);
    
    // Display error page
    http_response_code(500);
    if (!headers_sent()) {
        if ($isAjax) {
            header('Content-Type: application/json; charset=UTF-8');
        } else {
            header('Content-Type: text/html; charset=UTF-8');
        }
    }
    echo \App\Utils\Debug::showError($e, $isAjax);
}

// ============================================================================
// Performance Monitoring
// ============================================================================

if ($config['debug']) {
    $executionTime = round((microtime(true) - APP_START) * 1000, 2);
    $memoryUsage = round(memory_get_peak_usage() / 1024 / 1024, 2);
    
    // Add performance headers
    header("X-Execution-Time: {$executionTime}ms");
    header("X-Memory-Usage: {$memoryUsage}MB");
    header("X-OPcache: " . (OPCACHE_ENABLED ? 'enabled' : 'disabled'));
    
    // Log performance metrics
    if ($executionTime > 1000) { // Log slow requests (>1s)
        \App\Utils\Logger::warning("Slow request detected", [
            'uri' => $requestUri,
            'execution_time' => $executionTime . 'ms',
            'memory_usage' => $memoryUsage . 'MB'
        ]);
    }
}

// Flush output buffer only if we started it manually
// Don't flush if zlib compression is handling it
if (!extension_loaded('zlib') || !ini_get('zlib.output_compression')) {
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
}
