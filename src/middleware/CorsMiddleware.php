<?php
/**
 * VelocityPhp CORS Middleware
 * Handle Cross-Origin Resource Sharing
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

namespace App\Middleware;

class CorsMiddleware
{
    private $allowedOrigins;
    private $allowedMethods;
    private $allowedHeaders;
    private $maxAge;
    
    public function __construct(array $config = [])
    {
        $this->allowedOrigins = $config['origins'] ?? ['*'];
        $this->allowedMethods = $config['methods'] ?? ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];
        $this->allowedHeaders = $config['headers'] ?? ['Content-Type', 'Authorization', 'X-CSRF-Token', 'X-Requested-With'];
        $this->maxAge = $config['max_age'] ?? 3600;
    }
    
    public function handle($request, $next)
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        
        // Handle preflight request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            $this->setCorsHeaders($origin);
            http_response_code(200);
            exit;
        }
        
        $this->setCorsHeaders($origin);
        
        return $next($request);
    }
    
    private function setCorsHeaders($origin)
    {
        // Check if origin is allowed
        if ($this->allowedOrigins[0] === '*' || in_array($origin, $this->allowedOrigins)) {
            header("Access-Control-Allow-Origin: {$origin}");
        }
        
        header("Access-Control-Allow-Methods: " . implode(', ', $this->allowedMethods));
        header("Access-Control-Allow-Headers: " . implode(', ', $this->allowedHeaders));
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Max-Age: {$this->maxAge}");
    }
}

