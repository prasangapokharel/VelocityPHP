<?php
/**
 * VelocityPhp Logger
 * Provides clean, detailed error logging and debugging
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

namespace App\Utils;

class Logger
{
    private static $logPath;
    
    /**
     * Initialize logger
     */
    public static function init()
    {
        // Try default location first
        $logPath = BASE_PATH . '/logs/';
        
        // Create directory if it doesn't exist (shared hosting compatible)
        if (!is_dir($logPath)) {
            @mkdir($logPath, 0777, true);
        }
        
        // Check if default location is writable
        if (is_dir($logPath) && is_writable($logPath)) {
            self::$logPath = $logPath;
        } else {
            // Fallback to system temp directory (shared hosting compatible)
            $fallbackPath = sys_get_temp_dir() . '/velocity_logs/';
            if (!is_dir($fallbackPath)) {
                @mkdir($fallbackPath, 0777, true);
            }
            if (is_dir($fallbackPath) && is_writable($fallbackPath)) {
                self::$logPath = $fallbackPath;
            } else {
                // Last resort: use system temp directly
                self::$logPath = sys_get_temp_dir() . '/';
            }
        }
    }
    
    /**
     * Log error with full details
     */
    public static function error($message, $context = [])
    {
        self::log('ERROR', $message, $context);
    }
    
    /**
     * Log warning
     */
    public static function warning($message, $context = [])
    {
        self::log('WARNING', $message, $context);
    }
    
    /**
     * Log info
     */
    public static function info($message, $context = [])
    {
        self::log('INFO', $message, $context);
    }
    
    /**
     * Log debug information
     */
    public static function debug($message, $context = [])
    {
        self::log('DEBUG', $message, $context);
    }
    
    /**
     * Log exception or error with full stack trace
     * Accepts Throwable (Exception or Error)
     */
    public static function exception(\Throwable $e)
    {
        $context = [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'code' => $e->getCode(),
            'type' => get_class($e)
        ];
        
        self::log('EXCEPTION', $e->getMessage(), $context);
    }
    
    /**
     * Core logging function
     */
    private static function log($level, $message, $context = [])
    {
        if (self::$logPath === null) {
            self::init();
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $date = date('Y-m-d');
        
        // Format log entry
        $logEntry = sprintf(
            "[%s] [%s] %s\n",
            $timestamp,
            $level,
            $message
        );
        
        // Add context if provided (sanitize paths for shared hosting)
        if (!empty($context)) {
            // Sanitize file paths in context
            foreach ($context as $key => $value) {
                if (is_string($value) && (strpos($key, 'file') !== false || strpos($key, 'path') !== false || strpos($key, 'location') !== false)) {
                    $context[$key] = defined('BASE_PATH') ? str_replace(BASE_PATH, '[BASE]', $value) : $value;
                }
            }
            $logEntry .= "Context:\n";
            foreach ($context as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value, JSON_PRETTY_PRINT);
                }
                $logEntry .= "  {$key}: {$value}\n";
            }
        }
        
        // Add request information
        $logEntry .= sprintf(
            "Request: %s %s\n",
            $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            $_SERVER['REQUEST_URI'] ?? 'N/A'
        );
        
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $logEntry .= "IP: {$_SERVER['REMOTE_ADDR']}\n";
        }
        
        $logEntry .= str_repeat('-', 80) . "\n\n";
        
        // Write to daily log file
        $logFile = self::$logPath . "app-{$date}.log";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
        
        // Also write to error.log for errors and exceptions
        if (in_array($level, ['ERROR', 'EXCEPTION'])) {
            $errorLog = self::$logPath . 'error.log';
            file_put_contents($errorLog, $logEntry, FILE_APPEND);
        }
    }
    
    /**
     * Clear old log files (older than 30 days)
     */
    public static function cleanOldLogs($days = 30)
    {
        if (self::$logPath === null) {
            self::init();
        }
        
        $files = glob(self::$logPath . 'app-*.log');
        $cutoff = strtotime("-{$days} days");
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
            }
        }
    }
    
    /**
     * Get latest log entries
     */
    public static function getLatest($count = 50, $level = null)
    {
        if (self::$logPath === null) {
            self::init();
        }
        
        $logFile = self::$logPath . 'app-' . date('Y-m-d') . '.log';
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $lines = file($logFile);
        $entries = [];
        
        foreach (array_reverse($lines) as $line) {
            if (preg_match('/^\[(.*?)\] \[(.*?)\] (.*)$/', $line, $matches)) {
                if ($level === null || $matches[2] === $level) {
                    $entries[] = [
                        'timestamp' => $matches[1],
                        'level' => $matches[2],
                        'message' => $matches[3]
                    ];
                    
                    if (count($entries) >= $count) {
                        break;
                    }
                }
            }
        }
        
        return $entries;
    }
}
