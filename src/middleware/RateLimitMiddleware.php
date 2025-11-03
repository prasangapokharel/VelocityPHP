<?php
/**
 * VelocityPhp Rate Limiting Middleware
 * Prevent abuse with rate limiting
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

namespace App\Middleware;

class RateLimitMiddleware
{
    private $maxRequests;
    private $windowSeconds;
    private $storagePath;
    
    public function __construct($maxRequests = 60, $windowSeconds = 60, $storagePath = null)
    {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
        // Try default location first
        $defaultPath = $storagePath ?? BASE_PATH . '/storage/rate_limits';
        
        // Create directory if it doesn't exist (shared hosting compatible)
        if (!is_dir($defaultPath)) {
            @mkdir($defaultPath, 0777, true);
        }
        
        // Check if default location is writable
        if (is_dir($defaultPath) && is_writable($defaultPath)) {
            $this->storagePath = $defaultPath;
        } else {
            // Fallback to system temp directory (shared hosting compatible)
            $fallbackPath = sys_get_temp_dir() . '/velocity_rate_limits/';
            if (!is_dir($fallbackPath)) {
                @mkdir($fallbackPath, 0777, true);
            }
            if (is_dir($fallbackPath) && is_writable($fallbackPath)) {
                $this->storagePath = $fallbackPath;
            } else {
                // Last resort: use system temp directly
                $this->storagePath = sys_get_temp_dir() . '/';
            }
        }
    }
    
    public function handle($request, $next)
    {
        $key = $this->getKey($request);
        $file = $this->storagePath . '/' . md5($key) . '.json';
        
        $data = $this->loadData($file);
        $now = time();
        
        // Clean old entries
        $data['requests'] = array_filter($data['requests'], function($timestamp) use ($now) {
            return ($now - $timestamp) < $this->windowSeconds;
        });
        
        // Check limit
        if (count($data['requests']) >= $this->maxRequests) {
            $remaining = $this->windowSeconds - ($now - min($data['requests']));
            
            http_response_code(429);
            header('Retry-After: ' . $remaining);
            header('X-RateLimit-Limit: ' . $this->maxRequests);
            header('X-RateLimit-Remaining: 0');
            header('X-RateLimit-Reset: ' . ($now + $remaining));
            
            echo json_encode([
                'error' => 'Too Many Requests',
                'message' => "Rate limit exceeded. Try again in {$remaining} seconds.",
                'retry_after' => $remaining
            ]);
            exit;
        }
        
        // Record request
        $data['requests'][] = $now;
        $this->saveData($file, $data);
        
        // Set headers
        $remaining = $this->maxRequests - count($data['requests']);
        header('X-RateLimit-Limit: ' . $this->maxRequests);
        header('X-RateLimit-Remaining: ' . $remaining);
        header('X-RateLimit-Reset: ' . ($now + $this->windowSeconds));
        
        return $next($request);
    }
    
    private function getKey($request)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return $ip . '|' . $uri;
    }
    
    private function loadData($file)
    {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $data = json_decode($content, true);
            return $data ?: ['requests' => []];
        }
        return ['requests' => []];
    }
    
    private function saveData($file, $data)
    {
        file_put_contents($file, json_encode($data));
    }
}

