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
// Crypto prices
RouteCollection::get('/crypto', 'CryptoController@index')->name('crypto');

