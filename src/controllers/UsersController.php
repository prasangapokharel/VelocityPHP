<?php
/**
 * Users Controller
 * Handles user-related requests
 * 
 * @package VelocityPHP
 */

namespace App\Controllers;

use App\Models\UserModel;

class UsersController extends BaseController
{
    private $userModel;
    
    public function __construct()
    {
        $this->userModel = new UserModel();
    }
    
    /**
     * Display users list page OR return users JSON for API
     */
    public function index($params, $isAjax)
    {
        // Check if this is an API call (URL starts with /api/)
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $isApiCall = strpos($requestUri, '/api/') !== false;
        
        if ($isApiCall) {
            // Return JSON data for API calls
            try {
                $users = $this->userModel->all();
                
                // Remove sensitive data from all users
                $users = array_map(function($user) {
                    if (isset($user['password'])) {
                        unset($user['password']);
                    }
                    return $user;
                }, $users);
                
                return $this->jsonSuccess('Users retrieved successfully', $users);
            } catch (\Exception $e) {
                return $this->jsonError('Failed to retrieve users: ' . $e->getMessage(), [], 500);
            }
        }
        
        // For page navigation, let the router render the view
        return null;
    }
    
    /**
     * Display user creation form
     */
    public function create($params, $isAjax)
    {
        // For page navigation, let the router render the view
        return null;
    }
    
    /**
     * Display single user
     */
    public function show($params, $isAjax)
    {
        $userId = $params['id'] ?? null;
        
        if (!$userId) {
            return $this->jsonError('User ID required', [], 400);
        }
        
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            return $this->jsonError('User not found', [], 404);
        }
        
        if ($isAjax) {
            return $this->jsonSuccess('User retrieved successfully', $user);
        }
        
        return null;
    }
    
    /**
     * Create new user
     */
    public function store($params, $isAjax)
    {
        // Validate input
        $validation = $this->validate($this->post(), [
            'name' => 'required|min:3|max:100',
            'email' => 'required|email',
            'role' => 'required'
        ]);
        
        if ($validation !== true) {
            return $this->jsonError('Validation failed', $validation, 422);
        }
        
        // Sanitize input
        $data = $this->sanitize([
            'name' => $this->post('name'),
            'email' => $this->post('email'),
            'role' => $this->post('role'),
            'status' => 'active'
        ]);
        
        // Generate a secure random password if not provided
        if (!isset($data['password']) || empty($data['password'])) {
            $randomPassword = bin2hex(random_bytes(16)); // 32 character random password
            $data['password'] = password_hash($randomPassword, PASSWORD_DEFAULT);
        }
        
        try {
            // Check if email already exists
            $existingUser = $this->userModel->findByEmail($data['email']);
            if ($existingUser) {
                return $this->jsonError('Email already exists', [], 422);
            }
            
            // Create user
            $userId = $this->userModel->create($data);
            
            // Get the created user
            $user = $this->userModel->find($userId);
            
            // Remove sensitive data from response
            if (isset($user['password'])) {
                unset($user['password']);
            }
            
            return $this->jsonSuccess('User created successfully', $user);
            
        } catch (\Exception $e) {
            return $this->jsonError('Failed to create user: ' . $e->getMessage(), [], 500);
        }
    }
    
    /**
     * Update user
     */
    public function update($params, $isAjax)
    {
        $userId = $params['id'] ?? null;
        
        if (!$userId) {
            return $this->jsonError('User ID required', [], 400);
        }
        
        // Validate input
        $validation = $this->validate($this->post(), [
            'name' => 'required|min:3|max:100',
            'email' => 'required|email'
        ]);
        
        if ($validation !== true) {
            return $this->jsonError('Validation failed', $validation, 422);
        }
        
        // Sanitize input
        $data = $this->sanitize([
            'name' => $this->post('name'),
            'email' => $this->post('email')
        ]);
        
        try {
            // Check if user exists
            $user = $this->userModel->find($userId);
            if (!$user) {
                return $this->jsonError('User not found', [], 404);
            }
            
            // Check if email is taken by another user
            $existingUser = $this->userModel->findByEmail($data['email']);
            if ($existingUser && $existingUser['id'] != $userId) {
                return $this->jsonError('Email already exists', [], 422);
            }
            
            // Update user
            $this->userModel->update($userId, $data);
            
            // Get updated user
            $updatedUser = $this->userModel->find($userId);
            
            // Remove sensitive data from response
            if (isset($updatedUser['password'])) {
                unset($updatedUser['password']);
            }
            
            return $this->jsonSuccess('User updated successfully', $updatedUser);
            
        } catch (\Exception $e) {
            return $this->jsonError('Failed to update user: ' . $e->getMessage(), [], 500);
        }
    }
    
    /**
     * Delete user
     */
    public function destroy($params, $isAjax)
    {
        $userId = $params['id'] ?? null;
        
        if (!$userId) {
            return $this->jsonError('User ID required', [], 400);
        }
        
        try {
            // Check if user exists
            $user = $this->userModel->find($userId);
            if (!$user) {
                return $this->jsonError('User not found', [], 404);
            }
            
            // Delete user
            $this->userModel->delete($userId);
            
            return $this->jsonSuccess('User deleted successfully');
            
        } catch (\Exception $e) {
            return $this->jsonError('Failed to delete user: ' . $e->getMessage(), [], 500);
        }
    }
    
    /**
     * Get all users (API endpoint)
     */
    public function getUsers($params, $isAjax)
    {
        try {
            $users = $this->userModel->all();
            return $this->jsonSuccess('Users retrieved successfully', $users);
        } catch (\Exception $e) {
            return $this->jsonError('Failed to retrieve users: ' . $e->getMessage(), [], 500);
        }
    }
}