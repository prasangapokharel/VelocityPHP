<?php
/**
 * API Controller
 * Handles AJAX API endpoints
 * 
 * @package VelocityPhp
 */

namespace App\Controllers;

use App\Models\UserModel;
use App\Utils\VelocityCache;
use App\Utils\FileUpload;

class ApiController extends BaseController
{
    private $userModel;
    protected $modelName = 'users';
    
    public function __construct()
    {
        $this->userModel = new UserModel();
    }
    
    /**
     * Default API endpoint (for backwards compatibility)
     */
    public function index($params, $isAjax)
    {
        return $this->jsonSuccess('API is running', [
            'version' => '1.0.0',
            'timestamp' => time(),
            'message' => 'VelocityPhp API'
        ]);
    }
    
    /**
     * Test endpoint
     */
    public function test($params, $isAjax)
    {
        return $this->jsonSuccess('API test successful', [
            'timestamp' => time(),
            'message' => 'The AJAX framework is working perfectly!'
        ]);
    }
    
    /**
     * Get users list
     */
    public function getUsers($params, $isAjax)
    {
        $cache = VelocityCache::getInstance();
        $cacheKey = $cache->generateKey('/api/users', 'GET', []);
        
        if ($cache->isEnabled()) {
            $cached = $cache->get($cacheKey);
            if ($cached !== null) {
                return $this->jsonSuccess('Users retrieved successfully', $cached);
            }
        }
        
        try {
            $users = $this->userModel->all();
            
            $users = array_map(function($user) {
                if (isset($user['password'])) {
                    unset($user['password']);
                }
                return $user;
            }, $users);
            
            if ($cache->isEnabled()) {
                $cache->put($cacheKey, $users, 300);
            }
            
            return $this->jsonSuccess('Users retrieved successfully', $users);
        } catch (\PDOException $e) {
            // Database connection error
            \App\Utils\Logger::error('Database error loading users', ['error' => $e->getMessage()]);
            return $this->jsonError('Database connection error. Please check your database configuration.', [], 500);
        } catch (\Exception $e) {
            // Other errors
            \App\Utils\Logger::error('Error loading users', ['error' => $e->getMessage()]);
            return $this->jsonError('Failed to retrieve users. Please try again.', [], 500);
        }
    }
    
    /**
     * Get single user
     */
    public function getUser($params, $isAjax)
    {
        $userId = $params['id'] ?? null;
        
        if (!$userId) {
            return $this->jsonError('User ID required', [], 400);
        }
        
        try {
            $user = $this->userModel->find($userId);
            
            if (!$user) {
                return $this->jsonError('User not found', [], 404);
            }
            
            if (isset($user['password'])) {
                unset($user['password']);
            }
            
            return $this->jsonSuccess('User retrieved successfully', $user);
        } catch (\Exception $e) {
            return $this->jsonError('Failed to retrieve user: ' . $e->getMessage(), [], 500);
        }
    }
    
    /**
     * Create new user
     */
    public function createUser($params, $isAjax)
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
        
        // Sanitize non-password fields only.
        // Never pass bcrypt hashes through sanitize(): strip_tags() could corrupt them.
        $data = $this->sanitize([
            'name' => $this->post('name'),
            'email' => $this->post('email'),
            'role' => $this->post('role'),
            'status' => 'active'
        ]);

        // Generate a secure random password and hash it AFTER sanitize() so the hash
        // is never passed through strip_tags().
        $randomPassword = bin2hex(random_bytes(16)); // 32 hex chars
        $data['password'] = password_hash($randomPassword, PASSWORD_DEFAULT);
        
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
            
            if (isset($user['password'])) {
                unset($user['password']);
            }
            
            $this->invalidateCache('users');
            
            return $this->jsonSuccess('User created successfully', $user);
            
        } catch (\PDOException $e) {
            // Database connection error
            \App\Utils\Logger::error('Database error creating user', ['error' => $e->getMessage()]);
            
            // Check specific database errors
            $errorMsg = $e->getMessage();
            if (strpos($errorMsg, 'SQLSTATE') !== false) {
                if (strpos($errorMsg, '1045') !== false) {
                    return $this->jsonError('Database authentication failed. Please check your credentials.', [], 500);
                } elseif (strpos($errorMsg, '2002') !== false || strpos($errorMsg, 'Connection refused') !== false) {
                    return $this->jsonError('Cannot connect to database server. Please check your database configuration.', [], 500);
                } elseif (strpos($errorMsg, '1049') !== false || strpos($errorMsg, 'Unknown database') !== false) {
                    return $this->jsonError('Database does not exist. Please create the database first.', [], 500);
                }
            }
            
            return $this->jsonError('Database error. Please check your database configuration.', [], 500);
        } catch (\Exception $e) {
            // Other errors
            \App\Utils\Logger::error('Error creating user', ['error' => $e->getMessage()]);
            return $this->jsonError('Failed to create user. Please try again.', [], 500);
        }
    }
    
    /**
     * Update user
     */
    public function updateUser($params, $isAjax)
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
            
            if (isset($updatedUser['password'])) {
                unset($updatedUser['password']);
            }
            
            $this->invalidateCache('users', $userId);
            
            return $this->jsonSuccess('User updated successfully', $updatedUser);
            
        } catch (\Exception $e) {
            return $this->jsonError('Failed to update user: ' . $e->getMessage(), [], 500);
        }
    }
    
    /**
     * Delete user
     */
    public function deleteUser($params, $isAjax)
    {
        $userId = $params['id'] ?? null;
        
        if (!$userId) {
            return $this->jsonError('User ID required', [], 400);
        }
        
        try {
            $user = $this->userModel->find($userId);
            
            if ($user) {
                $this->userModel->delete($userId);
            }
            
            $this->invalidateCache('users', $userId);
            
            return $this->jsonSuccess('User deleted successfully');
            
        } catch (\Exception $e) {
            return $this->jsonError('Failed to delete user: ' . $e->getMessage(), [], 500);
        }
    }
    
    /**
     * File upload endpoint  POST /api/upload
     *
     * Accepts a multipart/form-data POST with a 'file' field.
     * Optional POST fields:
     *   max_size  (bytes, default 5 MB)
     *
     * Returns JSON: { success, message, url, filename, size }
     */
    public function upload($params, $isAjax)
    {
        $maxSize = (int)($this->post('max_size') ?: 5 * 1024 * 1024);

        $result = FileUpload::upload('file', [
            'maxSize'    => $maxSize,
            'randomName' => true,
        ]);

        if (!$result['success']) {
            return $this->jsonError($result['message'], [], 422);
        }

        return $this->jsonSuccess('File uploaded successfully', [
            'url'      => $result['url'],
            'filename' => $result['filename'],
            'size'     => $result['size'],
        ]);
    }
    
    /**
     * Search endpoint
     */
    public function search($params, $isAjax)
    {
        $query = $this->get('q', '');
        
        // Perform search logic here
        
        return $this->jsonSuccess('Search completed', [
            'query' => $query,
            'results' => []
        ]);
    }
}
