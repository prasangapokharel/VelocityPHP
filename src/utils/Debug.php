<?php
/**
 * VelocityPhp Debug Helper Class
 * Deep error analysis and beautiful error display
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

namespace App\Utils;

class Debug
{
    private static $errorCount = 0;
    private static $errorLog = [];
    
    /**
     * Display beautiful error page with deep analysis
     * Accepts Throwable (Exception or Error)
     */
    public static function showError(\Throwable $exception, $isAjax = false)
    {
        self::$errorCount++;
        self::logError($exception);
        
        // Always show clean errors - never verbose debug pages
        // This ensures a perfectly clean system
        if ($isAjax) {
            return self::getAjaxError($exception, false, false);
        }
        
        return self::getHtmlError($exception, false, false);
    }
    
    /**
     * Get AJAX error response with deep details
     */
    private static function getAjaxError(\Throwable $exception, $debug, $deepErrors)
    {
        // Simple user-friendly error for production
        if (!$debug) {
            return json_encode([
                'success' => false,
                'error' => 'An error occurred. Please try again later.',
                'message' => 'Something went wrong'
            ], JSON_PRETTY_PRINT);
        }
        
        // Debug mode - show details but keep it simple
        $error = [
            'success' => false,
            'error' => $exception->getMessage(),
            'message' => 'An error occurred'
        ];
        
        // Only add detailed info if deep errors enabled (sanitized for shared hosting)
        if ($deepErrors) {
            $file = $exception->getFile();
            // Sanitize path to prevent exposing absolute paths (shared hosting security)
            $error['file'] = basename($file);
            $error['line'] = $exception->getLine();
        }
        
        return json_encode($error, JSON_PRETTY_PRINT);
    }
    
    /**
     * Get beautiful HTML error page with deep analysis
     */
    private static function getHtmlError(\Throwable $exception, $debug, $deepErrors)
    {
        // Always show clean, simple error page - never show verbose debug info
        // Never show the verbose debug page - always use clean production error
        return self::getProductionError();
    }
    
    /**
     * OLD VERBOSE DEBUG CODE - REMOVED FOR CLEAN ERRORS
     * This code is kept for reference but never executed
     */
    private static function getHtmlErrorVerbose(\Throwable $exception, $debug, $deepErrors)
    {
        $file = $exception->getFile();
        $line = $exception->getLine();
        $message = $exception->getMessage();
        $type = get_class($exception);
        $trace = $exception->getTrace();
        
        // Get code snippet
        $codeSnippet = self::getCodeSnippet($file, $line, 15);
        $context = $deepErrors ? self::getContext() : null;
        $memoryUsage = self::getMemoryUsage();
        $dbStats = $deepErrors ? self::getDatabaseStats() : null;
        $requestData = $deepErrors ? self::getRequestData() : null;
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>VelocityPhp Error - <?php echo htmlspecialchars($type); ?></title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    background: #0f172a;
                    color: #e2e8f0;
                    padding: 2rem;
                }
                .container { max-width: 1600px; margin: 0 auto; }
                .header {
                    background: linear-gradient(135deg, #dc2626, #991b1b);
                    padding: 2rem;
                    border-radius: 12px;
                    margin-bottom: 2rem;
                    box-shadow: 0 10px 30px rgba(220, 38, 38, 0.3);
                }
                .logo {
                    font-size: 1.5rem;
                    font-weight: 700;
                    color: white;
                    margin-bottom: 1rem;
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                }
                .logo-icon {
                    width: 32px;
                    height: 32px;
                    background: white;
                    border-radius: 6px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.2rem;
                }
                .error-type {
                    font-size: 1rem;
                    color: #fca5a5;
                    text-transform: uppercase;
                    letter-spacing: 2px;
                    margin-bottom: 0.5rem;
                }
                .error-message {
                    font-size: 1.5rem;
                    font-weight: 600;
                    color: white;
                    margin-bottom: 1rem;
                }
                .error-location {
                    display: flex;
                    gap: 2rem;
                    font-size: 0.9rem;
                    color: #fca5a5;
                    flex-wrap: wrap;
                }
                .stats-bar {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                    gap: 1rem;
                    margin-bottom: 2rem;
                }
                .stat-card {
                    background: #1e293b;
                    padding: 1rem;
                    border-radius: 8px;
                    border-left: 4px solid #3b82f6;
                }
                .stat-label {
                    color: #94a3b8;
                    font-size: 0.85rem;
                    margin-bottom: 0.25rem;
                }
                .stat-value {
                    color: #60a5fa;
                    font-size: 1.25rem;
                    font-weight: 600;
                }
                .card {
                    background: #1e293b;
                    border-radius: 12px;
                    padding: 2rem;
                    margin-bottom: 2rem;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
                }
                .card-title {
                    font-size: 1.2rem;
                    font-weight: 600;
                    color: #60a5fa;
                    margin-bottom: 1rem;
                    padding-bottom: 0.5rem;
                    border-bottom: 2px solid #334155;
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                }
                .code-block {
                    background: #0f172a;
                    border-radius: 8px;
                    padding: 1rem;
                    overflow-x: auto;
                    font-family: 'Courier New', monospace;
                    font-size: 0.9rem;
                    line-height: 1.6;
                }
                .code-line {
                    display: flex;
                    padding: 0.25rem 0;
                }
                .line-number {
                    color: #64748b;
                    width: 60px;
                    text-align: right;
                    padding-right: 1rem;
                    user-select: none;
                }
                .line-code {
                    flex: 1;
                    color: #cbd5e1;
                    white-space: pre;
                }
                .line-error {
                    background: rgba(220, 38, 38, 0.2);
                    border-left: 4px solid #dc2626;
                    padding-left: 0.5rem;
                }
                .line-error .line-number {
                    color: #fca5a5;
                    font-weight: bold;
                }
                .line-error .line-code {
                    color: #fef2f2;
                    font-weight: 500;
                }
                .trace-item {
                    background: #0f172a;
                    border-radius: 8px;
                    padding: 1rem;
                    margin-bottom: 0.5rem;
                    border-left: 4px solid #475569;
                    transition: all 0.2s;
                }
                .trace-item:hover {
                    border-left-color: #60a5fa;
                    background: #1a2332;
                    transform: translateX(2px);
                }
                .trace-number {
                    display: inline-block;
                    background: #475569;
                    color: white;
                    padding: 0.25rem 0.75rem;
                    border-radius: 4px;
                    font-size: 0.8rem;
                    margin-right: 0.5rem;
                }
                .trace-function {
                    color: #60a5fa;
                    font-weight: 600;
                    margin-bottom: 0.5rem;
                }
                .trace-location {
                    color: #94a3b8;
                    font-size: 0.85rem;
                    margin-top: 0.25rem;
                }
                .trace-args {
                    margin-top: 0.5rem;
                    padding-top: 0.5rem;
                    border-top: 1px solid #334155;
                    color: #94a3b8;
                    font-size: 0.85rem;
                }
                .info-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                    gap: 1rem;
                }
                .info-item {
                    background: #0f172a;
                    padding: 1rem;
                    border-radius: 8px;
                }
                .info-label {
                    color: #64748b;
                    font-size: 0.85rem;
                    margin-bottom: 0.25rem;
                }
                .info-value {
                    color: #e2e8f0;
                    font-weight: 500;
                    word-break: break-word;
                }
                .badge {
                    display: inline-block;
                    padding: 0.25rem 0.75rem;
                    border-radius: 4px;
                    font-size: 0.85rem;
                    font-weight: 500;
                }
                .badge-error { background: #dc2626; color: white; }
                .badge-warning { background: #f59e0b; color: white; }
                .badge-info { background: #3b82f6; color: white; }
                .badge-success { background: #10b981; color: white; }
                .variable-dump {
                    background: #0f172a;
                    padding: 1rem;
                    border-radius: 8px;
                    margin-top: 0.5rem;
                    overflow-x: auto;
                }
                .variable-dump pre {
                    color: #94a3b8;
                    font-size: 0.85rem;
                    line-height: 1.6;
                }
                .tabs {
                    display: flex;
                    gap: 0.5rem;
                    margin-bottom: 1rem;
                }
                .tab {
                    padding: 0.5rem 1rem;
                    background: #0f172a;
                    border-radius: 6px;
                    cursor: pointer;
                    color: #94a3b8;
                    transition: all 0.2s;
                }
                .tab:hover {
                    background: #1a2332;
                    color: #60a5fa;
                }
                .tab.active {
                    background: #3b82f6;
                    color: white;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="logo">
                        <div class="logo-icon">⚡</div>
                        VelocityPhp
                    </div>
                    <div class="error-type"><?php echo htmlspecialchars($type); ?></div>
                    <div class="error-message"><?php echo htmlspecialchars($message); ?></div>
                    <div class="error-location">
                        <span>📁 <?php echo htmlspecialchars($file); ?></span>
                        <span>📍 Line <?php echo $line; ?></span>
                        <span>⏰ <?php echo date('Y-m-d H:i:s'); ?></span>
                    </div>
                </div>
                
                <?php if ($deepErrors): ?>
                <div class="stats-bar">
                    <div class="stat-card">
                        <div class="stat-label">Memory Usage</div>
                        <div class="stat-value"><?php echo $memoryUsage['current']; ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Peak Memory</div>
                        <div class="stat-value"><?php echo $memoryUsage['peak']; ?></div>
                    </div>
                    <?php if ($dbStats): ?>
                    <div class="stat-card">
                        <div class="stat-label">DB Queries</div>
                        <div class="stat-value"><?php echo $dbStats['queries']; ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Cache Hits</div>
                        <div class="stat-value"><?php echo $dbStats['cache_hits']; ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="stat-card">
                        <div class="stat-label">PHP Version</div>
                        <div class="stat-value"><?php echo phpversion(); ?></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($codeSnippet): ?>
                <div class="card">
                    <div class="card-title">📝 Code Snippet</div>
                    <div class="code-block">
                        <?php foreach ($codeSnippet as $lineNum => $lineCode): ?>
                            <div class="code-line <?php echo $lineNum == $line ? 'line-error' : ''; ?>">
                                <span class="line-number"><?php echo $lineNum; ?></span>
                                <span class="line-code"><?php echo htmlspecialchars($lineCode); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-title">📚 Stack Trace (<?php echo count($trace); ?> frames)</div>
                    <?php foreach ($trace as $index => $item): ?>
                        <div class="trace-item">
                            <span class="trace-number">#<?php echo $index; ?></span>
                            <div class="trace-function">
                                <?php echo htmlspecialchars(($item['class'] ?? '') . ($item['type'] ?? '') . ($item['function'] ?? 'main')); ?>()
                            </div>
                            <?php if (isset($item['file'])): ?>
                                <div class="trace-location">
                                    📁 <?php echo htmlspecialchars($item['file']); ?>
                                    📍 Line <?php echo $item['line'] ?? '?'; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($deepErrors && !empty($item['args'])): ?>
                                <div class="trace-args">
                                    <strong>Arguments:</strong> <?php echo self::formatArgs($item['args']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($deepErrors && $context): ?>
                <div class="card">
                    <div class="card-title">🔍 Context Variables</div>
                    
                    <?php if (!empty($context['globals'])): ?>
                    <h4 style="color: #94a3b8; margin-bottom: 0.5rem;">Globals</h4>
                    <div class="variable-dump">
                        <pre><?php echo htmlspecialchars(print_r($context['globals'], true)); ?></pre>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($context['post'])): ?>
                    <h4 style="color: #94a3b8; margin: 1rem 0 0.5rem;">POST Data</h4>
                    <div class="variable-dump">
                        <pre><?php echo htmlspecialchars(print_r($context['post'], true)); ?></pre>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($context['get'])): ?>
                    <h4 style="color: #94a3b8; margin: 1rem 0 0.5rem;">GET Data</h4>
                    <div class="variable-dump">
                        <pre><?php echo htmlspecialchars(print_r($context['get'], true)); ?></pre>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-title">🌐 Request Information</div>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Method</div>
                            <div class="info-value">
                                <span class="badge badge-info"><?php echo $_SERVER['REQUEST_METHOD'] ?? 'N/A'; ?></span>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">URI</div>
                            <div class="info-value"><?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">IP Address</div>
                            <div class="info-value"><?php echo $_SERVER['REMOTE_ADDR'] ?? 'N/A'; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">User Agent</div>
                            <div class="info-value" style="font-size: 0.8rem;"><?php echo htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Server Software</div>
                            <div class="info-value"><?php echo htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Memory Limit</div>
                            <div class="info-value"><?php echo ini_get('memory_limit'); ?></div>
                        </div>
                        <?php if ($deepErrors && $requestData): ?>
                        <div class="info-item">
                            <div class="info-label">Headers</div>
                            <div class="info-value">
                                <span class="badge badge-success"><?php echo count($requestData['headers']); ?> headers</span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($deepErrors && $requestData): ?>
                <div class="card">
                    <div class="card-title">📬 Request Headers</div>
                    <div class="info-grid">
                        <?php foreach ($requestData['headers'] as $header => $value): ?>
                        <div class="info-item">
                            <div class="info-label"><?php echo htmlspecialchars($header); ?></div>
                            <div class="info-value" style="font-size: 0.85rem;"><?php echo htmlspecialchars($value); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get production error page - clean and simple
     */
    private static function getProductionError()
    {
        // Use the clean error page from views
        $errorFile = VIEW_PATH . '/errors/500.php';
        if (is_file($errorFile)) {
            ob_start();
            include $errorFile;
            $content = ob_get_clean();
            
            // Wrap in layout - but skip debug panel on error pages
            $layoutFile = VIEW_PATH . '/layouts/main.php';
            if (is_file($layoutFile)) {
                ob_start();
                $title = 'Error - VelocityPhp';
                $skipDebugPanel = true; // Don't show debug panel on error pages
                include $layoutFile;
                return ob_get_clean();
            }
            return $content;
        }
        
        // Fallback simple error
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Error - VelocityPhp</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    background: #f5f5f5;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    padding: 2rem;
                }
                .error-container {
                    background: white;
                    padding: 3rem;
                    border-radius: 12px;
                    text-align: center;
                    max-width: 500px;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                }
                .logo {
                    font-size: 3rem;
                    margin-bottom: 1rem;
                }
                h1 {
                    font-size: 2rem;
                    color: #1e293b;
                    margin-bottom: 1rem;
                }
                p {
                    color: #64748b;
                    margin-bottom: 2rem;
                    line-height: 1.6;
                }
                .btn {
                    display: inline-block;
                    padding: 0.75rem 1.5rem;
                    background: #1e293b;
                    color: white;
                    text-decoration: none;
                    border-radius: 6px;
                    font-weight: 600;
                    transition: background 0.2s;
                }
                .btn:hover {
                    background: #334155;
                }
            </style>
        </head>
        <body>
            <div class="error-container">
                <div class="logo">⚡</div>
                <h1>Something went wrong</h1>
                <p>We're sorry, but something unexpected happened. Please try again later.</p>
                <a href="/" class="btn">Go Home</a>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get code snippet around error line
     */
    private static function getCodeSnippet($file, $line, $range = 15)
    {
        if (!file_exists($file)) {
            return null;
        }
        
        $lines = @file($file);
        if (!$lines) {
            return null;
        }
        
        $start = max(0, $line - $range - 1);
        $end = min(count($lines), $line + $range);
        
        $snippet = [];
        for ($i = $start; $i < $end; $i++) {
            $snippet[$i + 1] = $lines[$i];
        }
        
        return $snippet;
    }
    
    /**
     * Get code snippet as string for JS overlay
     */
    private static function getCodeSnippetString($file, $line, $range = 10)
    {
        $snippet = self::getCodeSnippet($file, $line, $range);
        if (!$snippet) {
            return '';
        }
        
        $lines = [];
        foreach ($snippet as $lineNum => $content) {
            $lines[] = rtrim($content);
        }
        
        return implode("\n", $lines);
    }
    
    /**
     * Get context variables
     */
    private static function getContext()
    {
        return [
            'globals' => isset($GLOBALS) ? array_filter($GLOBALS, function($key) {
                return !in_array($key, ['GLOBALS', '_SERVER', '_ENV', '_FILES', '_COOKIE', '_SESSION']);
            }, ARRAY_FILTER_USE_KEY) : [],
            'post' => $_POST ?? [],
            'get' => $_GET ?? [],
            'session' => $_SESSION ?? []
        ];
    }
    
    /**
     * Get memory usage
     */
    private static function getMemoryUsage()
    {
        return [
            'current' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
            'peak' => round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB',
            'limit' => ini_get('memory_limit')
        ];
    }
    
    /**
     * Get database statistics
     */
    private static function getDatabaseStats()
    {
        try {
            if (class_exists('App\Models\BaseModel')) {
                return \App\Models\BaseModel::getStats();
            }
        } catch (\Exception $e) {
            // Ignore
        }
        
        return null;
    }
    
    /**
     * Get request data
     */
    private static function getRequestData()
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace('_', '-', substr($key, 5));
                $headers[$header] = $value;
            }
        }
        
        return [
            'headers' => $headers,
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'uri' => $_SERVER['REQUEST_URI'] ?? '/',
            'protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1'
        ];
    }
    
    /**
     * Format function arguments
     */
    private static function formatArgs($args)
    {
        if (empty($args)) {
            return 'No arguments';
        }
        
        $formatted = [];
        foreach ($args as $arg) {
            if (is_object($arg)) {
                $formatted[] = get_class($arg);
            } elseif (is_array($arg)) {
                $formatted[] = 'Array(' . count($arg) . ')';
            } elseif (is_string($arg)) {
                $formatted[] = '"' . (strlen($arg) > 50 ? substr($arg, 0, 50) . '...' : $arg) . '"';
            } elseif (is_null($arg)) {
                $formatted[] = 'null';
            } elseif (is_bool($arg)) {
                $formatted[] = $arg ? 'true' : 'false';
            } else {
                $formatted[] = (string)$arg;
            }
        }
        
        return implode(', ', $formatted);
    }
    
    /**
     * Format stack trace
     */
    private static function formatTrace($trace)
    {
        $formatted = [];
        
        foreach ($trace as $index => $item) {
            $formatted[] = sprintf(
                "#%d %s%s%s() called at [%s:%s]",
                $index,
                $item['class'] ?? '',
                $item['type'] ?? '',
                $item['function'] ?? 'main',
                $item['file'] ?? 'unknown',
                $item['line'] ?? '?'
            );
        }
        
        return $formatted;
    }
    
    /**
     * Log error to file
     */
    private static function logError(\Throwable $exception)
    {
        $file = $exception->getFile();
        // Sanitize file path for logging (prevent exposing absolute paths on shared hosting)
        $filePath = str_replace(BASE_PATH, '[BASE]', $file);
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $filePath,
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];
        
        self::$errorLog[] = $logEntry;
        
        // Log to file if possible
        if (defined('BASE_PATH')) {
            $logFile = BASE_PATH . '/logs/error.log';
            $logDir = dirname($logFile);
            
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0777, true);
            }
            
            $logLine = sprintf(
                "[%s] %s: %s in %s:%d\n",
                $logEntry['timestamp'],
                $logEntry['type'],
                $logEntry['message'],
                $logEntry['file'],
                $logEntry['line']
            );
            
            @file_put_contents($logFile, $logLine, FILE_APPEND);
        }
    }
    
    /**
     * Get error statistics
     */
    public static function getStats()
    {
        return [
            'error_count' => self::$errorCount,
            'recent_errors' => array_slice(self::$errorLog, -10)
        ];
    }
}

