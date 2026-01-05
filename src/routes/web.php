<?php
/**
 * VelocityPhp Web Routes
 * Define your application routes here
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

use App\Utils\RouteCollection;

// ============================================================================
// Core Web Routes
// ============================================================================
RouteCollection::get('/', 'HomeController@index')->name('home');
RouteCollection::get('/documentation', 'HomeController@documentation')->name('documentation');

// ============================================================================
// API Routes (v1 handled by Api Router in index.php)
// ============================================================================
RouteCollection::group(['prefix' => 'api'], function() {
    
    // Auth API
    RouteCollection::group(['prefix' => 'auth'], function() {
        RouteCollection::post('/login', 'AuthController@login')->name('api.auth.login');
        RouteCollection::post('/register', 'AuthController@register')->name('api.auth.register');
        RouteCollection::post('/logout', 'AuthController@logout')->name('api.auth.logout');
    });
    
    // Users API (protected)
    RouteCollection::group(['prefix' => 'users', 'middleware' => ['AuthMiddleware']], function() {
        RouteCollection::get('/', 'UsersController@index')->name('api.users.index');
        RouteCollection::get('/{id}', 'UsersController@show')->name('api.users.show');
        RouteCollection::post('/', 'UsersController@store')->name('api.users.store');
        RouteCollection::put('/{id}', 'UsersController@update')->name('api.users.update');
        RouteCollection::delete('/{id}', 'UsersController@destroy')->name('api.users.delete');
    });
});

