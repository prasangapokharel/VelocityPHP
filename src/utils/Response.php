<?php
/**
 * VelocityPhp Response Helper
 * Provides convenient methods for sending HTTP responses
 * 
 * @package VelocityPhp
 * @version 1.0.0
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
    
    /**
     * Send XML response
     */
    public static function xml($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/xml; charset=utf-8');
        
        $xml = new \SimpleXMLElement('<response/>');
        self::arrayToXml($data, $xml);
        echo $xml->asXML();
        exit;
    }
    
    /**
     * Convert array to XML
     */
    private static function arrayToXml($data, &$xml)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $key = 'item' . $key;
                }
                $subnode = $xml->addChild($key);
                self::arrayToXml($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
    }
    
    /**
     * Set response header
     */
    public static function header($name, $value, $replace = true)
    {
        header("{$name}: {$value}", $replace);
        return new self();
    }
    
    /**
     * Set multiple headers
     */
    public static function headers(array $headers)
    {
        foreach ($headers as $name => $value) {
            self::header($name, $value);
        }
        return new self();
    }
    
    /**
     * Set status code
     */
    public static function status($code)
    {
        http_response_code($code);
        return new self();
    }
    
    /**
     * Send download response
     */
    public static function download($file, $name = null, array $headers = [])
    {
        if (!file_exists($file)) {
            self::notFound('File not found');
        }
        
        $name = $name ?? basename($file);
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        
        foreach ($headers as $header => $value) {
            header("{$header}: {$value}");
        }
        
        readfile($file);
        exit;
    }
    
    /**
     * Send file response
     */
    public static function file($file, array $headers = [])
    {
        if (!file_exists($file)) {
            self::notFound('File not found');
        }
        
        $mimeType = mime_content_type($file);
        
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($file));
        
        foreach ($headers as $header => $value) {
            header("{$header}: {$value}");
        }
        
        readfile($file);
        exit;
    }
    
    /**
     * Send view response
     */
    public static function view($view, $data = [], $statusCode = 200)
    {
        http_response_code($statusCode);
        extract($data);
        
        $viewPath = VIEW_PATH . '/pages/' . $view . '/index.php';
        if (file_exists($viewPath)) {
            ob_start();
            include $viewPath;
            $content = ob_get_clean();
            
            $layoutPath = VIEW_PATH . '/layouts/main.php';
            if (file_exists($layoutPath)) {
                include $layoutPath;
            } else {
                echo $content;
            }
        } else {
            self::notFound('View not found');
        }
        exit;
    }
}
