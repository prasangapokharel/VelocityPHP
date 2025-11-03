<?php
/**
 * VelocityPhp Router
 * Ultra-fast router with caching and optimized route matching
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

namespace App\Utils;

class Router
{
    private $routes = [];
    private $patterns = [];
    private static $routeCache = [];
    private static $cacheEnabled = true;
    private static $matchCount = 0;
    
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
        self::$matchCount++;
        
        $method = strtoupper($method);
        $uri = $this->cleanUri($uri);
        
        $cache = \App\Utils\VelocityCache::getInstance();
        $cacheKey = null;
        
        if ($cache->isEnabled() && $method === 'GET') {
            $getParams = array_filter($_GET ?? [], function($key) {
                return $key !== '_' && strpos($key, '_') !== 0;
            }, ARRAY_FILTER_USE_KEY);
            
            $cacheKey = $cache->generateKey($uri, $method, $getParams);
            $cached = $cache->get($cacheKey);
            if ($cached !== null) {
                if (is_string($cached)) {
                    if (strpos($cached, '{') === 0 || strpos($cached, '[') === 0) {
                        header('Content-Type: application/json');
                        return $cached;
                    }
                    return $cached;
                }
                if (is_array($cached)) {
                    if (isset($cached['html'])) {
                        if ($isAjax) {
                            return $cached;
                        } else {
                            return $this->renderFullPage($cached['html'], $cached['title'] ?? 'VelocityPhp', false);
                        }
                    }
                }
                return $cached;
            }
        }
        
        // Load routes if not already loaded (cached across requests)
        static $routesLoaded = false;
        if (!$routesLoaded) {
            $routeFile = BASE_PATH . '/src/routes/web.php';
            if (file_exists($routeFile)) {
                require $routeFile;
                $routesLoaded = true;
            }
        }
        
        // First, try RouteCollection routes (registered routes)
        $routeCollection = \App\Utils\RouteCollection::dispatch($uri, $method);
        
        // Check if RouteCollection found a route
        // null = no route found
        // array with 'status' => 'ROUTE_FOUND_NULL' = route found but controller returned null (render view)
        // other = route found and controller returned something (JSON, HTML, etc.)
        if (is_array($routeCollection) && isset($routeCollection['status']) && $routeCollection['status'] === 'ROUTE_FOUND_NULL') {
            // Route was found, controller returned null - render the view file
            // Get the route object and extract controller@method to determine view path
            $matchedRoute = $routeCollection['route'];
            $action = $matchedRoute->getAction();
            $params = $matchedRoute->getParameters();
            
            $response = null;
            
            // Extract view path from controller@method
            if (is_string($action) && strpos($action, '@') !== false) {
                list($controller, $method) = explode('@', $action);
                
                // Map controller method to view path
                // HomeController@prasanga -> index/prasanga
                // HomeController@index -> index/index
                if (strtolower($controller) === 'homecontroller') {
                    $viewPath = 'index/' . strtolower($method);
                } else {
                    // For other controllers, use controller name as directory
                    $controllerName = str_replace('Controller', '', $controller);
                    $viewPath = strtolower($controllerName) . '/' . strtolower($method);
                }
                
                // Render the view using the determined path
                $response = $this->renderViewFromPath($viewPath, $params, $isAjax);
            } else {
                // Fallback to URI-based view finding
                $route = $this->findRoute($uri);
                if ($route) {
                    $params = $this->extractParams($uri, $route);
                    $response = $this->renderView($route, $params, $isAjax);
                } else {
                    // If view not found, show 404
                    http_response_code(404);
                    if ($isAjax) {
                        $response = [
                            'success' => false,
                            'html' => $this->renderErrorView('404'),
                            'title' => '404 - Page Not Found'
                        ];
                    } else {
                        $response = $this->renderFullPage($this->renderErrorView('404'), '404 - Page Not Found', true);
                    }
                }
            }
            
            // Cache the response
            if ($cacheKey && $method === 'GET' && $response !== null) {
                $cacheResponse = $response;
                if (is_string($response) && !$isAjax) {
                    $cacheResponse = ['html' => $response, 'title' => $this->extractTitle($response)];
                }
                $cache->put($cacheKey, $cacheResponse, 3600);
            }
            
            // Handle response format for non-AJAX requests
            if (is_array($response) && !$isAjax && isset($response['html'])) {
                return $this->renderFullPage($response['html'], $response['title'] ?? 'VelocityPhp', false);
            }
            
            return $response;
        } elseif ($routeCollection !== null) {
            $response = null;
            $cacheResponse = null;
            
            if (is_string($routeCollection)) {
                if ($isAjax || (strpos($routeCollection, '{') === 0 || strpos($routeCollection, '[') === 0)) {
                    $response = $routeCollection;
                    $cacheResponse = $response;
                } else {
                    $response = $isAjax ? ['html' => $routeCollection, 'title' => 'VelocityPhp'] : $this->renderFullPage($routeCollection, 'VelocityPhp');
                    $cacheResponse = $isAjax ? ['html' => $routeCollection, 'title' => 'VelocityPhp'] : $response;
                }
            } elseif (is_array($routeCollection)) {
                if ($isAjax) {
                    $response = $routeCollection;
                    $cacheResponse = $routeCollection;
                } else {
                    if (isset($routeCollection['html'])) {
                        $response = $this->renderFullPage($routeCollection['html'], $routeCollection['title'] ?? 'VelocityPhp', false);
                        $cacheResponse = $routeCollection;
                    } else {
                        $response = $routeCollection;
                        $cacheResponse = $routeCollection;
                    }
                }
            } else {
                $response = $routeCollection;
                $cacheResponse = $response;
            }
            
            if ($cacheKey && $method === 'GET' && $cacheResponse !== null) {
                $cache->put($cacheKey, $cacheResponse, 3600);
            }
            
            return $response;
        }
        
        // RouteCollection returned null - no route found, try file-based routing
        // Don't show 404 yet, try to find view file first
        
        if (!$cacheKey && $cache->isEnabled() && $method === 'GET') {
            $getParams = array_filter($_GET ?? [], function($key) {
                return $key !== '_' && strpos($key, '_') !== 0;
            }, ARRAY_FILTER_USE_KEY);
            $cacheKey = $cache->generateKey($uri, $method, $getParams);
        }
        
        // Check route cache first
        $routeCacheKey = md5($uri . $method . ($isAjax ? '1' : '0'));
        
        if (self::$cacheEnabled && isset(self::$routeCache[$routeCacheKey])) {
            $cached = self::$routeCache[$routeCacheKey];
            return $this->executeCachedRoute($cached, $isAjax);
        }
        
        // Try to find a matching route (view files)
        $route = $this->findRoute($uri);
        
        if (!$route) {
            http_response_code(404);
            // Return clean 404 error - no verbose messages, no debug panel
            if ($isAjax) {
                return [
                    'success' => false,
                    'html' => $this->renderErrorView('404'),
                    'title' => '404 - Page Not Found'
                ];
            }
            return $this->renderFullPage($this->renderErrorView('404'), '404 - Page Not Found', true);
        }
        
        // Extract parameters from dynamic routes
        $params = $this->extractParams($uri, $route);
        
        // Check if controller exists for this route
        $controller = $this->getControllerForRoute($route, $method);
        
        // Cache route information for future requests
        if (self::$cacheEnabled) {
            self::$routeCache[$routeCacheKey] = [
                'route' => $route,
                'params' => $params,
                'has_controller' => $controller !== null,
                'controller_class' => $controller ? get_class($controller) : null
            ];
        }
        
        if ($controller) {
            $response = $this->dispatchToController($controller, $params, $isAjax, $method);
            
            if ($response === null) {
                $response = $this->renderView($route, $params, $isAjax);
            }
            
            return $this->formatAndCacheResponse($response, $isAjax, $cacheKey, $cache, $method);
        }
        
        $response = $this->renderView($route, $params, $isAjax);
        
        return $this->formatAndCacheResponse($response, $isAjax, $cacheKey, $cache, $method);
    }
    
    /**
     * Execute cached route
     */
    private function executeCachedRoute($cached, $isAjax)
    {
        $route = $cached['route'];
        $params = $cached['params'];
        
        if ($cached['has_controller']) {
            $controllerClass = $cached['controller_class'];
            $controller = new $controllerClass();
            
            $response = $this->dispatchToController(
                $controller,
                $params,
                $isAjax,
                $_SERVER['REQUEST_METHOD']
            );
            
            // If controller returns null, render view directly (for both AJAX and regular requests)
            if ($response === null) {
                return $this->renderView($route, $params, $isAjax);
            }
            
            return $response;
        }
        
        return $this->renderView($route, $params, $isAjax);
    }
    
    /**
     * Clean and normalize URI (optimized)
     */
    private function cleanUri($uri)
    {
        // Remove query string FIRST (important for refresh with ?_=timestamp)
        // This must happen before any other processing
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        
        // Fast path for root
        if ($uri === '/' || $uri === '') {
            return '/';
        }
        
        // Preserve leading slash for route matching
        $hasLeadingSlash = ($uri[0] === '/');
        $uri = trim($uri, '/');
        
        // Basic sanitization (faster than filter_var)
        // Allow alphanumeric, slashes, hyphens, underscores for routes
        $uri = preg_replace('/[^a-zA-Z0-9\/_-]/', '', $uri);
        
        // Return cleaned URI with preserved leading slash
        if ($uri === '') {
            return '/';
        }
        
        return $hasLeadingSlash ? '/' . $uri : $uri;
    }
    
    /**
     * Find matching route for URI (optimized with early returns)
     */
    private function findRoute($uri)
    {
        $uri = $uri === '/' ? '' : $uri;
        
        // Fast path: Check for API routes first (most common in modern apps)
        if (strpos($uri, 'api/') === 0) {
            return $uri;
        }
        
        // Fast path: Check direct match (most common case)
        $viewPath = VIEW_PATH . '/pages/' . ($uri ?: 'index') . '/index.php';
        
        if (is_file($viewPath)) {
            return $uri ?: 'index';
        }
        
        // Slower path: Check for dynamic routes
        if (strpos($uri, '/') !== false) {
            $segments = explode('/', $uri);
            $dynamicRoute = $this->findDynamicRoute($segments);
            
            if ($dynamicRoute) {
                return $dynamicRoute;
            }
        }
        
        return null;
    }
    
    /**
     * Find dynamic route with parameters (optimized)
     */
    private function findDynamicRoute($segments)
    {
        $pagesPath = VIEW_PATH . '/pages';
        $segmentCount = count($segments);
        
        // Try to match segments with potential dynamic routes
        for ($i = $segmentCount; $i >= 0; $i--) {
            $staticParts = array_slice($segments, 0, $i);
            $dynamicParts = array_slice($segments, $i);
            
            // Build potential path
            $path = $pagesPath . ($staticParts ? '/' . implode('/', $staticParts) : '');
            
            // Look for dynamic segment directories
            if (!is_dir($path)) continue;
            
            $dirs = @scandir($path);
            if (!$dirs) continue;
            
            foreach ($dirs as $dir) {
                if ($dir[0] === '.' || $dir[0] === '..') continue;
                
                // Check if it's a dynamic segment (e.g., [id])
                if ($dir[0] === '[' && $dir[strlen($dir) - 1] === ']') {
                    $paramName = substr($dir, 1, -1);
                    $checkPath = $path . '/' . $dir;
                    
                    // Check if this route exists
                    if (!empty($dynamicParts)) {
                        $remainingPath = $checkPath;
                        for ($j = 1; $j < count($dynamicParts); $j++) {
                            $remainingPath .= '/' . $dynamicParts[$j];
                        }
                        $remainingPath .= '/index.php';
                        
                        if (is_file($remainingPath)) {
                            $route = ($staticParts ? implode('/', $staticParts) . '/' : '') . 
                                    '[' . $paramName . ']';
                            for ($j = 1; $j < count($dynamicParts); $j++) {
                                $route .= '/' . $dynamicParts[$j];
                            }
                            return $route;
                        }
                    } else {
                        // Check for index.php in dynamic directory
                        if (is_file($checkPath . '/index.php')) {
                            $route = ($staticParts ? implode('/', $staticParts) . '/' : '') . 
                                    '[' . $paramName . ']';
                            return $route;
                        }
                    }
                }
            }
        }
        
        return null;
    }
    
    /**
     * Extract parameters from dynamic route (optimized)
     */
    private function extractParams($uri, $route)
    {
        $params = [];
        
        // Fast path: API routes
        if (strpos($route, 'api/') === 0) {
            $uriSegments = explode('/', $uri);
            $count = count($uriSegments);
            
            // api/users/123 -> extract ID
            if ($count >= 3 && ctype_digit($uriSegments[2])) {
                $params['id'] = (int)$uriSegments[2];
            }
            
            // For nested routes like api/users/123/posts/456
            if ($count >= 5 && ctype_digit($uriSegments[4])) {
                $params['nested_id'] = (int)$uriSegments[4];
            }
            
            return $params;
        }
        
        $uriSegments = $uri === '/' ? [] : explode('/', $uri);
        $routeSegments = $route === 'index' ? [] : explode('/', $route);
        
        foreach ($routeSegments as $index => $segment) {
            if ($segment[0] === '[' && $segment[strlen($segment) - 1] === ']') {
                $paramName = substr($segment, 1, -1);
                $params[$paramName] = $uriSegments[$index] ?? null;
            }
        }
        
        return $params;
    }
    
    /**
     * Get controller for route (optimized with static cache)
     */
    private function getControllerForRoute($route, $method)
    {
        static $controllerCache = [];
        
        $controllerClass = $this->resolveControllerClass($route);
        
        if (!$controllerClass) {
            return null;
        }
        
        if (isset($controllerCache[$controllerClass])) {
            return new $controllerClass();
        }
        
        if (class_exists($controllerClass)) {
            $controllerCache[$controllerClass] = true;
            return new $controllerClass();
        }
        
        return null;
    }
    
    /**
     * Dispatch to controller (optimized)
     */
    private function dispatchToController($controller, $params, $isAjax, $httpMethod)
    {
        // Map HTTP methods to controller actions
        static $methodMap = [
            'GET' => 'index',
            'POST' => 'store',
            'PUT' => 'update',
            'DELETE' => 'destroy'
        ];
        
        // For routes with ID parameter, use show/update/destroy
        if (!empty($params) && isset($params['id'])) {
            if ($httpMethod === 'GET') {
                if (method_exists($controller, 'show')) {
                    return $controller->show($params, $isAjax);
                }
            } else if (isset($methodMap[$httpMethod])) {
                $action = $methodMap[$httpMethod];
                if (method_exists($controller, $action)) {
                    return $controller->$action($params, $isAjax);
                }
            }
        }
        
        // Check if mapped method exists
        if (isset($methodMap[$httpMethod])) {
            $action = $methodMap[$httpMethod];
            if (method_exists($controller, $action)) {
                return $controller->$action($params, $isAjax);
            }
        }
        
        // Fallback to index
        if (method_exists($controller, 'index')) {
            return $controller->index($params, $isAjax);
        }
        
        return null;
    }
    
    /**
     * Render view from explicit path (for RouteCollection routes)
     * Supports both nested structure (index/prasanga/index.php) and flat structure (index/prasanga.php)
     */
    private function renderViewFromPath($viewPath, $params, $isAjax)
    {
        // First, try nested structure: index/prasanga/index.php
        $nestedViewFile = VIEW_PATH . '/pages/' . $viewPath . '/index.php';
        $flatViewFile = VIEW_PATH . '/pages/' . $viewPath . '.php';
        
        // Prefer nested structure, fallback to flat structure
        if (is_file($nestedViewFile)) {
            $viewFile = $nestedViewFile;
        } elseif (is_file($flatViewFile)) {
            $viewFile = $flatViewFile;
        } else {
            // View not found
            http_response_code(404);
            $content = $this->renderErrorView('404');
            // Return error page without debug panel
            if ($isAjax) {
                return [
                    'success' => false,
                    'html' => $content,
                    'title' => '404 - Page Not Found'
                ];
            }
            return $this->renderFullPage($content, '404 - Page Not Found', true);
        }
        
        // Extract params to variables
        extract($params, EXTR_SKIP);
        
        // Use output buffering
        ob_start();
        include $viewFile;
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
     * Render view (with caching support)
     */
    private function renderView($route, $params, $isAjax)
    {
        $route = $route === '/' ? 'index' : $route;
        $viewFile = VIEW_PATH . '/pages/' . $route . '/index.php';
        
        // Check if view exists
        if (!is_file($viewFile)) {
            http_response_code(404);
            $content = $this->renderErrorView('404');
            // Return error page without debug panel
            if ($isAjax) {
                return [
                    'success' => false,
                    'html' => $content,
                    'title' => '404 - Page Not Found'
                ];
            }
            return $this->renderFullPage($content, '404 - Page Not Found', true);
        } else {
            // Extract params to variables
            extract($params, EXTR_SKIP);
            
            // Use output buffering
            ob_start();
            include $viewFile;
            $content = ob_get_clean();
        }
        
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
    private function renderFullPage($content, $title = 'VelocityPhp', $isErrorPage = false)
    {
        // Ensure Content-Type is set to HTML for regular requests
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }
        
        $layoutFile = VIEW_PATH . '/layouts/main.php';
        
        ob_start();
        
        if (is_file($layoutFile)) {
            // Pass flag to layout to skip debug panel on error pages
            $skipDebugPanel = $isErrorPage;
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
        
        if (is_file($errorFile)) {
            include $errorFile;
        } else {
            echo "<div class='error-{$code}'><h1>{$code}</h1><p>An error occurred</p></div>";
        }
        
        return ob_get_clean();
    }
    
    /**
     * Extract title from content (optimized)
     */
    private function extractTitle($content)
    {
        // Fast path: look for h1 tag
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $content, $matches)) {
            return strip_tags($matches[1]) . ' - VelocityPhp';
        }
        
        return 'VelocityPhp';
    }
    
    /**
     * Format response for AJAX/non-AJAX and cache if needed
     */
    private function formatAndCacheResponse($response, $isAjax, $cacheKey, $cache, $method)
    {
        $cacheResponse = $response;
        
        if (is_array($response) && !$isAjax) {
            if (isset($response['html'])) {
                $cacheResponse = $response;
                $response = $this->renderFullPage($response['html'], $response['title'] ?? 'VelocityPhp', false);
            } else {
                $cacheResponse = $response;
                $response = json_encode($response);
                header('Content-Type: application/json');
            }
        }
        
        if ($cacheKey && $method === 'GET' && $cacheResponse !== null) {
            $cache->put($cacheKey, $cacheResponse, 3600);
        }
        
        return $response;
    }
    
    /**
     * Resolve controller class name from route
     */
    private function resolveControllerClass($route)
    {
        $segments = explode('/', $route);
        
        if (strpos($route, 'api/') === 0) {
            if (!isset($segments[1])) {
                return null;
            }
            return "App\\Controllers\\" . ucfirst($segments[1]) . "Controller";
        }
        
        $controllerName = ucfirst($segments[0] ?? '');
        
        if ($controllerName === 'Index' || $controllerName === '') {
            $controllerName = 'Home';
        }
        
        return "App\\Controllers\\{$controllerName}Controller";
    }
    
    /**
     * Clear route cache
     */
    public static function clearCache()
    {
        self::$routeCache = [];
    }
    
    /**
     * Get router statistics
     */
    public static function getStats()
    {
        return [
            'matches' => self::$matchCount,
            'cached_routes' => count(self::$routeCache),
            'cache_enabled' => self::$cacheEnabled
        ];
    }
    
    /**
     * Enable/disable route caching
     */
    public static function setCacheEnabled($enabled)
    {
        self::$cacheEnabled = $enabled;
    }
}
