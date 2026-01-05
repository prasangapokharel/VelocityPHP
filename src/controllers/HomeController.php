<?php
/**
 * Home Controller
 * Handles requests for the home page
 * 
 * @package VelocityPhp
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
            return $this->view('index/index', [], 'Home - VelocityPhp');
        }
        
        // For non-AJAX (refresh), the router handles full page rendering
        return null;
    }
    
    /**
     * Display documentation page
     */
    public function documentation($params, $isAjax)
    {
        if ($isAjax) {
            return $this->view('documentation/index', [], 'Documentation - VelocityPhp');
        }
        
        // For non-AJAX (refresh), the router handles full page rendering
        return null;
    }
}
