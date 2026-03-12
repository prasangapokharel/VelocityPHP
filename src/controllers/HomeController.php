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
        return $this->view('index/index', [], 'Home - VelocityPhp');
    }
    
    /**
     * Display about page
     */
    public function about($params, $isAjax)
    {
        return $this->view('about/index', [], 'About - VelocityPhp');
    }
}
