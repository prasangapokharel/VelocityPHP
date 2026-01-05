<?php

declare(strict_types=1);

namespace App\Api\V1\Middleware;

use App\Config\Config;
use App\Utils\ErrorHandler;

/**
 * API Rate Limiting Middleware
 * 
 * Limits API requests per IP/user to prevent abuse.
 * Respects RATE_LIMIT_ENABLED and RATE_LIMIT_REQUESTS from .env
 * 
 * @package App\Api\V1
 */
final class RateLimitMiddleware
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
        $this->storagePath = defined('ROOT_PATH') 
            ? ROOT_PATH . '/storage/rate_limits'
            : (defined('BASE_PATH') ? BASE_PATH . '/storage/rate_limits' : sys_get_temp_dir() . '/velocity_rate_limits');
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
     * Handle the request
     */
    public function handle(): bool
    {
        // Skip rate limiting if disabled
        if (!$this->enabled) {
            return true;
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
            $this->handleRateLimitExceeded($retryAfter);
            return false;
        }

        return true;
    }

    /**
     * Get unique key for rate limiting
     */
    private function getKey(): string
    {
        // Use user ID if authenticated, otherwise IP
        $userId = $_REQUEST['__api_user']['id'] ?? null;
        
        if ($userId) {
            return 'user_' . $userId;
        }

        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] 
            ?? $_SERVER['HTTP_X_REAL_IP'] 
            ?? $_SERVER['REMOTE_ADDR'] 
            ?? 'unknown';

        // Get first IP if multiple
        if (str_contains($ip, ',')) {
            $ip = trim(explode(',', $ip)[0]);
        }

        return 'ip_' . md5($ip);
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
     * Handle rate limit exceeded
     */
    private function handleRateLimitExceeded(int $retryAfter): void
    {
        header("Retry-After: {$retryAfter}");
        
        // Check if this is an API request
        $isApi = $this->isApiRequest();
        
        if ($isApi) {
            // API response - JSON
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Too many requests. Please slow down.',
                'code' => 429,
                'retry_after' => $retryAfter
            ]);
            exit;
        }
        
        // Web request - use ErrorHandler
        ErrorHandler::tooManyRequests($retryAfter, 'Too many requests. Please slow down.');
    }

    /**
     * Check if this is an API request
     */
    private function isApiRequest(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return strpos($uri, '/api/') !== false;
    }

    /**
     * Check if rate limiting is currently enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Get current configuration
     */
    public function getConfig(): array
    {
        return [
            'enabled' => $this->enabled,
            'max_requests' => $this->maxRequests,
            'window_seconds' => $this->windowSeconds,
        ];
    }
}
