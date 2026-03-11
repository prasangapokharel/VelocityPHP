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
        
        // Class map is populated at runtime as classes are discovered.
        // The static file cache (src/cache/classmap.php) was removed because
        // the directory never existed; runtime discovery is fast enough.
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
        
        // Replace namespace separators with directory separators.
        // On Linux/shared hosting the src/ subdirectories are all lowercase
        // (controllers/, models/, utils/, etc.) but the class name keeps its
        // original casing (e.g. HomeController.php).
        // Strategy: lowercase every path *segment* except the final filename.
        $relParts   = explode('/', str_replace('\\', '/', $relativeClass));
        $className  = array_pop($relParts);                        // keep original case
        $dirParts   = array_map('strtolower', $relParts);          // lowercase dirs
        $file = $baseDir . ($dirParts ? implode('/', $dirParts) . '/' : '') . $className . '.php';
        
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
        $utilsFiles = ['Router.php', 'Autoloader.php', 'View.php', 'Request.php', 'Response.php', 'Validator.php', 'Security.php', 'Session.php', 'Route.php', 'RouteCollection.php', 'VelocityCache.php', 'Logger.php', 'Debug.php', 'Auth.php', 'FileUpload.php'];
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
