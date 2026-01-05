<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Config\Config;
use App\Utils\ErrorHandler;

/**
 * Web Rate Limiting Middleware
 * 
 * Limits web requests per IP to prevent abuse.
 * Respects RATE_LIMIT_ENABLED and RATE_LIMIT_REQUESTS from .env
 * 
 * @package App\Middleware
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    private int $maxRequests;
    private int $windowSeconds;
    private string $storagePath;
    private bool $enabled;

    public function __construct(?int $maxRequests = null, int $windowSeconds = 60)
    {
        // Check if rate limiting is enabled in .env
        $this->enabled = $this->isRateLimitEnabled();
        
        // Get max requests from .env or use default
        $this->maxRequests = $maxRequests ?? (int) Config::env('RATE_LIMIT_REQUESTS', 60);
        $this->windowSeconds = $windowSeconds;
        $this->storagePath = defined('BASE_PATH') 
            ? BASE_PATH . '/storage/rate_limits'
            : sys_get_temp_dir() . '/velocity_rate_limits';
    }

    /**
     * Check if rate limiting is enabled
     */
    private function isRateLimitEnabled(): bool
    {
        $enabled = Config::env('RATE_LIMIT_ENABLED', true);
        
        // Handle string values from .env
        if (is_string($enabled)) {
            $enabled = strtolower($enabled);
            return $enabled === 'true' || $enabled === '1' || $enabled === 'yes';
        }
        
        return (bool) $enabled;
    }

    /**
     * Handle the request - compatible with MiddlewareInterface
     */
    public function handle($request, $next)
    {
        // Skip rate limiting if disabled
        if (!$this->enabled) {
            return $next($request);
        }

        $key = $this->getKey();
        $data = $this->getData($key);

        $now = time();
        
        // Reset if window expired
        if ($data['window_start'] + $this->windowSeconds < $now) {
            $data = [
                'count' => 0,
                'window_start' => $now
            ];
        }

        $data['count']++;

        // Calculate remaining
        $remaining = max(0, $this->maxRequests - $data['count']);
        $resetAt = $data['window_start'] + $this->windowSeconds;

        // Set rate limit headers
        header("X-RateLimit-Limit: {$this->maxRequests}");
        header("X-RateLimit-Remaining: {$remaining}");
        header("X-RateLimit-Reset: {$resetAt}");

        // Save data
        $this->saveData($key, $data);

        // Check if exceeded
        if ($data['count'] > $this->maxRequests) {
            $retryAfter = $resetAt - $now;
            return $this->handleRateLimitExceeded($retryAfter);
        }

        return $next($request);
    }

    /**
     * Get unique key for rate limiting
     */
    private function getKey(): string
    {
        // Use session ID if available, otherwise IP
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['user_id'])) {
            return 'user_' . $_SESSION['user_id'];
        }

        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] 
            ?? $_SERVER['HTTP_X_REAL_IP'] 
            ?? $_SERVER['REMOTE_ADDR'] 
            ?? 'unknown';

        // Get first IP if multiple
        if (str_contains($ip, ',')) {
            $ip = trim(explode(',', $ip)[0]);
        }

        return 'web_ip_' . md5($ip);
    }

    /**
     * Get rate limit data
     */
    private function getData(string $key): array
    {
        $this->ensureStorageExists();
        
        $file = $this->storagePath . '/' . $key . '.json';

        if (!file_exists($file)) {
            return [
                'count' => 0,
                'window_start' => time()
            ];
        }

        $data = json_decode(file_get_contents($file), true);
        
        return is_array($data) ? $data : [
            'count' => 0,
            'window_start' => time()
        ];
    }

    /**
     * Save rate limit data
     */
    private function saveData(string $key, array $data): void
    {
        $this->ensureStorageExists();
        
        $file = $this->storagePath . '/' . $key . '.json';
        file_put_contents($file, json_encode($data), LOCK_EX);
    }

    /**
     * Ensure storage directory exists
     */
    private function ensureStorageExists(): void
    {
        if (!is_dir($this->storagePath)) {
            @mkdir($this->storagePath, 0755, true);
        }
    }

    /**
     * Handle rate limit exceeded - redirect to 429 error page
     */
    private function handleRateLimitExceeded(int $retryAfter): void
    {
        ErrorHandler::tooManyRequests($retryAfter, 'Too many requests. Please slow down.');
    }

    /**
     * Check if rate limiting is currently enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
