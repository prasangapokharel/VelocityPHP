<?php
/**
 * VelocityPhp Development Router
 * Handles routing for PHP's built-in development server
 * 
 * This file is used by: php -S 0.0.0.0:8001 -t public router.php
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

// Get the requested URI
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Define the document root
$documentRoot = __DIR__ . '/public';

// Full path to the requested file
$requestedFile = $documentRoot . $uri;

// Check if this is a static file request
if ($uri !== '/' && file_exists($requestedFile)) {
    // Get file extension
    $extension = strtolower(pathinfo($requestedFile, PATHINFO_EXTENSION));
    
    // List of static file extensions
    $staticExtensions = [
        'css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'ico', 'webp',
        'woff', 'woff2', 'ttf', 'eot', 'otf',
        'pdf', 'doc', 'docx', 'xls', 'xlsx',
        'mp3', 'mp4', 'webm', 'ogg', 'wav',
        'zip', 'rar', 'gz', 'tar',
        'txt', 'xml', 'json', 'map'
    ];
    
    // If it's a static file, let PHP's built-in server handle it
    if (in_array($extension, $staticExtensions)) {
        return false;
    }
    
    // If it's a PHP file (but not index.php), include it
    if ($extension === 'php' && basename($requestedFile) !== 'index.php') {
        include $requestedFile;
        return true;
    }
}

// For all other requests, route through index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

// Include the main entry point
require $documentRoot . '/index.php';
