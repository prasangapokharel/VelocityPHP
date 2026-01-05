<?php

declare(strict_types=1);

namespace App\Api\V1\Middleware;

/**
 * API Rate Limiting Middleware
 * 
 * Limits API requests per IP/user to prevent abuse.
 * 
 * @package App\Api\V1
 */
final class RateLimitMiddleware
{
    private int $maxRequests;
    private int $windowSeconds;
    private string $storagePath;

    public function __construct(int $maxRequests = 60, int $windowSeconds = 60)
    {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
        $this->storagePath = defined('ROOT_PATH') 
            ? ROOT_PATH . '/storage/rate_limits'
            : sys_get_temp_dir() . '/velocity_rate_limits';
    }

    /**
     * Handle the request
     */
    public function handle(): bool
    {
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
            header("Retry-After: {$retryAfter}");
            $this->tooManyRequests($retryAfter);
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
     * Send rate limit exceeded response
     */
    private function tooManyRequests(int $retryAfter): void
    {
        http_response_code(429);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Too many requests',
            'retry_after' => $retryAfter
        ]);
        exit;
    }
}
