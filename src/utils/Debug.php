<?php
/**
 * Debug Helper Class
 * Beautiful error display for development
 * 
 * @package NativeMVC
 */

namespace App\Utils;

class Debug
{
    /**
     * Display beautiful error page
     */
    public static function showError($exception, $isAjax = false)
    {
        $config = require CONFIG_PATH . '/app.php';
        
        if ($isAjax) {
            return self::getAjaxError($exception, $config['debug']);
        }
        
        return self::getHtmlError($exception, $config['debug']);
    }
    
    /**
     * Get AJAX error response
     */
    private static function getAjaxError($exception, $debug)
    {
        $error = [
            'success' => false,
            'error' => $debug ? $exception->getMessage() : 'Internal Server Error',
            'type' => get_class($exception)
        ];
        
        if ($debug) {
            $error['file'] = $exception->getFile();
            $error['line'] = $exception->getLine();
            $error['trace'] = self::formatTrace($exception->getTrace());
        }
        
        return json_encode($error, JSON_PRETTY_PRINT);
    }
    
    /**
     * Get beautiful HTML error page
     */
    private static function getHtmlError($exception, $debug)
    {
        if (!$debug) {
            return self::getProductionError();
        }
        
        $file = $exception->getFile();
        $line = $exception->getLine();
        $message = $exception->getMessage();
        $type = get_class($exception);
        $trace = $exception->getTrace();
        
        // Get code snippet
        $codeSnippet = self::getCodeSnippet($file, $line);
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Error - <?php echo htmlspecialchars($type); ?></title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    background: #0f172a;
                    color: #e2e8f0;
                    padding: 2rem;
                }
                .container { max-width: 1400px; margin: 0 auto; }
                .error-header {
                    background: linear-gradient(135deg, #dc2626, #991b1b);
                    padding: 2rem;
                    border-radius: 12px;
                    margin-bottom: 2rem;
                    box-shadow: 0 10px 30px rgba(220, 38, 38, 0.3);
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
                    width: 50px;
                    text-align: right;
                    padding-right: 1rem;
                    user-select: none;
                }
                .line-code {
                    flex: 1;
                    color: #cbd5e1;
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
                }
                .trace-item {
                    background: #0f172a;
                    border-radius: 8px;
                    padding: 1rem;
                    margin-bottom: 0.5rem;
                    border-left: 4px solid #475569;
                }
                .trace-item:hover {
                    border-left-color: #60a5fa;
                    background: #1a2332;
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
                .request-info {
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
            </style>
        </head>
        <body>
            <div class="container">
                <div class="error-header">
                    <div class="error-type"><?php echo htmlspecialchars($type); ?></div>
                    <div class="error-message"><?php echo htmlspecialchars($message); ?></div>
                    <div class="error-location">
                        <span>📁 <?php echo htmlspecialchars($file); ?></span>
                        <span>📍 Line <?php echo $line; ?></span>
                    </div>
                </div>
                
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
                    <div class="card-title">📚 Stack Trace</div>
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
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="card">
                    <div class="card-title">🌐 Request Information</div>
                    <div class="request-info">
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
                            <div class="info-label">Time</div>
                            <div class="info-value"><?php echo date('Y-m-d H:i:s'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">PHP Version</div>
                            <div class="info-value"><?php echo phpversion(); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Memory Usage</div>
                            <div class="info-value"><?php echo round(memory_get_usage() / 1024 / 1024, 2); ?> MB</div>
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
     * Get production error page
     */
    private static function getProductionError()
    {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Error</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    background: #f8fafc;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    padding: 2rem;
                }
                .error-container {
                    text-align: center;
                    max-width: 500px;
                }
                .error-icon {
                    font-size: 5rem;
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
                    padding: 0.75rem 2rem;
                    background: #3b82f6;
                    color: white;
                    text-decoration: none;
                    border-radius: 8px;
                    font-weight: 500;
                }
                .btn:hover {
                    background: #2563eb;
                }
            </style>
        </head>
        <body>
            <div class="error-container">
                <div class="error-icon">⚠️</div>
                <h1>Oops! Something went wrong</h1>
                <p>We're sorry, but something unexpected happened. Our team has been notified and we're working on it.</p>
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
    private static function getCodeSnippet($file, $line, $range = 10)
    {
        if (!file_exists($file)) {
            return null;
        }
        
        $lines = file($file);
        $start = max(0, $line - $range - 1);
        $end = min(count($lines), $line + $range);
        
        $snippet = [];
        for ($i = $start; $i < $end; $i++) {
            $snippet[$i + 1] = $lines[$i];
        }
        
        return $snippet;
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
}
