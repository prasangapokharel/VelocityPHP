<?php

declare(strict_types=1);

namespace App\Api\V1\Controllers;

use App\Models\UserModel;
use App\Utils\FileCache;

/**
 * User API Controller
 * 
 * RESTful CRUD operations for users with file-based caching.
 * 
 * Cache Flow:
 *   Database → (only when data changes) → Cache → Client
 * 
 * @package App\Api\V1
 */
final class UserController extends BaseController
{
    private UserModel $model;
    private FileCache $cache;

    public function __construct()
    {
        $this->model = new UserModel();
        $this->cache = FileCache::getInstance();
    }

    /**
     * GET /api/v1/users
     * 
     * List users with pagination and caching
     */
    public function index(): void
    {
        $page = (int)($this->get('page', 1));
        $perPage = min((int)($this->get('per_page', 15)), 100);
        $status = $this->get('status');
        $role = $this->get('role');

        // Build cache key from query params
        $cacheKey = "users_list_p{$page}_pp{$perPage}_s" . ($status ?? 'all') . "_r" . ($role ?? 'all');
        
        // Try cache first
        $cachedResult = $this->cache->get($cacheKey, 'api');
        
        if ($cachedResult !== null) {
            // Return cached response
            $this->paginated($cachedResult['data'], $cachedResult['meta']);
            return;
        }

        // Cache miss - fetch from database
        $conditions = [];
        
        if ($status && in_array($status, ['active', 'inactive', 'suspended'])) {
            $conditions['status'] = $status;
        }
        
        if ($role && in_array($role, ['user', 'admin', 'moderator'])) {
            $conditions['role'] = $role;
        }

        $result = $this->model->paginate($page, $perPage, $conditions, 'created_at DESC');

        // Transform data - remove sensitive fields
        $users = array_map([$this, 'transform'], $result['data']);

        $meta = [
            'current_page' => $result['current_page'],
            'per_page' => $result['per_page'],
            'total' => $result['total'],
            'last_page' => $result['last_page'],
            'from' => $result['from'],
            'to' => $result['to']
        ];

        // Cache the result (5 minutes TTL)
        $this->cache->set($cacheKey, ['data' => $users, 'meta' => $meta], 'api', 300);

        $this->paginated($users, $meta);
    }

    /**
     * GET /api/v1/users/{id}
     * 
     * Get single user with caching
     */
    public function show(array $params): void
    {
        $id = (int)($params['id'] ?? 0);

        if ($id <= 0) {
            $this->notFound('User not found');
        }

        // Try to get from cache first, then database
        $user = $this->cache->remember("user_{$id}", function() use ($id) {
            return $this->model->find($id);
        }, 3600, 'users');

        if (!$user) {
            $this->notFound('User not found');
        }

        $this->ok($this->transform($user));
    }

    /**
     * POST /api/v1/users
     * 
     * Create new user with cache invalidation
     */
    public function store(): void
    {
        $errors = $this->validate([
            'name' => 'required|min:2|max:100',
            'email' => 'required|email|max:100',
            'password' => 'required|min:8|max:255',
            'role' => 'in:user,admin,moderator',
            'status' => 'in:active,inactive,suspended'
        ]);

        if (!empty($errors)) {
            $this->validationError($errors);
        }

        $input = $this->input();

        // Check email uniqueness
        $existing = $this->model->findByEmail($input['email']);
        if ($existing) {
            $this->validationError(['email' => ['Email already exists']]);
        }

        $id = $this->model->create([
            'name' => trim($input['name']),
            'email' => strtolower(trim($input['email'])),
            'password' => password_hash($input['password'], PASSWORD_ARGON2ID),
            'role' => $input['role'] ?? 'user',
            'status' => $input['status'] ?? 'active'
        ]);

        // Invalidate users list cache
        $this->invalidateListCache();

        $user = $this->model->find($id);

        $this->created($this->transform($user), 'User created');
    }

    /**
     * PUT /api/v1/users/{id}
     * 
     * Update user with cache invalidation
     */
    public function update(array $params): void
    {
        $id = (int)($params['id'] ?? 0);

        if ($id <= 0) {
            $this->notFound('User not found');
        }

        $user = $this->model->find($id);

        if (!$user) {
            $this->notFound('User not found');
        }

        $errors = $this->validate([
            'name' => 'min:2|max:100',
            'email' => 'email|max:100',
            'password' => 'min:8|max:255',
            'role' => 'in:user,admin,moderator',
            'status' => 'in:active,inactive,suspended'
        ]);

        if (!empty($errors)) {
            $this->validationError($errors);
        }

        $input = $this->input();
        $data = [];

        if (isset($input['name'])) {
            $data['name'] = trim($input['name']);
        }

        if (isset($input['email'])) {
            $email = strtolower(trim($input['email']));
            
            // Check uniqueness if email changed
            if ($email !== $user['email']) {
                $existing = $this->model->findByEmail($email);
                if ($existing) {
                    $this->validationError(['email' => ['Email already exists']]);
                }
            }
            
            $data['email'] = $email;
        }

        if (isset($input['password'])) {
            $data['password'] = password_hash($input['password'], PASSWORD_ARGON2ID);
        }

        if (isset($input['role'])) {
            $data['role'] = $input['role'];
        }

        if (isset($input['status'])) {
            $data['status'] = $input['status'];
        }

        if (empty($data)) {
            $this->error('No data to update', self::HTTP_BAD_REQUEST);
        }

        $this->model->update($id, $data);

        // Invalidate caches
        $this->model->invalidateUserCache($id);
        $this->invalidateListCache();

        $user = $this->model->find($id);

        $this->ok($this->transform($user), 'User updated');
    }

    /**
     * DELETE /api/v1/users/{id}
     * 
     * Delete user with cache invalidation
     */
    public function destroy(array $params): void
    {
        $id = (int)($params['id'] ?? 0);

        if ($id <= 0) {
            $this->notFound('User not found');
        }

        $user = $this->model->find($id);

        if (!$user) {
            $this->notFound('User not found');
        }

        // Prevent self-deletion
        $currentUserId = $this->userId();
        if ($currentUserId && $currentUserId === $id) {
            $this->error('Cannot delete yourself', self::HTTP_FORBIDDEN);
        }

        // Delete user (cache invalidation happens in model)
        $this->model->deleteUser($id);

        // Invalidate list cache
        $this->invalidateListCache();

        $this->noContent();
    }

    /**
     * Invalidate users list cache
     */
    private function invalidateListCache(): void
    {
        // Clear all users list caches
        $this->cache->invalidatePattern('users_list_*', 'api');
    }

    /**
     * Transform user data for API response
     */
    private function transform(array $user): array
    {
        return [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'status' => $user['status'],
            'created_at' => $user['created_at'] ?? null,
            'updated_at' => $user['updated_at'] ?? null
        ];
    }
}
