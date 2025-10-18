<?php
/**
 * Router Class
 * Handles automatic route discovery and dispatching based on file structure
 * Supports Next.js-style routing with dynamic segments
 * 
 * @package NativeMVC
 */

namespace App\Utils;

class Router
{
    private $routes = [];
    private $patterns = [];
    
    /**
     * Dispatch the request to appropriate controller/view
     * 
     * @param string $uri Request URI
     * @param string $method HTTP method
     * @param bool $isAjax Whether request is AJAX
     * @return mixed Response
     */
    public function dispatch($uri, $method = 'GET', $isAjax = false)
    {
        // Clean URI
        $uri = $this->cleanUri($uri);
        
        // Try to find a matching route
        $route = $this->findRoute($uri);
        
        if (!$route) {
            http_response_code(404);
            if ($isAjax) {
                return [
                    'error' => 'Route not found',
                    'html' => $this->renderErrorView('404'),
                    'title' => '404 - Not Found'
                ];
            }
            return $this->renderFullPage($this->renderErrorView('404'), '404 - Not Found');
        }
        
        // Extract parameters from dynamic routes
        $params = $this->extractParams($uri, $route);
        
        // Check if controller exists for this route
        $controller = $this->getControllerForRoute($route, $method);
        
        if ($controller) {
            $response = $this->dispatchToController($controller, $params, $isAjax);
            
            // If controller returns null for non-AJAX, render view directly
            if ($response === null && !$isAjax) {
                return $this->renderView($route, $params, $isAjax);
            }
            
            return $response;
        }
        
        // Otherwise, render the view directly
        return $this->renderView($route, $params, $isAjax);
    }
    
    /**
     * Clean and normalize URI
     */
    private function cleanUri($uri)
    {
        $uri = trim($uri, '/');
        $uri = filter_var($uri, FILTER_SANITIZE_URL);
        
        // Remove query string
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        return $uri ?: '/';
    }
    
    /**
     * Find matching route for URI
     */
    private function findRoute($uri)
    {
        $uri = $uri === '/' ? '' : $uri;
        
        // Check for API routes (e.g., api/users/123)
        if (strpos($uri, 'api/') === 0) {
            // Return the full API route for controller handling
            return $uri;
        }
        
        // Convert URI to potential file path
        $viewPath = VIEW_PATH . '/pages/' . ($uri ?: 'index') . '/index.php';
        
        // Check if direct match exists
        if (file_exists($viewPath)) {
            return $uri ?: 'index';
        }
        
        // Check for dynamic routes
        $segments = $uri ? explode('/', $uri) : [];
        $dynamicRoute = $this->findDynamicRoute($segments);
        
        if ($dynamicRoute) {
            return $dynamicRoute;
        }
        
        return null;
    }
    
    /**
     * Find dynamic route with parameters
     */
    private function findDynamicRoute($segments)
    {
        $pagesPath = VIEW_PATH . '/pages';
        
        // Try to match segments with potential dynamic routes
        for ($i = count($segments); $i >= 0; $i--) {
            $staticParts = array_slice($segments, 0, $i);
            $dynamicParts = array_slice($segments, $i);
            
            // Build potential path
            $path = $pagesPath . ($staticParts ? '/' . implode('/', $staticParts) : '');
            
            // Look for dynamic segment directories
            if (is_dir($path)) {
                $dirs = scandir($path);
                
                foreach ($dirs as $dir) {
                    if ($dir === '.' || $dir === '..') continue;
                    
                    // Check if it's a dynamic segment (e.g., [id])
                    if (preg_match('/^\[([a-zA-Z_][a-zA-Z0-9_]*)\]$/', $dir, $matches)) {
                        $paramName = $matches[1];
                        $checkPath = $path . '/' . $dir;
                        
                        // Recursively check if route exists
                        if (count($dynamicParts) > 0) {
                            $remainingPath = $checkPath;
                            for ($j = 1; $j < count($dynamicParts); $j++) {
                                $remainingPath .= '/' . $dynamicParts[$j];
                            }
                            $remainingPath .= '/index.php';
                            
                            if (file_exists($remainingPath)) {
                                $route = ($staticParts ? implode('/', $staticParts) . '/' : '') . 
                                        '[' . $paramName . ']';
                                for ($j = 1; $j < count($dynamicParts); $j++) {
                                    $route .= '/' . $dynamicParts[$j];
                                }
                                return $route;
                            }
                        } else {
                            // Check for index.php in dynamic directory
                            if (file_exists($checkPath . '/index.php')) {
                                $route = ($staticParts ? implode('/', $staticParts) . '/' : '') . 
                                        '[' . $paramName . ']';
                                return $route;
                            }
                        }
                    }
                }
            }
        }
        
        return null;
    }
    
    /**
     * Extract parameters from dynamic route
     */
    private function extractParams($uri, $route)
    {
        $params = [];
        
        // Handle API routes (e.g., api/users/123)
        if (strpos($route, 'api/') === 0) {
            $uriSegments = explode('/', $uri);
            
            // api/users/123 -> extract ID
            if (count($uriSegments) >= 3 && is_numeric($uriSegments[2])) {
                $params['id'] = $uriSegments[2];
            }
            
            return $params;
        }
        
        $uriSegments = $uri === '/' ? [] : explode('/', $uri);
        $routeSegments = $route === 'index' ? [] : explode('/', $route);
        
        foreach ($routeSegments as $index => $segment) {
            if (preg_match('/^\[([a-zA-Z_][a-zA-Z0-9_]*)\]$/', $segment, $matches)) {
                $paramName = $matches[1];
                $params[$paramName] = $uriSegments[$index] ?? null;
            }
        }
        
        return $params;
    }
    
    /**
     * Get controller for route
     */
    private function getControllerForRoute($route, $method)
    {
        // Check for API routes first (e.g., api/auth/login)
        if (strpos($route, 'api/') === 0) {
            $segments = explode('/', $route);
            
            // api/auth/login -> AuthController
            if (isset($segments[1])) {
                $controllerName = ucfirst($segments[1]);
                $controllerClass = "App\\Controllers\\{$controllerName}Controller";
                
                if (class_exists($controllerClass)) {
                    return new $controllerClass();
                }
            }
        }
        
        // Convert route to controller name
        // e.g., "users" -> "UsersController", "users/[id]" -> "UsersController"
        $segments = explode('/', $route);
        $controllerName = ucfirst($segments[0]);
        
        if ($controllerName === 'Index') {
            $controllerName = 'Home';
        }
        
        $controllerClass = "App\\Controllers\\{$controllerName}Controller";
        
        if (class_exists($controllerClass)) {
            return new $controllerClass();
        }
        
        return null;
    }
    
    /**
     * Dispatch to controller
     */
    private function dispatchToController($controller, $params, $isAjax)
    {
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Map HTTP methods to controller actions
        $methodMap = [
            'GET' => 'index',
            'POST' => 'store',
            'PUT' => 'update',
            'DELETE' => 'destroy'
        ];
        
        // For routes with ID parameter (e.g., /users/123), use show/update/destroy
        if (!empty($params) && $method === 'GET') {
            if (method_exists($controller, 'show')) {
                return $controller->show($params, $isAjax);
            }
        }
        
        // Check if mapped method exists
        if (isset($methodMap[$method]) && method_exists($controller, $methodMap[$method])) {
            $action = $methodMap[$method];
            return $controller->$action($params, $isAjax);
        }
        
        // Fallback to index
        if (method_exists($controller, 'index')) {
            return $controller->index($params, $isAjax);
        }
        
        return null;
    }
    
    /**
     * Render view
     */
    private function renderView($route, $params, $isAjax)
    {
        $route = $route === '/' ? 'index' : $route;
        $viewFile = VIEW_PATH . '/pages/' . $route . '/index.php';
        
        // Extract params to variables
        extract($params);
        
        // Start output buffering
        ob_start();
        
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            echo $this->renderErrorView('404');
        }
        
        $content = ob_get_clean();
        
        // Return appropriate response based on request type
        if ($isAjax) {
            return [
                'html' => $content,
                'title' => $this->extractTitle($content),
                'meta' => []
            ];
        }
        
        return $this->renderFullPage($content, $this->extractTitle($content));
    }
    
    /**
     * Render full HTML page with layout
     */
    private function renderFullPage($content, $title = 'Native MVC App')
    {
        $layoutFile = VIEW_PATH . '/layouts/main.php';
        
        ob_start();
        
        if (file_exists($layoutFile)) {
            include $layoutFile;
        } else {
            echo $content;
        }
        
        return ob_get_clean();
    }
    
    /**
     * Render error view
     */
    private function renderErrorView($code)
    {
        $errorFile = VIEW_PATH . '/errors/' . $code . '.php';
        
        ob_start();
        
        if (file_exists($errorFile)) {
            include $errorFile;
        } else {
            echo "<div class='error-{$code}'><h1>{$code}</h1><p>An error occurred</p></div>";
        }
        
        return ob_get_clean();
    }
    
    /**
     * Extract title from content
     */
    private function extractTitle($content)
    {
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $content, $matches)) {
            return strip_tags($matches[1]) . ' - Native MVC';
        }
        
        return 'Native MVC App';
    }
}
