<?php

declare(strict_types=1);

namespace App\Api\V1\Middleware;

use App\Models\UserModel;

/**
 * API Authentication Middleware
 * 
 * Validates Bearer token and attaches user to request.
 * 
 * @package App\Api\V1
 */
final class AuthMiddleware
{
    /**
     * Handle the request
     */
    public function handle(): bool
    {
        $token = $this->getToken();

        if (!$token) {
            $this->unauthorized('No token provided');
        }

        $payload = $this->verifyToken($token);

        if (!$payload) {
            $this->unauthorized('Invalid token');
        }

        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            $this->unauthorized('Token expired');
        }

        // Get user
        $model = new UserModel();
        $user = $model->find($payload['sub']);

        if (!$user) {
            $this->unauthorized('User not found');
        }

        if ($user['status'] !== 'active') {
            $this->forbidden('Account is ' . $user['status']);
        }

        // Attach user to request
        $_REQUEST['__api_user'] = $user;

        return true;
    }

    /**
     * Get token from Authorization header
     */
    private function getToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (empty($header)) {
            // Try alternative header
            $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        }

        if (empty($header)) {
            return null;
        }

        if (preg_match('/Bearer\s+(.+)$/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Verify token and return payload
     */
    private function verifyToken(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;

        // Verify signature
        $secret = getenv('APP_KEY') ?: 'velocity-secret-key-change-in-production';
        $expectedSignature = base64_encode(hash_hmac('sha256', "{$header}.{$payload}", $secret, true));

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        // Decode payload
        $decoded = json_decode(base64_decode($payload), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Send unauthorized response
     */
    private function unauthorized(string $message = 'Unauthorized'): void
    {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
        exit;
    }

    /**
     * Send forbidden response
     */
    private function forbidden(string $message = 'Forbidden'): void
    {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
        exit;
    }
}
