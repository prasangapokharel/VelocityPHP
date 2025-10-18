<?php
/**
 * Home Controller
 * Handles requests for the home page
 * 
 * @package NativeMVC
 */

namespace App\Controllers;

class HomeController extends BaseController
{
    /**
     * Display home page
     */
    public function index($params, $isAjax)
    {
        if ($isAjax) {
            return $this->view('index/index', [], 'Home - Native MVC');
        }
        
        // For non-AJAX, the router handles full page rendering
        return null;
    }

    public function login ($params, $isAjax) {
        if ($isAjax) {
            return $this->view('index/login', [], 'Login - Native MVC');
        }
        
        // For non-AJAX, the router handles full page rendering
        return null;
    }
}
