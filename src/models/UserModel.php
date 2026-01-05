<?php
/**
 * VelocityPhp User Model
 * Handles user data operations with optimized queries and caching
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

namespace App\Models;

use App\Utils\FileCache;

class UserModel extends BaseModel
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $fillable = ['name', 'email', 'password', 'role', 'status'];
    protected $timestamps = false;
    
    /**
     * Find user by ID with caching
     * 
     * Flow: Cache → Database (if miss) → Cache (store)
     */
    public function findCached(int $id): ?array
    {
        $cache = FileCache::getInstance();
        
        $result = $cache->getUserWithFallback($id, function($userId) {
            return $this->find($userId);
        });
        
        // Ensure we return null for not found, not false
        return $result ?: null;
    }
    
    /**
     * Find user by email
     */
    public function findByEmail($email)
    {
        return $this->where(['email' => $email], null, 1)[0] ?? null;
    }
    
    /**
     * Find user by email with caching
     */
    public function findByEmailCached(string $email): ?array
    {
        $cache = FileCache::getInstance();
        $cacheKey = 'email_' . md5($email);
        
        return $cache->remember($cacheKey, function() use ($email) {
            return $this->findByEmail($email);
        }, 3600, 'users');
    }
    
    /**
     * Get active users
     */
    public function getActiveUsers()
    {
        return $this->where(['status' => 'active'], 'created_at DESC');
    }
    
    /**
     * Get users by role
     */
    public function getUsersByRole($role)
    {
        return $this->where(['role' => $role], 'name ASC');
    }
    
    /**
     * Create user with password hashing and cache invalidation
     */
    public function createUser($data)
    {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        $id = $this->create($data);
        
        // Invalidate any related caches
        if ($id && isset($data['email'])) {
            $cache = FileCache::getInstance();
            $cache->delete('email_' . md5($data['email']), 'users');
        }
        
        return $id;
    }
    
    /**
     * Update user with cache invalidation
     */
    public function updateUser(int $id, array $data): bool
    {
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        $result = $this->update($id, $data);
        
        // Invalidate cache after update
        if ($result) {
            $this->invalidateUserCache($id);
        }
        
        return $result;
    }
    
    /**
     * Delete user with cache invalidation
     */
    public function deleteUser(int $id): bool
    {
        // Get user data before deletion to clear email cache
        $user = $this->find($id);
        
        $result = $this->delete($id);
        
        // Invalidate cache after delete
        if ($result) {
            $this->invalidateUserCache($id);
            
            // Also clear email-based cache
            if ($user && isset($user['email'])) {
                $cache = FileCache::getInstance();
                $cache->delete('email_' . md5($user['email']), 'users');
            }
        }
        
        return $result;
    }
    
    /**
     * Invalidate user cache
     * Call this whenever user data changes
     */
    public function invalidateUserCache(int $userId): void
    {
        $cache = FileCache::getInstance();
        $cache->invalidateUser($userId);
    }
    
    /**
     * Verify user password
     */
    public function verifyPassword($userId, $password)
    {
        $user = $this->findCached($userId);
        
        if (!$user) {
            return false;
        }
        
        return password_verify($password, $user['password']);
    }
    
    /**
     * Update user password with cache invalidation
     */
    public function updatePassword($userId, $newPassword)
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $result = $this->update($userId, ['password' => $hashedPassword]);
        
        if ($result) {
            $this->invalidateUserCache($userId);
        }
        
        return $result;
    }
    
    /**
     * Get all users with caching (for lists)
     */
    public function getAllCached(int $ttl = 300): array
    {
        $cache = FileCache::getInstance();
        
        return $cache->remember('all_users', function() {
            return $this->all();
        }, $ttl, 'data');
    }
    
    /**
     * Invalidate all users list cache
     */
    public function invalidateAllUsersCache(): void
    {
        $cache = FileCache::getInstance();
        $cache->delete('all_users', 'data');
    }
}
