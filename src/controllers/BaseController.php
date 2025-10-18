<?php
/**
 * Base Controller
 * Provides common controller functionality and helpers
 * 
 * @package NativeMVC
 */

namespace App\Controllers;

abstract class BaseController
{
    protected $data = [];
    
    /**
     * Return JSON response
     */
    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        return json_encode($data);
    }
    
    /**
     * Return success JSON response for AJAX
     */
    protected function jsonSuccess($message = 'Success', $data = [], $redirect = null)
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];
        
        if ($redirect) {
            $response['redirect'] = $redirect;
        }
        
        return $this->json($response);
    }
    
    /**
     * Return error JSON response for AJAX
     */
    protected function jsonError($message = 'Error', $errors = [], $statusCode = 400)
    {
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        return $this->json($response, $statusCode);
    }
    
    /**
     * Return view with data for AJAX requests
     */
    protected function view($viewPath, $data = [], $title = null)
    {
        $viewFile = VIEW_PATH . '/pages/' . $viewPath . '.php';
        
        if (!file_exists($viewFile)) {
            return $this->jsonError('View not found', [], 404);
        }
        
        // Extract data to variables
        extract($data);
        
        // Capture view output
        ob_start();
        include $viewFile;
        $html = ob_get_clean();
        
        return [
            'html' => $html,
            'title' => $title ?: 'Native MVC App',
            'meta' => []
        ];
    }
    
    /**
     * Redirect to another route
     */
    protected function redirect($url, $statusCode = 302)
    {
        header("Location: {$url}", true, $statusCode);
        exit;
    }
    
    /**
     * Validate request data
     */
    protected function validate($data, $rules)
    {
        $errors = [];
        
        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            $value = $data[$field] ?? null;
            
            foreach ($fieldRules as $rule) {
                $error = $this->validateRule($field, $value, $rule, $data);
                if ($error) {
                    if (!isset($errors[$field])) {
                        $errors[$field] = [];
                    }
                    $errors[$field][] = $error;
                }
            }
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * Validate single rule
     */
    private function validateRule($field, $value, $rule, $allData)
    {
        // Parse rule and parameters
        $parts = explode(':', $rule);
        $ruleName = $parts[0];
        $params = isset($parts[1]) ? explode(',', $parts[1]) : [];
        
        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    return ucfirst(str_replace('_', ' ', $field)) . ' is required';
                }
                break;
                
            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return ucfirst(str_replace('_', ' ', $field)) . ' must be a valid email';
                }
                break;
                
            case 'min':
                if (!empty($value) && strlen($value) < $params[0]) {
                    return ucfirst(str_replace('_', ' ', $field)) . " must be at least {$params[0]} characters";
                }
                break;
                
            case 'max':
                if (!empty($value) && strlen($value) > $params[0]) {
                    return ucfirst(str_replace('_', ' ', $field)) . " must not exceed {$params[0]} characters";
                }
                break;
                
            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    return ucfirst(str_replace('_', ' ', $field)) . ' must be a number';
                }
                break;
                
            case 'confirmed':
                $confirmField = $field . '_confirmation';
                if ($value !== ($allData[$confirmField] ?? null)) {
                    return ucfirst(str_replace('_', ' ', $field)) . ' confirmation does not match';
                }
                break;
        }
        
        return null;
    }
    
    /**
     * Get request input
     */
    protected function input($key = null, $default = null)
    {
        if ($key === null) {
            return $_REQUEST;
        }
        
        return $_REQUEST[$key] ?? $default;
    }
    
    /**
     * Get POST data
     */
    protected function post($key = null, $default = null)
    {
        if ($key === null) {
            return $_POST;
        }
        
        return $_POST[$key] ?? $default;
    }
    
    /**
     * Get GET data
     */
    protected function get($key = null, $default = null)
    {
        if ($key === null) {
            return $_GET;
        }
        
        return $_GET[$key] ?? $default;
    }
    
    /**
     * Check if request has file
     */
    protected function hasFile($key)
    {
        return isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK;
    }
    
    /**
     * Get uploaded file
     */
    protected function file($key)
    {
        return $_FILES[$key] ?? null;
    }
    
    /**
     * Sanitize input
     */
    protected function sanitize($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        
        return htmlspecialchars(strip_tags($data), ENT_QUOTES, 'UTF-8');
    }
}
