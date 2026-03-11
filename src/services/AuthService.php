<?php
/**
 * Authentication Service
 * Handles user authentication and session management
 * 
 * @package VelocityPhp
 */

namespace App\Services;

use App\Models\UserModel;

class AuthService
{
    private $userModel;
    
    public function __construct()
    {
        $this->userModel = new UserModel();
    }
    
    /**
     * Authenticate user with email and password
     */
    public function login($email, $password)
    {
        // Find user by email
        $user = $this->userModel->findByEmail($email);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid credentials'
            ];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Invalid credentials'
            ];
        }
        
        // Check if user is active
        if ($user['status'] !== 'active') {
            return [
                'success' => false,
                'message' => 'Account is not active'
            ];
        }
        
        // Create session
        $this->createSession($user);
        
        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => $this->sanitizeUser($user)
        ];
    }
    
    /**
     * Register new user
     */
    public function register($data)
    {
        // Check if email already exists
        $existingUser = $this->userModel->findByEmail($data['email']);
        
        if ($existingUser) {
            return [
                'success' => false,
                'message' => 'Email already registered'
            ];
        }
        
        // Create user
        try {
            $userId = $this->userModel->createUser([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'user',
                'status' => 'active'
            ]);
            
            // Get created user
            $user = $this->userModel->find($userId);
            
            // Create session
            $this->createSession($user);
            
            return [
                'success' => true,
                'message' => 'Registration successful',
                'user' => $this->sanitizeUser($user)
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Registration failed'
            ];
        }
    }
    
    /**
     * Logout user
     */
    public function logout()
    {
        // Destroy session
        session_unset();
        session_destroy();
        
        return [
            'success' => true,
            'message' => 'Logged out successfully'
        ];
    }
    
    /**
     * Check if user is authenticated
     */
    public function check()
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Get authenticated user
     */
    public function user()
    {
        if (!$this->check()) {
            return null;
        }
        
        if (!isset($_SESSION['user_data'])) {
            // Fetch fresh user data
            $user = $this->userModel->find($_SESSION['user_id']);
            $_SESSION['user_data'] = $this->sanitizeUser($user);
        }
        
        return $_SESSION['user_data'];
    }
    
    /**
     * Update user password
     */
    public function updatePassword($userId, $currentPassword, $newPassword)
    {
        // Verify current password
        if (!$this->userModel->verifyPassword($userId, $currentPassword)) {
            return [
                'success' => false,
                'message' => 'Current password is incorrect'
            ];
        }
        
        // Update password
        try {
            $this->userModel->updatePassword($userId, $newPassword);
            
            return [
                'success' => true,
                'message' => 'Password updated successfully'
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update password'
            ];
        }
    }
    
    /**
     * Create user session
     */
    private function createSession($user)
    {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Store user data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_data'] = $this->sanitizeUser($user);
        $_SESSION['logged_in_at'] = time();
    }
    
    /**
     * Remove sensitive data from user array
     */
    private function sanitizeUser($user)
    {
        if (!$user) {
            return null;
        }
        
        unset($user['password']);
        return $user;
    }
    
    /**
     * Check if user has role
     */
    public function hasRole($role)
    {
        if (!$this->check()) {
            return false;
        }
        
        return $_SESSION['user_role'] === $role;
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->hasRole('admin');
    }
}
