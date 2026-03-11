# VelocityPHP Framework

<p align="center">
  <img src="https://img.shields.io/badge/PHP-%3E%3D7.4-blue?style=flat-square&logo=php" alt="PHP Version">
  <img src="https://img.shields.io/badge/License-MIT-green?style=flat-square" alt="License">
  <img src="https://img.shields.io/badge/Version-1.1.0-orange?style=flat-square" alt="Version">
  <img src="https://img.shields.io/github/stars/prasangapokharel/VelocityPHP?style=flat-square" alt="Stars">
  <img src="https://img.shields.io/github/forks/prasangapokharel/VelocityPHP?style=flat-square" alt="Forks">
  <img src="https://img.shields.io/github/issues/prasangapokharel/VelocityPHP?style=flat-square" alt="Issues">
  <img src="https://img.shields.io/github/actions/workflow/status/prasangapokharel/VelocityPHP/ci.yml?style=flat-square" alt="CI">
</p>

<p align="center">
  <strong>A blazing-fast, production-ready PHP framework with MVC architecture, SPA-like AJAX navigation, built-in caching, rate limiting, and a complete REST API layer.</strong>
</p>

---

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Directory Structure](#directory-structure)
- [Routing](#routing)
- [Controllers](#controllers)
- [Models & Database](#models--database)
- [Middleware](#middleware)
- [Caching (VelocaCache)](#caching-velocache)
- [Authentication](#authentication)
- [REST API](#rest-api)
- [Configuration](#configuration)
- [Contributing](#contributing)
- [Changelog](#changelog)
- [License](#license)

---

## Overview

VelocityPHP is a lightweight, high-performance PHP framework designed for developers who want the speed and simplicity of a micro-framework without sacrificing the structure and power of a full MVC framework. It ships with:

- A fast **file-based router** with support for named routes, route groups, and middleware
- **AJAX-powered SPA-like navigation** — no full page reloads
- **VelocaCache** — a custom SQLite-backed caching engine
- A **QueryBuilder** for fluent, safe database queries
- A **dependency injection container**
- Built-in **rate limiting**, **CORS handling**, and **CSRF protection**
- **Shared hosting compatible** — runs on any PHP 7.4+ host without modification

---

## Features

| Feature | Description |
|---|---|
| **Ultra-Fast Routing** | Regex-based router with zero overhead |
| **MVC Architecture** | Clean separation of controllers, models, and views |
| **VelocaCache** | SQLite-powered caching with TTL and tag invalidation |
| **QueryBuilder** | Fluent, PDO-backed query builder with prepared statements |
| **Middleware Pipeline** | PSR-inspired middleware stack (Auth, CORS, RateLimit) |
| **DI Container** | Lightweight dependency injection container |
| **REST API** | Built-in API layer with JSON responses |
| **AJAX Navigation** | SPA-like page transitions without a JS framework |
| **Security** | CSRF protection, input sanitization, secure sessions |
| **Shared Hosting Ready** | Works on cPanel / shared hosts out of the box |
| **Zero Dependencies** | No Composer packages required at runtime |

---

## Requirements

- PHP >= 7.4
- PDO extension
- JSON extension
- mbstring extension
- A web server (Apache with mod_rewrite, Nginx, or PHP built-in server)

---

## Installation

### Via Git Clone

```bash
git clone https://github.com/prasangapokharel/VelocityPHP.git
cd VelocityPHP
cp .env.example .env
```

### Via Composer

```bash
composer create-project velocityphp/framework my-app
cd my-app
cp .env.example .env
```

---

## Quick Start

```bash
# Start the built-in PHP development server
php start.php

# Or use the Composer script
composer serve
```

Open your browser at `http://localhost:8000`.

---

## Directory Structure

```
VelocityPHP/
├── public/                  # Web root (point your server here)
│   ├── index.php            # Front controller / entry point
│   ├── .htaccess            # Apache rewrite rules
│   └── assets/              # CSS, JS, images
├── src/
│   ├── config/              # App, database, and environment config
│   │   ├── app.php
│   │   ├── database.php
│   │   └── Config.php
│   ├── controllers/         # HTTP request controllers
│   │   ├── BaseController.php
│   │   ├── HomeController.php
│   │   ├── AuthController.php
│   │   ├── ApiController.php
│   │   └── CryptoController.php
│   ├── core/                # Framework core (DI container)
│   │   └── Container.php
│   ├── database/            # Migrations and QueryBuilder
│   │   ├── Migration.php
│   │   └── QueryBuilder.php
│   ├── middleware/          # Middleware pipeline
│   │   ├── MiddlewareInterface.php
│   │   ├── MiddlewareStack.php
│   │   ├── AuthMiddleware.php
│   │   ├── CorsMiddleware.php
│   │   └── RateLimitMiddleware.php
│   ├── models/              # Data models
│   │   ├── BaseModel.php
│   │   └── UserModel.php
│   ├── routes/              # Route definitions
│   │   └── web.php
│   ├── services/            # Business logic services
│   │   ├── AuthService.php
│   │   └── CryptoService.php
│   ├── utils/               # Core utilities
│   │   ├── Auth.php
│   │   ├── Autoloader.php
│   │   ├── Debug.php
│   │   ├── Logger.php
│   │   ├── Request.php
│   │   ├── Response.php
│   │   ├── Route.php
│   │   ├── Router.php
│   │   ├── Security.php
│   │   ├── Session.php
│   │   ├── Validator.php
│   │   ├── VelocityCache.php
│   │   └── View.php
│   └── views/               # Templates and layouts
│       ├── layouts/
│       ├── components/
│       ├── pages/
│       └── errors/
├── database/                # SQL schema files
├── storage/                 # Uploaded files, temp storage
├── logs/                    # Application logs
├── velocache/               # Cache store
├── .env.example             # Environment config template
├── composer.json
└── start.php                # Dev server launcher
```

---

## Routing

Routes are defined in `src/routes/web.php`:

```php
use App\Utils\Route;

// Basic GET route
Route::get('/', [HomeController::class, 'index']);

// POST route
Route::post('/login', [AuthController::class, 'login']);

// Route with parameter
Route::get('/users/{id}', [UsersController::class, 'show']);

// Route group with middleware
Route::group(['middleware' => 'auth'], function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/profile', [ProfileController::class, 'index']);
});

// API routes
Route::get('/api/users', [ApiController::class, 'users']);
```

---

## Controllers

Controllers extend `BaseController` and return views or JSON responses:

```php
namespace App\Controllers;

class HomeController extends BaseController
{
    public function index(): void
    {
        $this->view('pages/index/index', [
            'title' => 'Welcome to VelocityPHP',
        ]);
    }
}
```

---

## Models & Database

Use the fluent `QueryBuilder` for database access:

```php
namespace App\Models;

use App\Database\QueryBuilder;

class PostModel extends BaseModel
{
    protected string $table = 'posts';

    public function getPublished(): array
    {
        return QueryBuilder::table($this->table)
            ->where('status', 'published')
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->get();
    }
}
```

---

## Middleware

Middleware is registered in the pipeline and runs before controllers:

```php
// Auth middleware
class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (!Auth::check()) {
            return Response::redirect('/login');
        }
        return $next($request);
    }
}
```

---

## Caching (VelocaCache)

VelocaCache is a zero-dependency SQLite-backed cache engine with TTL:

```php
use App\Utils\VelocityCache;

// Store a value for 60 minutes
VelocityCache::put('key', $value, 3600);

// Retrieve a value
$value = VelocityCache::get('key');

// Remember (fetch or compute and store)
$posts = VelocityCache::remember('posts.all', 3600, function () {
    return PostModel::all();
});

// Invalidate a key
VelocityCache::forget('key');
```

---

## Authentication

VelocityPHP includes a session-based authentication system:

```php
use App\Utils\Auth;

// Attempt login
if (Auth::attempt($email, $password)) {
    redirect('/dashboard');
}

// Check if user is authenticated
if (Auth::check()) {
    $user = Auth::user();
}

// Logout
Auth::logout();
```

---

## REST API

API controllers return JSON responses automatically:

```php
class ApiController extends BaseController
{
    public function users(): void
    {
        $users = (new UserModel())->all();
        $this->json([
            'status'  => 'success',
            'data'    => $users,
            'count'   => count($users),
        ]);
    }
}
```

---

## Configuration

Copy `.env.velocity.example` to `.env.velocity` and update your settings:

```env
APP_NAME=VelocityPHP
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=velocityphp
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=sqlite
SESSION_LIFETIME=120
```

---

## Contributing

Contributions are welcome! Please read [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines on how to open issues, submit pull requests, and follow the code style.

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a full list of changes across releases.

---

## License

VelocityPHP is open-source software licensed under the [MIT License](LICENSE).

---

<p align="center">
  Built with care by <a href="https://github.com/prasangapokharel">Prasanga Pokharel</a> &mdash; <a href="https://www.prasangapokharel.com.np">prasangapokharel.com.np</a>
</p>
