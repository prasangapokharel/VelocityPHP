<?php
/**
 * VelocityPhp Debug Helper Class
 * Beautiful error display with dev/production modes
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
     * Display error page based on environment
     */
    public static function showError(\Throwable $exception, $isAjax = false)
    {
        self::$errorCount++;
        self::logError($exception);
        
        // Check if debug mode is enabled
        $debug = self::isDebugMode();
        
        if ($isAjax) {
            return self::getAjaxError($exception, $debug);
        }
        
        return $debug ? self::getDevError($exception) : self::getProductionError();
    }
    
    /**
     * Check if debug mode is enabled
     */
    private static function isDebugMode()
    {
        // Check environment variable
        if (getenv('APP_DEBUG') === 'true' || getenv('APP_DEBUG') === '1') {
            return true;
        }
        
        // Check config
        if (defined('CONFIG_PATH') && file_exists(CONFIG_PATH . '/app.php')) {
            $config = require CONFIG_PATH . '/app.php';
            return $config['debug'] ?? false;
        }
        
        return false;
    }
    
    /**
     * Get AJAX error response
     */
    private static function getAjaxError(\Throwable $exception, $debug)
    {
        $response = [
            'success' => false,
            'error' => $debug ? $exception->getMessage() : 'An error occurred. Please try again later.',
            'message' => 'Something went wrong'
        ];
        
        if ($debug) {
            $response['debug'] = [
                'type' => get_class($exception),
                'file' => basename($exception->getFile()),
                'line' => $exception->getLine(),
                'trace' => self::getCleanTrace($exception)
            ];
        }
        
        return json_encode($response, JSON_PRETTY_PRINT);
    }
    
    /**
     * Get clean stack trace (simplified)
     */
    private static function getCleanTrace(\Throwable $exception)
    {
        $trace = [];
        foreach ($exception->getTrace() as $i => $item) {
            if ($i >= 10) break; // Limit to 10 frames
            $trace[] = [
                'file' => isset($item['file']) ? basename($item['file']) : 'unknown',
                'line' => $item['line'] ?? '?',
                'function' => ($item['class'] ?? '') . ($item['type'] ?? '') . ($item['function'] ?? 'main')
            ];
        }
        return $trace;
    }
    
    /**
     * Get beautiful development error page
     */
    private static function getDevError(\Throwable $exception)
    {
        $file = $exception->getFile();
        $line = $exception->getLine();
        $message = $exception->getMessage();
        $type = get_class($exception);
        $trace = $exception->getTrace();
        $codeSnippet = self::getCodeSnippet($file, $line, 10);
        
        ob_start();
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error: <?php echo htmlspecialchars($type); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            padding: 2rem;
            line-height: 1.6;
        }
        .container { max-width: 1200px; margin: 0 auto; }
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
        .error-type {
            font-size: 0.9rem;
            color: #fca5a5;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
        }
        .error-message {
            font-size: 1.4rem;
            font-weight: 600;
            color: white;
            margin-bottom: 1rem;
            word-break: break-word;
        }
        .error-location {
            font-size: 0.9rem;
            color: #fca5a5;
        }
        .error-location span { margin-right: 1.5rem; }
        .card {
            background: #1e293b;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #60a5fa;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #334155;
        }
        .code-block {
            background: #0f172a;
            border-radius: 8px;
            padding: 1rem;
            overflow-x: auto;
            font-family: 'Fira Code', 'Consolas', monospace;
            font-size: 0.85rem;
        }
        .code-line {
            display: flex;
            padding: 0.2rem 0;
        }
        .line-number {
            color: #64748b;
            width: 50px;
            text-align: right;
            padding-right: 1rem;
            user-select: none;
            flex-shrink: 0;
        }
        .line-code {
            color: #cbd5e1;
            white-space: pre;
        }
        .line-error {
            background: rgba(220, 38, 38, 0.2);
            border-left: 3px solid #dc2626;
            margin: 0 -1rem;
            padding: 0 1rem;
        }
        .line-error .line-number { color: #f87171; font-weight: bold; }
        .line-error .line-code { color: #fef2f2; }
        .trace-item {
            background: #0f172a;
            border-radius: 6px;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            border-left: 3px solid #475569;
        }
        .trace-item:hover { border-left-color: #60a5fa; }
        .trace-number {
            display: inline-block;
            background: #475569;
            color: white;
            padding: 0.15rem 0.5rem;
            border-radius: 3px;
            font-size: 0.75rem;
            margin-right: 0.5rem;
        }
        .trace-function { color: #60a5fa; font-weight: 500; }
        .trace-location { color: #94a3b8; font-size: 0.85rem; margin-top: 0.25rem; }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        .info-item {
            background: #0f172a;
            padding: 1rem;
            border-radius: 6px;
        }
        .info-label { color: #64748b; font-size: 0.8rem; margin-bottom: 0.25rem; }
        .info-value { color: #e2e8f0; font-weight: 500; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">⚡ VelocityPhp Error</div>
            <div class="error-type"><?php echo htmlspecialchars($type); ?></div>
            <div class="error-message"><?php echo htmlspecialchars($message); ?></div>
            <div class="error-location">
                <span>📁 <?php echo htmlspecialchars(basename($file)); ?></span>
                <span>📍 Line <?php echo $line; ?></span>
                <span>🕐 <?php echo date('H:i:s'); ?></span>
            </div>
        </div>
        
        <?php if ($codeSnippet): ?>
        <div class="card">
            <div class="card-title">📝 Source Code</div>
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
            <div class="card-title">📚 Stack Trace</div>
            <?php foreach (array_slice($trace, 0, 15) as $i => $item): ?>
            <div class="trace-item">
                <span class="trace-number">#<?php echo $i; ?></span>
                <span class="trace-function"><?php echo htmlspecialchars(($item['class'] ?? '') . ($item['type'] ?? '') . ($item['function'] ?? 'main')); ?>()</span>
                <?php if (isset($item['file'])): ?>
                <div class="trace-location">
                    <?php echo htmlspecialchars(basename($item['file'])); ?>:<?php echo $item['line'] ?? '?'; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="card">
            <div class="card-title">🌐 Request Info</div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Method</div>
                    <div class="info-value"><?php echo $_SERVER['REQUEST_METHOD'] ?? 'N/A'; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">URI</div>
                    <div class="info-value"><?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">PHP Version</div>
                    <div class="info-value"><?php echo phpversion(); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Memory</div>
                    <div class="info-value"><?php echo round(memory_get_peak_usage() / 1024 / 1024, 2); ?> MB</div>
                </div>
            </div>
        </div>
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
        $errorFile = VIEW_PATH . '/errors/500.php';
        if (is_file($errorFile)) {
            ob_start();
            include $errorFile;
            $content = ob_get_clean();
            
            $layoutFile = VIEW_PATH . '/layouts/main.php';
            if (is_file($layoutFile)) {
                ob_start();
                $title = 'Error - VelocityPhp';
                $skipDebugPanel = true;
                include $layoutFile;
                return ob_get_clean();
            }
            return $content;
        }
        
        // Fallback simple error
        return <<<HTML
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
        h1 { font-size: 2rem; color: #1e293b; margin-bottom: 1rem; }
        p { color: #64748b; margin-bottom: 2rem; }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #1e293b;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>Something went wrong</h1>
        <p>We're sorry, but something unexpected happened. Please try again later.</p>
        <a href="/" class="btn">Go Home</a>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Get code snippet around error line
     */
    private static function getCodeSnippet($file, $line, $range = 10)
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
            $snippet[$i + 1] = rtrim($lines[$i]);
        }
        
        return $snippet;
    }
    
    /**
     * Log error to file
     */
    private static function logError(\Throwable $exception)
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ];
        
        self::$errorLog[] = $logEntry;
        
        if (defined('BASE_PATH')) {
            $logFile = BASE_PATH . '/logs/error.log';
            $logDir = dirname($logFile);
            
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            
            $logLine = sprintf(
                "[%s] %s: %s in %s:%d\n",
                $logEntry['timestamp'],
                $logEntry['type'],
                $logEntry['message'],
                basename($logEntry['file']),
                $logEntry['line']
            );
            
            @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
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

