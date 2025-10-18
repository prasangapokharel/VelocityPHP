<?php
/**
 * User Model
 * Handles user data operations
 * 
 * @package NativeMVC
 */

namespace App\Models;

class UserModel extends BaseModel
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $fillable = ['name', 'email', 'password', 'role', 'status'];
    
    /**
     * Find user by email
     */
    public function findByEmail($email)
    {
        return $this->where(['email' => $email], null, 1)[0] ?? null;
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
     * Create user with password hashing
     */
    public function createUser($data)
    {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        return $this->create($data);
    }
    
    /**
     * Verify user password
     */
    public function verifyPassword($userId, $password)
    {
        $user = $this->find($userId);
        
        if (!$user) {
            return false;
        }
        
        return password_verify($password, $user['password']);
    }
    
    /**
     * Update user password
     */
    public function updatePassword($userId, $newPassword)
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        return $this->update($userId, ['password' => $hashedPassword]);
    }
}
