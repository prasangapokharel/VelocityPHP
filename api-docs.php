<?php
/**
 * VelocityPhp API Documentation Generator
 * Generates API documentation from routes and controller docblocks
 * 
 * Run: php api-docs.php [format]
 * Formats: console (default), json, html
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

// Bootstrap
define('ROOT_PATH', __DIR__);
define('BASE_PATH', __DIR__);
define('SRC_PATH', __DIR__ . '/src');
define('CONFIG_PATH', SRC_PATH . '/config');
define('VIEW_PATH', SRC_PATH . '/views');

// Load autoloader
require_once SRC_PATH . '/utils/Autoloader.php';
\App\Utils\Autoloader::register();

// Load environment
if (file_exists(ROOT_PATH . '/.env')) {
    $lines = file(ROOT_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            putenv(trim($line));
        }
    }
}

// Load routes
require_once SRC_PATH . '/routes/web.php';

use App\Utils\RouteCollection;

/**
 * API Documentation Generator
 */
class ApiDocs
{
    private $routes = [];
    private $format = 'console';
    
    public function __construct(string $format = 'console')
    {
        $this->format = $format;
        $this->loadRoutes();
    }
    
    /**
     * Load and parse all routes
     */
    private function loadRoutes()
    {
        $routes = RouteCollection::getRoutes();
        
        foreach ($routes as $route) {
            $methods = $route->getMethods();
            $uri = $route->getUri();
            $action = $route->getAction();
            $name = $route->getName();
            $middleware = $route->getMiddleware();
            
            // Get controller info
            $controllerInfo = $this->parseAction($action);
            
            $this->routes[] = [
                'methods' => is_array($methods) ? $methods : [$methods],
                'uri' => $uri,
                'name' => $name,
                'controller' => $controllerInfo['controller'],
                'method' => $controllerInfo['method'],
                'middleware' => $middleware,
                'description' => $controllerInfo['description'],
                'parameters' => $controllerInfo['parameters'],
                'returns' => $controllerInfo['returns'],
                'is_api' => strpos($uri, '/api') === 0
            ];
        }
    }
    
    /**
     * Parse controller action and extract docblock info
     */
    private function parseAction($action): array
    {
        $result = [
            'controller' => null,
            'method' => null,
            'description' => '',
            'parameters' => [],
            'returns' => ''
        ];
        
        if (is_string($action) && strpos($action, '@') !== false) {
            list($controller, $method) = explode('@', $action);
            $result['controller'] = $controller;
            $result['method'] = $method;
            
            // Try to get docblock
            $controllerClass = "App\\Controllers\\{$controller}";
            if (class_exists($controllerClass)) {
                try {
                    $reflection = new ReflectionMethod($controllerClass, $method);
                    $docComment = $reflection->getDocComment();
                    
                    if ($docComment) {
                        $result = array_merge($result, $this->parseDocBlock($docComment));
                    }
                } catch (\Exception $e) {
                    // Skip if method doesn't exist
                }
            }
        } elseif (is_callable($action)) {
            $result['controller'] = 'Closure';
            $result['method'] = '-';
        }
        
        return $result;
    }
    
    /**
     * Parse PHPDoc block
     */
    private function parseDocBlock(string $docComment): array
    {
        $result = [
            'description' => '',
            'parameters' => [],
            'returns' => ''
        ];
        
        // Extract description (first non-tag lines)
        $lines = explode("\n", $docComment);
        $description = [];
        
        foreach ($lines as $line) {
            $line = trim($line, " \t\n\r\0\x0B/*");
            if (empty($line)) continue;
            
            if (strpos($line, '@') === 0) {
                // Parse tags
                if (strpos($line, '@param') === 0) {
                    $result['parameters'][] = trim(substr($line, 6));
                } elseif (strpos($line, '@return') === 0) {
                    $result['returns'] = trim(substr($line, 7));
                }
            } else {
                $description[] = $line;
            }
        }
        
        $result['description'] = implode(' ', $description);
        
        return $result;
    }
    
    /**
     * Generate documentation
     */
    public function generate(): string
    {
        switch ($this->format) {
            case 'json':
                return $this->generateJson();
            case 'html':
                return $this->generateHtml();
            default:
                return $this->generateConsole();
        }
    }
    
    /**
     * Generate console output
     */
    private function generateConsole(): string
    {
        $output = "\n";
        $output .= "\033[1;36m╔════════════════════════════════════════════════════════════════════════════╗\033[0m\n";
        $output .= "\033[1;36m║                    VELOCITYPHP API DOCUMENTATION                           ║\033[0m\n";
        $output .= "\033[1;36m╚════════════════════════════════════════════════════════════════════════════╝\033[0m\n\n";
        
        // Group routes
        $webRoutes = array_filter($this->routes, fn($r) => !$r['is_api']);
        $apiRoutes = array_filter($this->routes, fn($r) => $r['is_api']);
        
        // Web Routes
        $output .= "\033[1;33m═══ WEB ROUTES ═══\033[0m\n\n";
        $output .= $this->formatRoutesTable($webRoutes);
        
        // API Routes
        $output .= "\n\033[1;33m═══ API ROUTES ═══\033[0m\n\n";
        $output .= $this->formatRoutesTable($apiRoutes);
        
        // Summary
        $output .= "\n\033[1;32m═══ SUMMARY ═══\033[0m\n";
        $output .= "  Total Routes: " . count($this->routes) . "\n";
        $output .= "  Web Routes: " . count($webRoutes) . "\n";
        $output .= "  API Routes: " . count($apiRoutes) . "\n";
        
        return $output;
    }
    
    /**
     * Format routes as table
     */
    private function formatRoutesTable(array $routes): string
    {
        if (empty($routes)) {
            return "  No routes defined.\n";
        }
        
        $output = sprintf(
            "  \033[1m%-10s %-30s %-30s %-20s\033[0m\n",
            'METHOD', 'URI', 'ACTION', 'NAME'
        );
        $output .= "  " . str_repeat('-', 90) . "\n";
        
        foreach ($routes as $route) {
            $methods = implode('|', array_filter($route['methods'], fn($m) => $m !== 'HEAD'));
            $action = $route['controller'] ? "{$route['controller']}@{$route['method']}" : 'Closure';
            $name = $route['name'] ?: '-';
            
            // Color code methods
            $methodColor = match($methods) {
                'GET' => "\033[32m",     // Green
                'POST' => "\033[34m",    // Blue
                'PUT' => "\033[33m",     // Yellow
                'DELETE' => "\033[31m",  // Red
                'PATCH' => "\033[35m",   // Purple
                default => "\033[37m"    // White
            };
            
            $output .= sprintf(
                "  %s%-10s\033[0m %-30s %-30s %-20s\n",
                $methodColor,
                $methods,
                strlen($route['uri']) > 28 ? substr($route['uri'], 0, 27) . '…' : $route['uri'],
                strlen($action) > 28 ? substr($action, 0, 27) . '…' : $action,
                strlen($name) > 18 ? substr($name, 0, 17) . '…' : $name
            );
            
            // Show middleware if present
            if (!empty($route['middleware'])) {
                $output .= sprintf(
                    "           \033[90m└─ Middleware: %s\033[0m\n",
                    implode(', ', $route['middleware'])
                );
            }
        }
        
        return $output;
    }
    
    /**
     * Generate JSON output
     */
    private function generateJson(): string
    {
        $data = [
            'generated_at' => date('Y-m-d H:i:s'),
            'total_routes' => count($this->routes),
            'routes' => $this->routes
        ];
        
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Generate HTML output
     */
    private function generateHtml(): string
    {
        $webRoutes = array_filter($this->routes, fn($r) => !$r['is_api']);
        $apiRoutes = array_filter($this->routes, fn($r) => $r['is_api']);
        
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VelocityPhp API Documentation</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        h2 { color: #667eea; margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #667eea; color: white; font-weight: 600; }
        tr:hover { background: #f8f9ff; }
        .method { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; color: white; }
        .method-get { background: #28a745; }
        .method-post { background: #007bff; }
        .method-put { background: #ffc107; color: #333; }
        .method-delete { background: #dc3545; }
        .method-patch { background: #6f42c1; }
        .middleware { font-size: 11px; color: #666; margin-top: 4px; }
        code { background: #e9ecef; padding: 2px 6px; border-radius: 3px; font-size: 13px; }
        .summary { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 30px; }
        .summary h3 { margin-top: 0; }
        .stat { display: inline-block; margin-right: 30px; }
        .stat-value { font-size: 24px; font-weight: bold; color: #667eea; }
    </style>
</head>
<body>
    <div class="container">
        <h1>VelocityPhp API Documentation</h1>
        <p>Generated: ' . date('Y-m-d H:i:s') . '</p>
        
        <h2>Web Routes</h2>
        ' . $this->generateHtmlTable($webRoutes) . '
        
        <h2>API Routes</h2>
        ' . $this->generateHtmlTable($apiRoutes) . '
        
        <div class="summary">
            <h3>Summary</h3>
            <div class="stat">
                <div class="stat-value">' . count($this->routes) . '</div>
                <div>Total Routes</div>
            </div>
            <div class="stat">
                <div class="stat-value">' . count($webRoutes) . '</div>
                <div>Web Routes</div>
            </div>
            <div class="stat">
                <div class="stat-value">' . count($apiRoutes) . '</div>
                <div>API Routes</div>
            </div>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }
    
    /**
     * Generate HTML table for routes
     */
    private function generateHtmlTable(array $routes): string
    {
        if (empty($routes)) {
            return '<p>No routes defined.</p>';
        }
        
        $html = '<table>
            <thead>
                <tr>
                    <th>Method</th>
                    <th>URI</th>
                    <th>Action</th>
                    <th>Name</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($routes as $route) {
            $methods = array_filter($route['methods'], fn($m) => $m !== 'HEAD');
            $methodBadges = '';
            foreach ($methods as $method) {
                $class = 'method method-' . strtolower($method);
                $methodBadges .= "<span class=\"{$class}\">{$method}</span> ";
            }
            
            $action = $route['controller'] ? "{$route['controller']}@{$route['method']}" : 'Closure';
            $middleware = !empty($route['middleware']) 
                ? '<div class="middleware">Middleware: ' . implode(', ', $route['middleware']) . '</div>'
                : '';
            
            $html .= "<tr>
                <td>{$methodBadges}</td>
                <td><code>{$route['uri']}</code>{$middleware}</td>
                <td>{$action}</td>
                <td>{$route['name']}</td>
            </tr>";
        }
        
        $html .= '</tbody></table>';
        
        return $html;
    }
}

// Run
$format = $argv[1] ?? 'console';
$docs = new ApiDocs($format);
echo $docs->generate();
echo "\n";
