<?php
/**
 * Request Helper Class
 * Provides convenient methods for handling HTTP requests
 * 
 * @package NativeMVC
 */

namespace App\Utils;

class Request
{
    /**
     * Get all request data
     */
    public static function all()
    {
        return $_REQUEST;
    }
    
    /**
     * Get specific input value
     */
    public static function input($key, $default = null)
    {
        return $_REQUEST[$key] ?? $default;
    }
    
    /**
     * Get POST data
     */
    public static function post($key = null, $default = null)
    {
        if ($key === null) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }
    
    /**
     * Get GET data
     */
    public static function get($key = null, $default = null)
    {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }
    
    /**
     * Check if request is AJAX
     */
    public static function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Check if request is POST
     */
    public static function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    /**
     * Check if request is GET
     */
    public static function isGet()
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }
    
    /**
     * Get request method
     */
    public static function method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }
    
    /**
     * Get request URI
     */
    public static function uri()
    {
        return $_SERVER['REQUEST_URI'];
    }
    
    /**
     * Check if request has file
     */
    public static function hasFile($key)
    {
        return isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK;
    }
    
    /**
     * Get uploaded file
     */
    public static function file($key)
    {
        return $_FILES[$key] ?? null;
    }
    
    /**
     * Get JSON input
     */
    public static function json()
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true);
    }
}
