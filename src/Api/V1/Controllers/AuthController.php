<?php

declare(strict_types=1);

namespace App\Api\V1\Controllers;

use App\Models\UserModel;

/**
 * Auth API Controller
 * 
 * Authentication endpoints: login, register, logout, me
 * 
 * @package App\Api\V1
 */
final class AuthController extends BaseController
{
    private UserModel $model;

    public function __construct()
    {
        $this->model = new UserModel();
    }

    /**
     * POST /api/v1/auth/register
     * 
     * Register new user
     */
    public function register(): void
    {
        $errors = $this->validate([
            'name' => 'required|min:2|max:100',
            'email' => 'required|email|max:100',
            'password' => 'required|min:8|max:255'
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
            'role' => 'user',
            'status' => 'active'
        ]);

        $user = $this->model->find($id);
        $token = $this->generateToken($user);

        $this->created([
            'user' => $this->transformUser($user),
            'token' => $token,
            'token_type' => 'Bearer'
        ], 'Registration successful');
    }

    /**
     * POST /api/v1/auth/login
     * 
     * Login user
     */
    public function login(): void
    {
        $errors = $this->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!empty($errors)) {
            $this->validationError($errors);
        }

        $input = $this->input();
        $user = $this->model->findByEmail(strtolower(trim($input['email'])));

        if (!$user) {
            $this->unauthorized('Invalid credentials');
        }

        if (!password_verify($input['password'], $user['password'])) {
            $this->unauthorized('Invalid credentials');
        }

        if ($user['status'] !== 'active') {
            $this->forbidden('Account is ' . $user['status']);
        }

        $token = $this->generateToken($user);

        $this->ok([
            'user' => $this->transformUser($user),
            'token' => $token,
            'token_type' => 'Bearer'
        ], 'Login successful');
    }

    /**
     * POST /api/v1/auth/logout
     * 
     * Logout user (invalidate token)
     */
    public function logout(): void
    {
        // In a stateless API, logout is typically handled client-side
        // by removing the token. For added security, you could:
        // 1. Add token to a blacklist
        // 2. Use short-lived tokens with refresh tokens
        
        $this->ok(null, 'Logged out');
    }

    /**
     * GET /api/v1/auth/me
     * 
     * Get current authenticated user
     */
    public function me(): void
    {
        $user = $this->user();

        if (!$user) {
            $this->unauthorized();
        }

        $this->ok($this->transformUser($user));
    }

    /**
     * POST /api/v1/auth/refresh
     * 
     * Refresh token
     */
    public function refresh(): void
    {
        $user = $this->user();

        if (!$user) {
            $this->unauthorized();
        }

        // Get fresh user data
        $freshUser = $this->model->find($user['id']);

        if (!$freshUser || $freshUser['status'] !== 'active') {
            $this->unauthorized('Account no longer valid');
        }

        $token = $this->generateToken($freshUser);

        $this->ok([
            'user' => $this->transformUser($freshUser),
            'token' => $token,
            'token_type' => 'Bearer'
        ], 'Token refreshed');
    }

    /**
     * Generate JWT-like token
     */
    private function generateToken(array $user): string
    {
        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        
        $payload = base64_encode(json_encode([
            'sub' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24) // 24 hours
        ]));

        $secret = getenv('APP_KEY') ?: 'velocity-secret-key-change-in-production';
        $signature = base64_encode(hash_hmac('sha256', "{$header}.{$payload}", $secret, true));

        return "{$header}.{$payload}.{$signature}";
    }

    /**
     * Transform user for response
     */
    private function transformUser(array $user): array
    {
        return [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'status' => $user['status']
        ];
    }
}
