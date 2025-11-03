<?php
/**
 * Authentication Middleware
 * Protect routes that require authentication
 * 
 * @package VelocityPhp
 */

namespace App\Middleware;

use App\Services\AuthService;

class AuthMiddleware implements MiddlewareInterface
{
    /**
     * Handle middleware
     */
    public function handle($request, $next)
    {
        $auth = new AuthService();
        
        if (!$auth->check()) {
            // For AJAX requests
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'redirect' => '/login',
                    'html' => '<div class="container text-center py-2xl"><h1 class="text-4xl font-bold mb-md">401</h1><p class="text-lg mb-xl text-neutral-600">Unauthorized. Please login.</p><a href="/login" class="btn btn-primary btn-md">Go to Login</a></div>',
                    'title' => '401 - Unauthorized'
                ]);
                exit;
            }
            
            // For regular requests
            header('Location: /login');
            exit;
        }
        
        return $next($request);
    }
    
    /**
     * Check if user is authenticated (static method for backward compatibility)
     */
    public static function check()
    {
        $auth = new AuthService();
        return $auth->check();
    }
    
    /**
     * Check if user is admin
     */
    public static function isAdmin()
    {
        $auth = new AuthService();
        
        if (!$auth->isAdmin()) {
            // For AJAX requests
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Forbidden - Admin access required'
                ]);
                exit;
            }
            
            // For regular requests
            header('HTTP/1.1 403 Forbidden');
            echo 'Access denied';
            exit;
        }
        
        return true;
    }
}
