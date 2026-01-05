<?php

declare(strict_types=1);

namespace App\Api\V1;

/**
 * API V1 Router
 * 
 * Simple, clean router for API v1 endpoints.
 * 
 * @package App\Api\V1
 */
final class Router
{
    private array $routes = [];
    private array $middleware = [];
    private string $prefix = '/api/v1';

    /**
     * Register GET route
     */
    public function get(string $uri, string $handler): self
    {
        return $this->addRoute('GET', $uri, $handler);
    }

    /**
     * Register POST route
     */
    public function post(string $uri, string $handler): self
    {
        return $this->addRoute('POST', $uri, $handler);
    }

    /**
     * Register PUT route
     */
    public function put(string $uri, string $handler): self
    {
        return $this->addRoute('PUT', $uri, $handler);
    }

    /**
     * Register PATCH route
     */
    public function patch(string $uri, string $handler): self
    {
        return $this->addRoute('PATCH', $uri, $handler);
    }

    /**
     * Register DELETE route
     */
    public function delete(string $uri, string $handler): self
    {
        return $this->addRoute('DELETE', $uri, $handler);
    }

    /**
     * Add middleware to last route
     */
    public function middleware(string|array $middleware): self
    {
        if (empty($this->routes)) {
            return $this;
        }

        $lastKey = array_key_last($this->routes);
        $middleware = is_array($middleware) ? $middleware : [$middleware];
        
        $this->routes[$lastKey]['middleware'] = array_merge(
            $this->routes[$lastKey]['middleware'] ?? [],
            $middleware
        );

        return $this;
    }

    /**
     * Group routes with shared middleware
     */
    public function group(array $middleware, callable $callback): void
    {
        $previousMiddleware = $this->middleware;
        $this->middleware = array_merge($this->middleware, $middleware);
        
        $callback($this);
        
        $this->middleware = $previousMiddleware;
    }

    /**
     * Add route
     */
    private function addRoute(string $method, string $uri, string $handler): self
    {
        $fullUri = $this->prefix . $uri;
        
        $this->routes[] = [
            'method' => $method,
            'uri' => $fullUri,
            'pattern' => $this->uriToPattern($fullUri),
            'handler' => $handler,
            'middleware' => $this->middleware
        ];

        return $this;
    }

    /**
     * Convert URI to regex pattern
     */
    private function uriToPattern(string $uri): string
    {
        // Replace {param} with named capture group
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $uri);
        return '#^' . $pattern . '$#';
    }

    /**
     * Dispatch request
     */
    public function dispatch(string $uri, string $method): void
    {
        // Set JSON response headers
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        // Handle OPTIONS for CORS
        if ($method === 'OPTIONS') {
            $this->handleCors();
            http_response_code(204);
            exit;
        }

        // Add CORS headers
        $this->handleCors();

        // Find matching route
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extract params
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Run middleware
                $this->runMiddleware($route['middleware']);

                // Execute handler
                $this->executeHandler($route['handler'], $params);
                return;
            }
        }

        // No route found
        $this->notFound();
    }

    /**
     * Run middleware chain
     */
    private function runMiddleware(array $middleware): void
    {
        foreach ($middleware as $mw) {
            $class = "App\\Api\\V1\\Middleware\\{$mw}";
            
            if (class_exists($class)) {
                $instance = new $class();
                if (method_exists($instance, 'handle')) {
                    $instance->handle();
                }
            }
        }
    }

    /**
     * Execute route handler
     */
    private function executeHandler(string $handler, array $params): void
    {
        [$controller, $method] = explode('@', $handler);
        
        $class = "App\\Api\\V1\\Controllers\\{$controller}";

        if (!class_exists($class)) {
            $this->error("Controller not found: {$controller}", 500);
        }

        $instance = new $class();

        if (!method_exists($instance, $method)) {
            $this->error("Method not found: {$method}", 500);
        }

        // Call method with params
        $instance->{$method}($params);
    }

    /**
     * Handle CORS headers
     */
    private function handleCors(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        
        header("Access-Control-Allow-Origin: {$origin}");
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
    }

    /**
     * Send not found response
     */
    private function notFound(): void
    {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Endpoint not found'
        ]);
        exit;
    }

    /**
     * Send error response
     */
    private function error(string $message, int $code = 500): void
    {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
        exit;
    }

    /**
     * Get all registered routes (for documentation)
     */
    public function getRoutes(): array
    {
        return array_map(fn($r) => [
            'method' => $r['method'],
            'uri' => $r['uri'],
            'handler' => $r['handler'],
            'middleware' => $r['middleware']
        ], $this->routes);
    }
}
