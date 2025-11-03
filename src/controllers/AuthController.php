<?php
/**
 * VelocityPhp Auth Controller
 * Handles login/logout with minimal code
 * 
 * @package VelocityPhp
 */

namespace App\Controllers;

use App\Utils\Auth;
use App\Utils\Response;
use App\Models\UserModel;

class AuthController extends BaseController
{
    /**
     * Show login page
     */
    public function showLogin()
    {
        // Redirect if already logged in
        if (Auth::check()) {
            Response::redirect('/dashboard');
        }
        
        include VIEW_PATH . '/pages/login/index.php';
    }
    
    /**
     * Handle login
     */
    public function login($params, $isAjax)
    {
        $email = $this->post('email');
        $password = $this->post('password');
        $remember = $this->post('remember') === 'on';
        
        // Validate
        $errors = $this->validate($_POST, [
            'email' => 'required|email',
            'password' => 'required'
        ]);
        
        if ($errors !== true) {
            return $this->jsonError('Validation failed', $errors, 400);
        }
        
        // Attempt login
        if (Auth::login($email, $password, $remember)) {
            return $this->jsonSuccess('Login successful', [
                'user' => Auth::user(),
                'redirect' => '/dashboard'
            ]);
        }
        
        return $this->jsonError('Invalid credentials', [], 401);
    }
    
    /**
     * Handle logout
     */
    public function logout($params, $isAjax)
    {
        Auth::logout();
        
        if ($isAjax) {
            return $this->jsonSuccess('Logged out', ['redirect' => '/login']);
        }
        
        Response::redirect('/login');
    }
    
    /**
     * Show register page
     */
    public function showRegister()
    {
        if (Auth::check()) {
            Response::redirect('/dashboard');
        }
        
        include VIEW_PATH . '/pages/register/index.php';
    }
    
    /**
     * Handle registration
     */
    public function register($params, $isAjax)
    {
        $data = $this->post();
        
        // Validate
        $errors = $this->validate($data, [
            'name' => 'required|min:3',
            'email' => 'required|email',
            'password' => 'required|min:8',
            'password_confirmation' => 'required|confirmed'
        ]);
        
        if ($errors !== true) {
            return $this->jsonError('Validation failed', $errors, 400);
        }
        
        // Check if email exists
        $userModel = new UserModel();
        if ($userModel->findByEmail($data['email'])) {
            return $this->jsonError('Email already exists', [], 400);
        }
        
        // Create user
        $userId = $userModel->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Auth::hashPassword($data['password']),
            'role' => 'user',
            'status' => 'active'
        ]);
        
        // Auto login
        Auth::login($data['email'], $data['password']);
        
        return $this->jsonSuccess('Registration successful', [
            'user' => Auth::user(),
            'redirect' => '/dashboard'
        ]);
    }
}
