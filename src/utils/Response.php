<?php
/**
 * Response Helper Class
 * Provides convenient methods for sending HTTP responses
 * 
 * @package NativeMVC
 */

namespace App\Utils;

class Response
{
    /**
     * Send JSON response
     */
    public static function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Send success response
     */
    public static function success($message = 'Success', $data = [], $statusCode = 200)
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }
    
    /**
     * Send error response
     */
    public static function error($message = 'Error', $errors = [], $statusCode = 400)
    {
        self::json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $statusCode);
    }
    
    /**
     * Redirect to URL
     */
    public static function redirect($url, $statusCode = 302)
    {
        header("Location: {$url}", true, $statusCode);
        exit;
    }
    
    /**
     * Send 404 response
     */
    public static function notFound($message = 'Not Found')
    {
        self::error($message, [], 404);
    }
    
    /**
     * Send 403 response
     */
    public static function forbidden($message = 'Forbidden')
    {
        self::error($message, [], 403);
    }
    
    /**
     * Send 401 response
     */
    public static function unauthorized($message = 'Unauthorized')
    {
        self::error($message, [], 401);
    }
    
    /**
     * Send 500 response
     */
    public static function serverError($message = 'Internal Server Error')
    {
        self::error($message, [], 500);
    }
}
