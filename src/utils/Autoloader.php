<?php
/**
 * VelocityPhp PSR-4 Autoloader
 * Optimized automatic class loading
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

namespace App\Utils;

class Autoloader
{
    private static $registered = false;
    private static $classMap = [];
    private static $fileCache = [];
    
    /**
     * Register the autoloader
     */
    public static function register()
    {
        if (self::$registered) {
            return;
        }
        
        spl_autoload_register([__CLASS__, 'load']);
        self::$registered = true;
        
        // Pre-load class map cache file if exists
        $cacheFile = BASE_PATH . '/src/cache/classmap.php';
        if (file_exists($cacheFile)) {
            self::$classMap = require $cacheFile;
        }
    }
    
    /**
     * Load a class file (optimized with caching)
     * 
     * @param string $class Fully qualified class name
     */
    public static function load($class)
    {
        // Check cache first
        if (isset(self::$classMap[$class])) {
            $file = self::$classMap[$class];
            if (isset(self::$fileCache[$file])) {
                return;
            }
            if (file_exists($file)) {
                self::$fileCache[$file] = true;
                require $file;
                return;
            }
        }
        
        // Namespace prefix
        $prefix = 'App\\';
        
        // Check if the class uses the namespace prefix
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        
        // Base directory for the namespace prefix
        $baseDir = SRC_PATH . '/';
        
        // Get the relative class name
        $relativeClass = substr($class, $len);
        
        // Replace namespace separators with directory separators
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        
        // Optimized path resolution with early file check
        if (file_exists($file)) {
            self::$classMap[$class] = $file;
            self::$fileCache[$file] = true;
            require $file;
            return;
        }
        
        // Special handling for different types (only if direct path doesn't exist)
        $parts = explode('/', $file);
        $filename = array_pop($parts);
        
        $specialPaths = [
            'Controller' => 'controllers/',
            'Model' => 'models/',
            'Middleware' => 'middleware/',
            'Service' => 'services/',
        ];
        
        foreach ($specialPaths as $suffix => $dir) {
            if (strpos($filename, $suffix . '.php') !== false) {
                $file = $baseDir . $dir . $filename;
                if (file_exists($file)) {
                    self::$classMap[$class] = $file;
                    self::$fileCache[$file] = true;
                    require $file;
                    return;
                }
            }
        }
        
        // Utils directory check
        $utilsFiles = ['Router.php', 'Autoloader.php', 'View.php', 'Request.php', 'Response.php', 'Validator.php', 'Security.php', 'Session.php', 'Route.php', 'RouteCollection.php', 'VelocityCache.php', 'Logger.php', 'Debug.php', 'Auth.php'];
        if (in_array($filename, $utilsFiles)) {
            $file = $baseDir . 'utils/' . $filename;
            if (file_exists($file)) {
                self::$classMap[$class] = $file;
                self::$fileCache[$file] = true;
                require $file;
                return;
            }
        }
        
        // Config directory
        if (strpos($relativeClass, 'Config') !== false) {
            $file = $baseDir . 'config/' . $filename;
            if (file_exists($file)) {
                self::$classMap[$class] = $file;
                self::$fileCache[$file] = true;
                require $file;
                return;
            }
        }
        
        // Core and Database directories
        if (strpos($relativeClass, 'Core') !== false) {
            $file = $baseDir . 'core/' . $filename;
        } elseif (strpos($relativeClass, 'Database') !== false) {
            $file = $baseDir . 'database/' . $filename;
        }
        
        if (isset($file) && file_exists($file)) {
            self::$classMap[$class] = $file;
            self::$fileCache[$file] = true;
            require $file;
        }
    }
}
