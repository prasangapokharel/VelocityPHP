<?php

declare(strict_types=1);

/**
 * API V1 Routes
 * 
 * All API v1 endpoints are defined here.
 * 
 * @package App\Api\V1
 */

use App\Api\V1\Router;

$router = new Router();

// ============================================================================
// Public Routes (No Authentication Required)
// ============================================================================

// Auth - Public
$router->post('/auth/register', 'AuthController@register');
$router->post('/auth/login', 'AuthController@login');

// ============================================================================
// Protected Routes (Authentication Required)
// ============================================================================

$router->group(['AuthMiddleware'], function (Router $router) {
    
    // Auth - Protected
    $router->get('/auth/me', 'AuthController@me');
    $router->post('/auth/refresh', 'AuthController@refresh');
    $router->post('/auth/logout', 'AuthController@logout');
    
    // Users CRUD
    $router->get('/users', 'UserController@index');
    $router->get('/users/{id}', 'UserController@show');
    $router->post('/users', 'UserController@store');
    $router->put('/users/{id}', 'UserController@update');
    $router->delete('/users/{id}', 'UserController@destroy');
});

return $router;
