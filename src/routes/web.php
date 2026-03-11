<?php
/**
 * VelocityPhp Web Routes
 * Define your application routes here
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

use App\Utils\RouteCollection;

// Core routes
RouteCollection::get('/', 'HomeController@index')->name('home');
RouteCollection::get('/about', 'HomeController@about')->name('about');

// Auth routes
RouteCollection::get('/login', 'AuthController@showLogin')->name('login');
RouteCollection::post('/login', 'AuthController@login');
RouteCollection::get('/register', 'AuthController@showRegister')->name('register');
RouteCollection::post('/register', 'AuthController@register');
RouteCollection::get('/logout', 'AuthController@logout')->name('logout');
RouteCollection::post('/logout', 'AuthController@logout');

// Dashboard (auth-protected)
RouteCollection::get('/dashboard', 'DashboardController@index')->name('dashboard');

// Users resource (page routes + REST mutations)
RouteCollection::resource('users', 'UsersController');

// API auth endpoints (used by login/register AJAX forms)
RouteCollection::post('/api/auth/login', 'AuthController@login');
RouteCollection::post('/api/auth/register', 'AuthController@register');

// API users endpoints (used by users management AJAX UI)
RouteCollection::get('/api/users', 'ApiController@getUsers');
RouteCollection::post('/api/users', 'ApiController@createUser');
RouteCollection::get('/api/users/{id}', 'ApiController@getUser');
RouteCollection::put('/api/users/{id}', 'ApiController@updateUser');
RouteCollection::delete('/api/users/{id}', 'ApiController@deleteUser');

// File upload endpoint
RouteCollection::post('/api/upload', 'ApiController@upload');

// Crypto prices
RouteCollection::get('/crypto', 'CryptoController@index')->name('crypto');

