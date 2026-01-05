<?php

declare(strict_types=1);

namespace App\Api\V1\Controllers;

/**
 * API Base Controller
 * 
 * Provides standardized JSON responses following REST API best practices.
 * All API v1 controllers should extend this class.
 * 
 * @package App\Api\V1
 */
abstract class BaseController
{
    protected const HTTP_OK = 200;
    protected const HTTP_CREATED = 201;
    protected const HTTP_NO_CONTENT = 204;
    protected const HTTP_BAD_REQUEST = 400;
    protected const HTTP_UNAUTHORIZED = 401;
    protected const HTTP_FORBIDDEN = 403;
    protected const HTTP_NOT_FOUND = 404;
    protected const HTTP_METHOD_NOT_ALLOWED = 405;
    protected const HTTP_UNPROCESSABLE_ENTITY = 422;
    protected const HTTP_TOO_MANY_REQUESTS = 429;
    protected const HTTP_INTERNAL_ERROR = 500;

    /**
     * Send success response
     */
    protected function ok(mixed $data = null, string $message = 'Success'): void
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], self::HTTP_OK);
    }

    /**
     * Send created response
     */
    protected function created(mixed $data = null, string $message = 'Created'): void
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], self::HTTP_CREATED);
    }

    /**
     * Send no content response
     */
    protected function noContent(): void
    {
        http_response_code(self::HTTP_NO_CONTENT);
        exit;
    }

    /**
     * Send error response
     */
    protected function error(
        string $message,
        int $code = self::HTTP_BAD_REQUEST,
        array $errors = []
    ): void {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        $this->json($response, $code);
    }

    /**
     * Send validation error response
     */
    protected function validationError(array $errors): void
    {
        $this->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors
        ], self::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Send unauthorized response
     */
    protected function unauthorized(string $message = 'Unauthorized'): void
    {
        $this->error($message, self::HTTP_UNAUTHORIZED);
    }

    /**
     * Send forbidden response
     */
    protected function forbidden(string $message = 'Forbidden'): void
    {
        $this->error($message, self::HTTP_FORBIDDEN);
    }

    /**
     * Send not found response
     */
    protected function notFound(string $message = 'Resource not found'): void
    {
        $this->error($message, self::HTTP_NOT_FOUND);
    }

    /**
     * Send paginated response
     */
    protected function paginated(array $data, array $meta): void
    {
        $this->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $meta['current_page'] ?? 1,
                'per_page' => $meta['per_page'] ?? 15,
                'total' => $meta['total'] ?? 0,
                'last_page' => $meta['last_page'] ?? 1,
                'from' => $meta['from'] ?? 0,
                'to' => $meta['to'] ?? 0
            ]
        ], self::HTTP_OK);
    }

    /**
     * Send JSON response
     */
    protected function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Get JSON input from request body
     */
    protected function input(): array
    {
        $json = file_get_contents('php://input');
        
        if (empty($json)) {
            return $_POST;
        }

        $data = json_decode($json, true);
        
        return is_array($data) ? $data : [];
    }

    /**
     * Get specific input field
     */
    protected function get(string $key, mixed $default = null): mixed
    {
        $input = $this->input();
        return $input[$key] ?? $_GET[$key] ?? $default;
    }

    /**
     * Validate input data
     */
    protected function validate(array $rules): array
    {
        $input = $this->input();
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $input[$field] ?? null;
            $ruleList = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;

            foreach ($ruleList as $rule) {
                $error = $this->validateRule($field, $value, $rule);
                if ($error) {
                    $errors[$field][] = $error;
                }
            }
        }

        return $errors;
    }

    /**
     * Validate single rule
     */
    private function validateRule(string $field, mixed $value, string $rule): ?string
    {
        $params = [];
        
        if (str_contains($rule, ':')) {
            [$rule, $paramStr] = explode(':', $rule, 2);
            $params = explode(',', $paramStr);
        }

        $label = ucfirst(str_replace('_', ' ', $field));

        return match($rule) {
            'required' => empty($value) && $value !== '0' ? "{$label} is required" : null,
            'email' => $value && !filter_var($value, FILTER_VALIDATE_EMAIL) ? "{$label} must be valid email" : null,
            'min' => $value && strlen((string)$value) < (int)$params[0] ? "{$label} must be at least {$params[0]} characters" : null,
            'max' => $value && strlen((string)$value) > (int)$params[0] ? "{$label} must not exceed {$params[0]} characters" : null,
            'numeric' => $value && !is_numeric($value) ? "{$label} must be numeric" : null,
            'integer' => $value && !filter_var($value, FILTER_VALIDATE_INT) ? "{$label} must be integer" : null,
            'in' => $value && !in_array($value, $params) ? "{$label} must be one of: " . implode(', ', $params) : null,
            default => null
        };
    }

    /**
     * Get authenticated user from request
     */
    protected function user(): ?array
    {
        return $_REQUEST['__api_user'] ?? null;
    }

    /**
     * Get user ID from authenticated user
     */
    protected function userId(): ?int
    {
        $user = $this->user();
        return $user ? (int)$user['id'] : null;
    }
}
