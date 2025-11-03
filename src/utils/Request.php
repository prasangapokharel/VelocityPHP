<?php
/**
 * VelocityPhp Request Helper
 * Provides convenient methods for handling HTTP requests
 * 
 * @package VelocityPhp
 * @version 1.0.0
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
    
    /**
     * Get XML input
     */
    public static function xml()
    {
        $input = file_get_contents('php://input');
        $xml = simplexml_load_string($input);
        return $xml ? json_decode(json_encode($xml), true) : null;
    }
    
    /**
     * Get request headers
     */
    public static function headers($key = null)
    {
        $headers = [];
        
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) === 'HTTP_') {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($name, 5)))));
                $headers[$header] = $value;
            }
        }
        
        if ($key !== null) {
            return $headers[$key] ?? null;
        }
        
        return $headers;
    }
    
    /**
     * Get request header
     */
    public static function header($key)
    {
        return self::headers($key);
    }
    
    /**
     * Get IP address
     */
    public static function ip()
    {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Get user agent
     */
    public static function userAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    
    /**
     * Validate request data
     */
    public static function validate(array $rules, array $messages = [])
    {
        $validator = \App\Utils\Validator::make(self::all(), $rules, $messages);
        
        if ($validator->fails()) {
            if (self::isAjax()) {
                \App\Utils\Response::error('Validation failed', $validator->errors(), 422);
            }
            return $validator;
        }
        
        return $validator;
    }
    
    /**
     * Get validated input
     */
    public static function validated()
    {
        return self::all();
    }
    
    /**
     * Get only specified keys
     */
    public static function only($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        $data = self::all();
        return array_intersect_key($data, array_flip($keys));
    }
    
    /**
     * Get all except specified keys
     */
    public static function except($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        $data = self::all();
        return array_diff_key($data, array_flip($keys));
    }
    
    /**
     * Check if request has key
     */
    public static function has($key)
    {
        return isset($_REQUEST[$key]);
    }
    
    /**
     * Check if request has any of the keys
     */
    public static function hasAny($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        foreach ($keys as $key) {
            if (self::has($key)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if request has all keys
     */
    public static function hasAll($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        foreach ($keys as $key) {
            if (!self::has($key)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Get request path
     */
    public static function path()
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return trim($uri, '/');
    }
    
    /**
     * Get request URL
     */
    public static function url()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
    
    /**
     * Get full URL
     */
    public static function fullUrl()
    {
        return self::url();
    }
    
    /**
     * Check if request is secure
     */
    public static function secure()
    {
        return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    }
    
    /**
     * Handle file upload
     */
    public static function handleFileUpload($key, $destination, $allowedTypes = [], $maxSize = 5242880)
    {
        if (!self::hasFile($key)) {
            return ['success' => false, 'error' => 'No file uploaded'];
        }
        
        $file = self::file($key);
        
        // Check file size
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'File size exceeds maximum allowed'];
        }
        
        // Check file type
        if (!empty($allowedTypes)) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedTypes)) {
                return ['success' => false, 'error' => 'File type not allowed'];
            }
        }
        
        // Generate unique filename
        $filename = uniqid() . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $path = rtrim($destination, '/') . '/' . $filename;
        
        // Create destination directory if it doesn't exist (shared hosting compatible)
        if (!is_dir($destination)) {
            @mkdir($destination, 0777, true);
        }
        
        // Verify directory is writable
        if (!is_writable($destination)) {
            throw new \Exception('Upload directory is not writable');
        }
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $path)) {
            return [
                'success' => true,
                'path' => $path,
                'filename' => $filename,
                'original_name' => $file['name'],
                'size' => $file['size'],
                'type' => $file['type']
            ];
        }
        
        return ['success' => false, 'error' => 'Failed to move uploaded file'];
    }
}
