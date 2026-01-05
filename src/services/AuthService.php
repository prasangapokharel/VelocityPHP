<?php
/**
 * Authentication Service
 * Wrapper around Auth utility for service-oriented architecture
 * 
 * @package VelocityPhp
 */

namespace App\Services;

use App\Utils\Auth;
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
    public function login($email, $password, $remember = false)
    {
        if (Auth::login($email, $password, $remember)) {
            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => $this->sanitizeUser(Auth::user())
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Invalid credentials'
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
            $userId = $this->userModel->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Auth::hashPassword($data['password']),
                'role' => $data['role'] ?? 'user',
                'status' => 'active'
            ]);
            
            // Auto-login after registration
            Auth::login($data['email'], $data['password']);
            
            return [
                'success' => true,
                'message' => 'Registration successful',
                'user' => $this->sanitizeUser(Auth::user())
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Logout user
     */
    public function logout()
    {
        Auth::logout();
        
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
        return Auth::check();
    }
    
    /**
     * Get authenticated user
     */
    public function user()
    {
        return $this->sanitizeUser(Auth::user());
    }
    
    /**
     * Get user ID
     */
    public function id()
    {
        return Auth::id();
    }
    
    /**
     * Update user password
     */
    public function updatePassword($userId, $currentPassword, $newPassword)
    {
        $user = $this->userModel->find($userId);
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Current password is incorrect'
            ];
        }
        
        try {
            $this->userModel->update($userId, [
                'password' => Auth::hashPassword($newPassword)
            ]);
            
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
        return Auth::hasRole($role);
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return Auth::hasRole('admin');
    }
}
