<?php
/**
 * Authentication Middleware
 * Protect routes that require authentication
 * 
 * @package NativeMVC
 */

namespace App\Middleware;

use App\Services\AuthService;

class AuthMiddleware
{
    /**
     * Check if user is authenticated
     */
    public static function check()
    {
        $auth = new AuthService();
        
        if (!$auth->check()) {
            // For AJAX requests
            if (self::isAjax()) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'redirect' => '/login'
                ]);
                exit;
            }
            
            // For regular requests
            header('Location: /login');
            exit;
        }
        
        return true;
    }
    
    /**
     * Check if user is admin
     */
    public static function isAdmin()
    {
        $auth = new AuthService();
        
        if (!$auth->isAdmin()) {
            // For AJAX requests
            if (self::isAjax()) {
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
    
    /**
     * Check if request is AJAX
     */
    private static function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
