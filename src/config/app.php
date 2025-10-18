<?php
/**
 * Application Configuration
 * 
 * @package NativeMVC
 */

return [
    // Application name
    'name' => 'Native MVC App',
    
    // Application environment: development, staging, production
    'env' => getenv('APP_ENV') ?: 'development',
    
    // Debug mode (set to false in production)
    'debug' => getenv('APP_DEBUG') !== 'false',
    
    // Enable detailed logging
    'log_level' => getenv('LOG_LEVEL') ?: 'debug', // debug, info, warning, error
    
    // Application URL
    'url' => getenv('APP_URL') ?: 'http://localhost',
    
    // Timezone
    'timezone' => 'UTC',
    
    // Default locale
    'locale' => 'en',
    
    // Session configuration
    'session' => [
        'lifetime' => 120, // minutes
        'secure' => false, // Set to true if using HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ],
    
    // View caching (recommended for production)
    'cache_views' => getenv('APP_ENV') === 'production',
    
    // AJAX settings
    'ajax' => [
        'cache_routes' => true,
        'preload_routes' => ['/dashboard', '/profile'],
        'transition_duration' => 300
    ]
];
