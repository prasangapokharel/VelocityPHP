<?php
/**
 * Logger Class
 * Provides clean, detailed error logging and debugging
 * 
 * @package NativeMVC
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
        self::$logPath = BASE_PATH . '/logs/';
        
        // Ensure log directory exists
        if (!is_dir(self::$logPath)) {
            mkdir(self::$logPath, 0777, true);
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
     * Log exception with full stack trace
     */
    public static function exception(\Exception $e)
    {
        $context = [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'code' => $e->getCode()
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
        
        // Add context if provided
        if (!empty($context)) {
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
