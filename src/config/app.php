<?php
/**
 * VelocityPhp Application Configuration
 * High-performance configuration for production-ready applications
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

return [
    // Application name
    'name' => 'VelocityPhp',
    
    // Application environment: development, staging, production
    'env' => getenv('APP_ENV') ?: 'development',
    
    // Debug mode (set to false in production)
    'debug' => getenv('APP_DEBUG') !== 'false',
    
    // Enable detailed logging with stack traces
    'log_level' => getenv('LOG_LEVEL') ?: 'info', // debug, info, warning, error
    
    // Deep error reporting - disable for clean errors
    'deep_errors' => false,
    'error_detail_level' => 5, // Max stack trace depth
    
    // Application URL
    'url' => getenv('APP_URL') ?: 'http://localhost',
    
    // Timezone
    'timezone' => getenv('TIMEZONE') ?: 'UTC',
    
    // Default locale
    'locale' => 'en',
    
    // Session configuration
    'session' => [
        'lifetime' => 120, // minutes
        'secure' => getenv('APP_ENV') === 'production',
        'httponly' => true,
        'samesite' => 'Lax'
    ],
    
    // Performance optimizations
    'performance' => [
        // Enable OPcache (recommended for production)
        'opcache' => getenv('APP_ENV') === 'production',
        
        // Enable view caching
        'cache_views' => getenv('APP_ENV') === 'production',
        
        // Enable query caching
        'cache_queries' => true,
        'query_cache_ttl' => 3600, // seconds
        
        // Enable route caching
        'cache_routes' => true,
        
        // Enable response compression
        'compression' => true,
        
        // Database connection pooling
        'db_pool_size' => 10,
        'db_pool_timeout' => 30,
    ],
    
    // AJAX settings
    'ajax' => [
        'cache_routes' => true,
        'preload_routes' => ['/dashboard', '/profile'],
        'transition_duration' => 200, // milliseconds - optimized for speed
        'enable_history' => true,
        'enable_prefetch' => true
    ],
    
    // Security settings
    'security' => [
        'csrf_protection' => true,
        'xss_protection' => true,
        'sql_injection_protection' => true,
        'rate_limiting' => false, // Enable for production
        'rate_limit' => 100 // requests per minute
    ]
];
