<?php
/**
 * VelocityPhp Middleware Stack
 * Manages middleware execution pipeline
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

namespace App\Middleware;

class MiddlewareStack
{
    private $middleware = [];
    private $globalMiddleware = [];
    
    public function __construct(array $globalMiddleware = [])
    {
        $this->globalMiddleware = $globalMiddleware;
    }
    
    public function push($middleware)
    {
        $this->middleware[] = $this->resolve($middleware);
        return $this;
    }
    
    public function unshift($middleware)
    {
        array_unshift($this->middleware, $this->resolve($middleware));
        return $this;
    }
    
    public function handle($request, $finalHandler)
    {
        $stack = array_merge($this->globalMiddleware, $this->middleware);
        $next = $finalHandler;
        
        foreach (array_reverse($stack) as $middleware) {
            $next = function($req) use ($middleware, $next) {
                return $middleware->handle($req, $next);
            };
        }
        
        return $next($request);
    }
    
    private function resolve($middleware)
    {
        if (is_object($middleware)) {
            return $middleware;
        }
        
        if (is_string($middleware)) {
            $class = "App\\Middleware\\{$middleware}";
            if (class_exists($class)) {
                return new $class();
            }
        }
        
        throw new \Exception("Middleware could not be resolved: " . (is_string($middleware) ? $middleware : gettype($middleware)));
    }
    
    public function clear()
    {
        $this->middleware = [];
    }
}

