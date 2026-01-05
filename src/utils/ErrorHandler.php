<?php
/**
 * ErrorHandler - Centralized Error Handling
 * 
 * Provides consistent error responses for both web and API requests.
 * Supports automatic redirection to error pages.
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

namespace App\Utils;

use App\Config\Config;

class ErrorHandler
{
    /**
     * Error code configurations
     */
    private static array $errors = [
        400 => [
            'title' => 'Bad Request',
            'message' => 'The request could not be understood by the server.',
        ],
        401 => [
            'title' => 'Unauthorized',
            'message' => 'Authentication is required to access this resource.',
        ],
        403 => [
            'title' => 'Forbidden',
            'message' => 'You don\'t have permission to access this resource.',
        ],
        404 => [
            'title' => 'Not Found',
            'message' => 'The requested resource could not be found.',
        ],
        405 => [
            'title' => 'Method Not Allowed',
            'message' => 'The request method is not supported for this resource.',
        ],
        422 => [
            'title' => 'Unprocessable Entity',
            'message' => 'The request was well-formed but contains invalid data.',
        ],
        429 => [
            'title' => 'Too Many Requests',
            'message' => 'You have exceeded the rate limit. Please try again later.',
        ],
        500 => [
            'title' => 'Internal Server Error',
            'message' => 'An unexpected error occurred on the server.',
        ],
        502 => [
            'title' => 'Bad Gateway',
            'message' => 'The server received an invalid response from an upstream server.',
        ],
        503 => [
            'title' => 'Service Unavailable',
            'message' => 'The service is temporarily unavailable.',
        ],
    ];

    /**
     * Handle an error and respond appropriately
     * 
     * @param int $code HTTP status code
     * @param string|null $message Custom message (optional)
     * @param array $data Additional data (for API responses)
     * @param bool $redirect Whether to redirect to error page (for web requests)
     * @return void
     */
    public static function handle(int $code, ?string $message = null, array $data = [], bool $redirect = true): void
    {
        $isAjax = self::isAjaxRequest();
        $isApi = self::isApiRequest();

        // Set HTTP response code
        http_response_code($code);

        // Get error info
        $errorInfo = self::$errors[$code] ?? [
            'title' => 'Error',
            'message' => 'An error occurred.',
        ];

        $message = $message ?? $errorInfo['message'];

        // API request - return JSON
        if ($isApi) {
            self::jsonResponse($code, $message, $data);
            return;
        }

        // AJAX request - return JSON with HTML
        if ($isAjax) {
            self::ajaxResponse($code, $message, $data);
            return;
        }

        // Web request - render error page
        self::renderErrorPage($code, $message, $data);
    }

    /**
     * Abort with an error (alias for handle with exit)
     */
    public static function abort(int $code, ?string $message = null, array $data = []): void
    {
        self::handle($code, $message, $data);
        exit;
    }

    /**
     * Handle 401 Unauthorized
     */
    public static function unauthorized(?string $message = null): void
    {
        self::abort(401, $message);
    }

    /**
     * Handle 403 Forbidden
     */
    public static function forbidden(?string $message = null): void
    {
        self::abort(403, $message);
    }

    /**
     * Handle 404 Not Found
     */
    public static function notFound(?string $message = null): void
    {
        self::abort(404, $message);
    }

    /**
     * Handle 429 Too Many Requests
     */
    public static function tooManyRequests(int $retryAfter = 60, ?string $message = null): void
    {
        header("Retry-After: {$retryAfter}");
        self::abort(429, $message, ['retry_after' => $retryAfter]);
    }

    /**
     * Handle 500 Internal Server Error
     */
    public static function serverError(?string $message = null): void
    {
        self::abort(500, $message);
    }

    /**
     * Handle 503 Service Unavailable
     */
    public static function serviceUnavailable(?string $message = null): void
    {
        self::abort(503, $message);
    }

    /**
     * Check if request is AJAX
     */
    private static function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Check if request is API
     */
    private static function isApiRequest(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return strpos($uri, '/api/') === 0 || strpos($uri, '/api/') !== false;
    }

    /**
     * Send JSON response for API
     */
    private static function jsonResponse(int $code, string $message, array $data = []): void
    {
        header('Content-Type: application/json');
        
        $response = [
            'success' => false,
            'message' => $message,
            'code' => $code,
        ];

        if (!empty($data)) {
            $response['data'] = $data;
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send AJAX response with HTML content
     */
    private static function ajaxResponse(int $code, string $message, array $data = []): void
    {
        header('Content-Type: application/json');
        
        $html = self::getErrorPageContent($code, $data);
        $errorInfo = self::$errors[$code] ?? ['title' => 'Error'];
        
        $response = [
            'success' => false,
            'html' => $html,
            'title' => "{$code} - {$errorInfo['title']} - VelocityPhp",
            'message' => $message,
            'code' => $code,
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Render error page for web requests
     */
    private static function renderErrorPage(int $code, string $message, array $data = []): void
    {
        $content = self::getErrorPageContent($code, $data);
        $errorInfo = self::$errors[$code] ?? ['title' => 'Error'];
        $title = "{$code} - {$errorInfo['title']}";

        // Include layout
        $layoutFile = defined('VIEW_PATH') ? VIEW_PATH . '/layouts/main.php' : null;
        
        if ($layoutFile && is_file($layoutFile)) {
            ob_start();
            include $layoutFile;
            echo ob_get_clean();
        } else {
            // Fallback: render basic HTML
            echo "<!DOCTYPE html><html><head><title>{$title}</title></head><body>{$content}</body></html>";
        }
        
        exit;
    }

    /**
     * Get error page content from view file
     */
    private static function getErrorPageContent(int $code, array $data = []): string
    {
        $viewPath = defined('VIEW_PATH') ? VIEW_PATH : (defined('BASE_PATH') ? BASE_PATH . '/src/views' : null);
        
        if (!$viewPath) {
            return self::getFallbackContent($code);
        }

        $errorFile = $viewPath . '/errors/' . $code . '.php';
        
        if (!is_file($errorFile)) {
            // Try generic error file
            $errorFile = $viewPath . '/errors/500.php';
        }

        if (!is_file($errorFile)) {
            return self::getFallbackContent($code);
        }

        // Extract data variables
        extract($data, EXTR_SKIP);
        
        ob_start();
        include $errorFile;
        return ob_get_clean();
    }

    /**
     * Get fallback HTML content when no view file exists
     */
    private static function getFallbackContent(int $code): string
    {
        $errorInfo = self::$errors[$code] ?? ['title' => 'Error', 'message' => 'An error occurred.'];
        
        return <<<HTML
        <div style="text-align: center; padding: 50px; font-family: system-ui, sans-serif;">
            <h1 style="font-size: 72px; margin: 0; color: #ef4444;">{$code}</h1>
            <h2 style="margin: 20px 0; color: #1f2937;">{$errorInfo['title']}</h2>
            <p style="color: #6b7280;">{$errorInfo['message']}</p>
            <a href="/" style="display: inline-block; margin-top: 20px; padding: 10px 20px; background: #1f2937; color: white; text-decoration: none; border-radius: 5px;">Go Home</a>
        </div>
        HTML;
    }

    /**
     * Register global error and exception handlers
     */
    public static function register(): void
    {
        // Set error handler
        set_error_handler(function($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            
            Logger::error('PHP Error', [
                'severity' => $severity,
                'message' => $message,
                'file' => $file,
                'line' => $line,
            ]);
            
            // Only show error page for fatal errors in production
            $isDebug = Config::env('APP_DEBUG', false);
            if (!$isDebug && in_array($severity, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                self::serverError();
            }
            
            return false;
        });

        // Set exception handler
        set_exception_handler(function(\Throwable $e) {
            Logger::error('Uncaught Exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $isDebug = Config::env('APP_DEBUG', false);
            
            if ($isDebug) {
                // Show detailed error in debug mode
                echo "<pre style='background:#1f2937;color:#f87171;padding:20px;margin:20px;border-radius:8px;'>";
                echo "<strong>Exception:</strong> " . htmlspecialchars($e->getMessage()) . "\n";
                echo "<strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "\n\n";
                echo htmlspecialchars($e->getTraceAsString());
                echo "</pre>";
            } else {
                self::serverError();
            }
        });

        // Register shutdown function for fatal errors
        register_shutdown_function(function() {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                Logger::error('Fatal Error', $error);
                
                $isDebug = Config::env('APP_DEBUG', false);
                if (!$isDebug) {
                    self::serverError();
                }
            }
        });
    }
}
