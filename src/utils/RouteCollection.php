<?php
/**
 * VelocityPhp Route Collection
 * Enhanced router with named routes, groups, middleware, wildcards
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

namespace App\Utils;

class RouteCollection
{
    private static $routes = [];
    private static $namedRoutes = [];
    private static $groups = [];
    private static $currentGroup = [];
    
    public static function get($uri, $action)
    {
        return self::addRoute(['GET', 'HEAD'], $uri, $action);
    }
    
    public static function post($uri, $action)
    {
        return self::addRoute('POST', $uri, $action);
    }
    
    public static function put($uri, $action)
    {
        return self::addRoute('PUT', $uri, $action);
    }
    
    public static function patch($uri, $action)
    {
        return self::addRoute('PATCH', $uri, $action);
    }
    
    public static function delete($uri, $action)
    {
        return self::addRoute('DELETE', $uri, $action);
    }
    
    public static function options($uri, $action)
    {
        return self::addRoute('OPTIONS', $uri, $action);
    }
    
    public static function any($uri, $action)
    {
        return self::addRoute(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $uri, $action);
    }
    
    public static function match($methods, $uri, $action)
    {
        return self::addRoute($methods, $uri, $action);
    }
    
    private static function addRoute($methods, $uri, $action)
    {
        // Apply current group prefix and middleware
        $uri = self::applyGroupPrefix($uri);
        $route = new Route($methods, $uri, $action);
        
        // Apply group middleware
        if (!empty(self::$currentGroup)) {
            $groupMiddleware = self::$currentGroup['middleware'] ?? [];
            if (!empty($groupMiddleware)) {
                $route->middleware($groupMiddleware);
            }
        }
        
        self::$routes[] = $route;
        
        // Register named route
        if ($route->getName()) {
            self::$namedRoutes[$route->getName()] = $route;
        }
        
        return $route;
    }
    
    public static function group(array $attributes, callable $callback)
    {
        $previousGroup = self::$currentGroup;
        
        self::$currentGroup = array_merge($previousGroup, $attributes);
        
        call_user_func($callback);
        
        self::$currentGroup = $previousGroup;
    }
    
    private static function applyGroupPrefix($uri)
    {
        if (!empty(self::$currentGroup['prefix'])) {
            $prefix = trim(self::$currentGroup['prefix'], '/');
            $uri = trim($uri, '/');
            return $prefix ? "/{$prefix}/{$uri}" : "/{$uri}";
        }
        return $uri;
    }
    
    public static function dispatch($uri, $method = 'GET')
    {
        $method = strtoupper($method);
        
        // Ensure URI has leading slash for proper matching
        if ($uri !== '/' && $uri[0] !== '/') {
            $uri = '/' . $uri;
        }
        
        foreach (self::$routes as $route) {
            if ($route->matches($uri, $method)) {
                // Extract parameters using the route's pattern
                if (preg_match($route->getPattern(), $uri, $matches)) {
                    $parameters = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    $route->setParameters($parameters);
                }
                
                // Handle middleware
                $middleware = $route->getMiddleware();
                if (!empty($middleware)) {
                    foreach ($middleware as $mw) {
                        $middlewareInstance = self::resolveMiddleware($mw);
                        if ($middlewareInstance && method_exists($middlewareInstance, 'handle')) {
                            $next = function($request) use ($route) {
                                return true; // Continue to route execution
                            };
                            $result = $middlewareInstance->handle($route, $next);
                            if ($result !== true && $result !== null) {
                                return $result;
                            }
                        }
                    }
                }
                
                // Execute route action
                // Return a special marker if route was found but controller returns null
                // Use 'ROUTE_FOUND_NULL' to distinguish from "no route found"
                $result = self::executeRoute($route);
                if ($result === null) {
                    // Return route info so Router can determine view path from controller@method
                    return ['status' => 'ROUTE_FOUND_NULL', 'route' => $route];
                }
                return $result;
            }
        }
        
        return null; // No route found at all
    }
    
    private static function executeRoute(Route $route)
    {
        $action = $route->getAction();
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        if (is_string($action) && strpos($action, '@') !== false) {
            list($controller, $method) = explode('@', $action);
            $controllerClass = "App\\Controllers\\{$controller}";
            
            if (class_exists($controllerClass)) {
                $controllerInstance = new $controllerClass();
                if (method_exists($controllerInstance, $method)) {
                    // Pass params as array and isAjax flag to controller methods
                    $params = $route->getParameters();
                    return call_user_func_array([$controllerInstance, $method], [$params, $isAjax]);
                }
            }
        } elseif (is_callable($action)) {
            return call_user_func_array($action, $route->getParameters());
        }
        
        return null;
    }
    
    private static function resolveMiddleware($middleware)
    {
        if (is_object($middleware)) {
            return $middleware;
        }
        
        $middlewareClass = "App\\Middleware\\{$middleware}";
        if (class_exists($middlewareClass)) {
            return new $middlewareClass();
        }
        
        return null;
    }
    
    public static function url($name, $parameters = [])
    {
        if (isset(self::$namedRoutes[$name])) {
            $route = self::$namedRoutes[$name];
            $uri = $route->getUri();
            
            foreach ($parameters as $key => $value) {
                $uri = str_replace('{' . $key . '}', $value, $uri);
                $uri = str_replace('{' . $key . '?}', $value, $uri);
            }
            
            // Remove remaining optional parameters
            $uri = preg_replace('/\{[^}]+\?}/', '', $uri);
            
            return $uri;
        }
        
        return null;
    }
    
    public static function route($name)
    {
        return self::$namedRoutes[$name] ?? null;
    }
    
    public static function getRoutes()
    {
        return self::$routes;
    }
    
    public static function clear()
    {
        self::$routes = [];
        self::$namedRoutes = [];
        self::$groups = [];
        self::$currentGroup = [];
    }
}

