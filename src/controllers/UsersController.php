<?php
/**
 * Users Controller
 * Handles user-related requests
 * 
 * @package NativeMVC
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
     * Display users list
     */
    public function index($params, $isAjax)
    {
        if ($isAjax) {
            // You can fetch real data from the model
            // $users = $this->userModel->all();
            
            return $this->view('users/index', [
                // 'users' => $users
            ], 'Users - Native MVC');
        }
        
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
        
        // Fetch user from database
        // $user = $this->userModel->find($userId);
        
        if ($isAjax) {
            return $this->view('users/[id]/index', [
                'id' => $userId
                // 'user' => $user
            ], "User #{$userId} - Native MVC");
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
            'role' => $this->post('role')
        ]);
        
        try {
            // Create user
            // $userId = $this->userModel->create($data);
            
            return $this->jsonSuccess('User created successfully', [
                // 'id' => $userId
            ], '/users');
            
        } catch (\Exception $e) {
            return $this->jsonError('Failed to create user', [], 500);
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
            // Update user
            // $this->userModel->update($userId, $data);
            
            return $this->jsonSuccess('User updated successfully');
            
        } catch (\Exception $e) {
            return $this->jsonError('Failed to update user', [], 500);
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
            // Delete user
            // $this->userModel->delete($userId);
            
            return $this->jsonSuccess('User deleted successfully');
            
        } catch (\Exception $e) {
            return $this->jsonError('Failed to delete user', [], 500);
        }
    }
}
