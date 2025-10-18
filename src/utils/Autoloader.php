<?php
/**
 * PSR-4 Style Autoloader
 * Automatically loads classes based on namespace and file structure
 * 
 * @package NativeMVC
 */

namespace App\Utils;

class Autoloader
{
    private static $registered = false;
    
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
    }
    
    /**
     * Load a class file
     * 
     * @param string $class Fully qualified class name
     */
    public static function load($class)
    {
        // Namespace prefix
        $prefix = 'App\\';
        
        // Base directory for the namespace prefix
        $baseDir = SRC_PATH . '/';
        
        // Check if the class uses the namespace prefix
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        
        // Get the relative class name
        $relativeClass = substr($class, $len);
        
        // Replace namespace separators with directory separators
        // and append with .php
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        
        // Convert PascalCase to lowercase for directory names
        // Controllers, Models, etc. stay in their directories
        $parts = explode('/', $file);
        $filename = array_pop($parts);
        
        // Special handling for different types
        if (strpos($filename, 'Controller.php') !== false) {
            $file = $baseDir . 'controllers/' . $filename;
        } elseif (strpos($filename, 'Model.php') !== false) {
            $file = $baseDir . 'models/' . $filename;
        } elseif (strpos($filename, 'Middleware.php') !== false) {
            $file = $baseDir . 'middleware/' . $filename;
        } elseif (strpos($filename, 'Service.php') !== false) {
            $file = $baseDir . 'services/' . $filename;
        } elseif (in_array($filename, ['Router.php', 'Autoloader.php', 'View.php', 'Request.php', 'Response.php'])) {
            $file = $baseDir . 'utils/' . $filename;
        }
        
        // If the file exists, require it
        if (file_exists($file)) {
            require $file;
        }
    }
}
