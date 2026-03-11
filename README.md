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
- [Shared Hosting / cPanel Deploy](#shared-hosting--cpanel-deploy)
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
cp .env.velocity.example .env.velocity
# Edit .env.velocity with your credentials, then:
php migrate.php
php start.php
```

### Via Composer

```bash
composer create-project velocityphp/framework my-app
cd my-app
cp .env.velocity.example .env.velocity
```

---

## Shared Hosting / cPanel Deploy

No SSH, no Composer, no terminal — just a zip file upload. Here are the exact steps:

### Step 1 — Download the zip

Download the repository as a zip from GitHub (`Code → Download ZIP`), or produce one locally:

```bash
git archive --format=zip HEAD -o velocityphp.zip
```

### Step 2 — Upload and extract

- In cPanel **File Manager**, navigate to `public_html` (or a subdomain folder).
- Upload `velocityphp.zip` and use **Extract** to unzip it there.
- You should now have `public_html/VelocityPHP/` (or similar). Move the contents up one level so the structure is:

```
public_html/
├── public/          ← this is what the browser should hit
├── src/
├── database/
├── .env.velocity    ← you will create this in Step 3
├── .htaccess
└── ...
```

> **cPanel document root tip:** If you can set the domain's document root directly to `public_html/public/`, do that — it is the most secure option and the root `.htaccess` is not needed at all.
> If you cannot change the document root, keep everything in `public_html/` and the root `.htaccess` will redirect traffic into `/public/` automatically.

### Step 3 — Create your `.env.velocity`

Copy `.env.velocity.example` to `.env.velocity` in the same folder and fill in your values:

```env
APP_NAME="My App"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=cpanelusername_dbname
DB_USER=cpanelusername_dbuser
DB_PASS=your_db_password

CACHE_ENABLED=true
CACHE_LOCATION=src/velocache/velocity.db

SESSION_LIFETIME=120
SESSION_SECURE=true
SESSION_HTTPONLY=true
```

**That is the only file you need to change.** Everything else in the framework reads from `.env.velocity`.

### Step 4 — Set folder permissions

These directories must be writable by the web server (`755` or `775`):

```
logs/
storage/
storage/cache/
src/velocache/
public/uploads/
```

In cPanel File Manager, right-click each folder → **Change Permissions** → tick all three *Write* boxes (or set `755`).

### Step 5 — Run migrations

Open your browser and visit:

```
https://yourdomain.com/migrate-run
```

Or, if your host provides a **Terminal** / **PHP Script Runner** in cPanel:

```bash
php migrate.php
```

This creates the `users` and `remember_tokens` tables in your database.

### Step 6 — Done

Visit `https://yourdomain.com` — the framework should boot. If you see a blank page, check `logs/error.log` (temporarily set `APP_DEBUG=true` in `.env.velocity` to see errors in the browser, then set it back to `false`).

---

## Quick Start

```bash
# Start the built-in PHP development server (port 8001, doc-root = public/)
php start.php

# Alternative dev server with auto-reload
php dev.php
```

Open your browser at `http://localhost:8001`.

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
├── src/velocache/           # SQLite cache store (must be writable)
├── .env.velocity.example    # Environment config template — copy to .env.velocity
├── composer.json
└── start.php                # Dev server launcher (port 8001)
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
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=your_database_name
DB_USER=your_database_user
DB_PASS=your_database_password

CACHE_ENABLED=true
CACHE_LOCATION=src/velocache/velocity.db

SESSION_LIFETIME=120
SESSION_SECURE=true
SESSION_HTTPONLY=true
```

| Key | Default | Description |
|-----|---------|-------------|
| `APP_ENV` | `development` | `production` disables debug output and enables HSTS |
| `APP_DEBUG` | `false` | Set `true` locally to show errors in browser |
| `APP_URL` | `http://localhost` | Your full domain — used for asset URLs and redirects |
| `APP_KEY` | _(empty)_ | Generate: `php -r "echo bin2hex(random_bytes(32));"` |
| `DB_CONNECTION` | `mysql` | `mysql`, `pgsql`, or `sqlite` |
| `CACHE_ENABLED` | `true` | Disable to turn off SQLite page cache |
| `SESSION_SECURE` | `false` | Set `true` when running on HTTPS |

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
