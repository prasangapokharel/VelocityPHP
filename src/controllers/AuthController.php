<?php
/**
 * Authentication Controller
 * Handles login, register, logout
 * 
 * @package NativeMVC
 */

namespace App\Controllers;

use App\Services\AuthService;
use App\Utils\Logger;

class AuthController extends BaseController
{
    private $authService;
    
    public function __construct()
    {
        $this->authService = new AuthService();
    }
    
    /**
     * Handle login request
     */
    public function login($params, $isAjax)
    {
        // Validate input
        $validation = $this->validate($this->post(), [
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);
        
        if ($validation !== true) {
            Logger::warning('Login validation failed', [
                'errors' => $validation,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            return $this->jsonError('Validation failed', $validation, 422);
        }
        
        // Attempt login
        $email = $this->post('email');
        $password = $this->post('password');
        $remember = $this->post('remember', false);
        
        $result = $this->authService->login($email, $password);
        
        if ($result['success']) {
            // Set remember me cookie if requested
            if ($remember) {
                setcookie('remember_token', bin2hex(random_bytes(32)), [
                    'expires' => time() + (30 * 24 * 60 * 60), // 30 days
                    'path' => '/',
                    'secure' => isset($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            }
            
            Logger::info('User login successful', [
                'email' => $email,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_id' => $result['user']['id'] ?? null
            ]);
            
            return $this->jsonSuccess(
                $result['message'],
                ['user' => $result['user']],
                '/dashboard'
            );
        } else {
            Logger::warning('Login failed', [
                'email' => $email,
                'reason' => $result['message'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            return $this->jsonError($result['message'], [], 401);
        }
    }
    
    /**
     * Handle register request
     */
    public function register($params, $isAjax)
    {
        // Validate input
        $validation = $this->validate($this->post(), [
            'name' => 'required|min:3|max:100',
            'email' => 'required|email',
            'password' => 'required|min:6',
            'password_confirmation' => 'required|confirmed'
        ]);
        
        if ($validation !== true) {
            Logger::warning('Registration validation failed', [
                'errors' => $validation
            ]);
            
            return $this->jsonError('Validation failed', $validation, 422);
        }
        
        // Attempt registration
        $data = [
            'name' => $this->sanitize($this->post('name')),
            'email' => $this->sanitize($this->post('email')),
            'password' => $this->post('password')
        ];
        
        $result = $this->authService->register($data);
        
        if ($result['success']) {
            Logger::info('User registration successful', [
                'email' => $data['email'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            return $this->jsonSuccess(
                $result['message'],
                ['user' => $result['user']],
                '/dashboard'
            );
        } else {
            Logger::warning('Registration failed', [
                'email' => $data['email'],
                'reason' => $result['message']
            ]);
            
            return $this->jsonError($result['message'], [], 400);
        }
    }
    
    /**
     * Handle logout request
     */
    public function logout($params, $isAjax)
    {
        $userId = $_SESSION['user_id'] ?? null;
        
        $result = $this->authService->logout();
        
        // Clear remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        Logger::info('User logout', [
            'user_id' => $userId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        return $this->jsonSuccess($result['message'], [], '/login');
    }
    
    /**
     * Check if user is authenticated
     */
    public function check($params, $isAjax)
    {
        $isAuthenticated = $this->authService->check();
        $user = $this->authService->user();
        
        return $this->json([
            'authenticated' => $isAuthenticated,
            'user' => $user
        ]);
    }
}
