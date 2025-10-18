<?php
/**
 * Single Entry Point - Bootstrap Application
 * Handles all routing, initializes framework, zero-refresh AJAX support
 * 
 * @package NativeMVC
 * @version 1.0.0
 */

// Start session for state persistence
session_start();

// Define base paths
define('BASE_PATH', dirname(__DIR__));
define('SRC_PATH', BASE_PATH . '/src');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('VIEW_PATH', SRC_PATH . '/views');
define('CONFIG_PATH', SRC_PATH . '/config');

// Load autoloader
require_once SRC_PATH . '/utils/Autoloader.php';
\App\Utils\Autoloader::register();

// Initialize logger
\App\Utils\Logger::init();

// Load configuration
$config = require_once CONFIG_PATH . '/app.php';
$dbConfig = require_once CONFIG_PATH . '/database.php';

// Error handling based on environment
if ($config['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', BASE_PATH . '/logs/error.log');
}

// Initialize Router
$router = new \App\Utils\Router();

// Get request URI and method
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Check if this is an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Add AJAX header to response
if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
}

// CSRF Protection
if ($requestMethod === 'POST' || $requestMethod === 'PUT' || $requestMethod === 'DELETE') {
    if ($isAjax) {
        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
            if ($isAjax) {
                echo json_encode(['error' => 'CSRF token validation failed']);
                exit;
            }
            die('CSRF token validation failed');
        }
    }
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    // Route the request
    $response = $router->dispatch($requestUri, $requestMethod, $isAjax);
    
    // Handle response
    if ($isAjax) {
        // AJAX request - return JSON
        if (is_array($response)) {
            echo json_encode($response);
        } else {
            echo $response;
        }
    } else {
        // Regular request - return full HTML with SPA shell
        echo $response;
    }
    
} catch (\Exception $e) {
    // Log error with full details
    \App\Utils\Logger::exception($e);
    
    // Display beautiful error page
    http_response_code(500);
    echo \App\Utils\Debug::showError($e, $isAjax);
}
