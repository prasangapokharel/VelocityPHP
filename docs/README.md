# VelocityPHP — Complete Usage Guide

VelocityPHP is a lightweight, production-ready PHP framework built for shared hosting (cPanel / Apache mod_rewrite). It requires no Composer, no root access, and deploys by zip upload.

---

## Table of Contents

1. [Requirements](#requirements)
2. [Installation](#installation)
3. [Shared Hosting (cPanel) Deployment](#shared-hosting-cpanel-deployment)
4. [Local Development](#local-development)
5. [Directory Structure](#directory-structure)
6. [Configuration](#configuration)
7. [Routing](#routing)
8. [Controllers](#controllers)
9. [Models](#models)
10. [Views & Layouts](#views--layouts)
11. [Authentication](#authentication)
12. [Database Migrations](#database-migrations)
13. [Caching](#caching)
14. [File Uploads](#file-uploads)
15. [Logging](#logging)
16. [REST API](#rest-api)
17. [Testing](#testing)
18. [Performance Tuning](#performance-tuning)
19. [Security Checklist](#security-checklist)

---

## Requirements

| Requirement | Minimum | Recommended |
|-------------|---------|-------------|
| PHP | 8.1 | 8.2+ |
| MySQL / MariaDB | 5.7 / 10.3 | 8.0+ |
| Apache | 2.2 | 2.4 |
| mod_rewrite | required | — |
| PDO + pdo_mysql | required | — |
| cURL | recommended | — |
| SQLite3 | for cache | — |

---

## Installation

### Option A — Git clone (local/VPS)

```bash
git clone https://github.com/prasangapokharel/VelocityPHP.git
cd VelocityPHP
cp .env.velocity.example .env.velocity   # edit with your credentials
php migrate.php                          # run DB migrations
php start.php                            # start dev server on port 8001
```

### Option B — Zip upload (shared hosting)

See [Shared Hosting Deployment](#shared-hosting-cpanel-deployment) below.

---

## Shared Hosting (cPanel) Deployment

### Recommended layout — `public/` as document root

This is the cleanest and most secure option.

1. **Upload** the entire project zip to a directory above `public_html`, e.g. `/home/user/velocityphp/`.
2. In cPanel → **Domains**, point your domain's document root to `/home/user/velocityphp/public`.
3. Edit `.env.velocity` with your database credentials.
4. Run migrations via SSH:  
   ```bash
   php /home/user/velocityphp/migrate.php
   ```
   Or use cPanel's **Terminal** if SSH is unavailable.
5. Set `APP_URL` in `.env.velocity` to your domain:
   ```
   APP_URL=https://yourdomain.com
   ```

### Fallback layout — everything in `public_html/`

If your host does not allow changing the document root:

1. Upload everything directly into `public_html/`.
2. The root `.htaccess` will block sensitive directories and redirect all requests into `public/`.
3. Edit `.env.velocity` and run migrations as above.

> **Note:** The root `.htaccess` uses `R=301` permanent redirects. Clear your browser cache after first deployment.

---

## Local Development

```bash
# Start the built-in dev server (port 8001, doc root = public/)
php start.php

# Or use the alternative dev launcher
php dev.php
```

The server listens on `http://localhost:8001`.

---

## Directory Structure

```
VelocityPHP/
├── database/
│   └── migrations/          # SQL migration files
├── docs/                    # Documentation
├── logs/                    # Application logs (auto-created)
├── public/                  # Web root (point Apache here)
│   ├── index.php            # Single entry point
│   ├── .htaccess            # mod_rewrite + security headers
│   └── assets/              # CSS, JS, images
├── src/
│   ├── config/              # app.php, database.php, Config.php
│   ├── controllers/         # Controller classes
│   ├── database/            # Migration.php (schema builder)
│   ├── models/              # BaseModel, UserModel, etc.
│   ├── routes/              # web.php (route definitions)
│   ├── services/            # Business-logic services
│   ├── utils/               # Auth, Router, Logger, Cache, etc.
│   ├── velocache/           # SQLite cache database (auto-created)
│   └── views/               # Templates
│       ├── layouts/         # main.php (HTML shell)
│       ├── pages/           # One subdirectory per route
│       └── components/      # navbar.php, footer.php
├── storage/
│   ├── cache/               # CryptoService JSON cache
│   ├── config/              # Config file cache (JSON)
│   └── uploads/             # File uploads
├── testing/                 # Test and benchmark scripts
├── .env.velocity            # Environment variables (never commit)
├── .htaccess                # Root redirect (shared hosting fallback)
├── migrate.php              # CLI migration runner
└── start.php                # Local dev server launcher
```

---

## Configuration

### `.env.velocity`

```ini
APP_NAME=VelocityPHP
APP_ENV=production          # development | staging | production
APP_DEBUG=false             # true only for development
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=velocity
DB_USER=root
DB_PASS=secret

LOG_LEVEL=info              # debug | info | warning | error
TIMEZONE=UTC
```

> `APP_DEBUG=false` is the safe default. Debug mode is **only** enabled when this is explicitly set to `true`.

### `src/config/app.php`

Runtime application settings. Read via `Config::get('app.key')`.

### `src/config/database.php`

Database driver configuration. Supports `mysql`, `pgsql`, `sqlite`.

---

## Routing

Routes are defined in `src/routes/web.php`:

```php
use App\Utils\RouteCollection;

// Basic routes
RouteCollection::get('/', 'HomeController@index');
RouteCollection::get('/about', 'HomeController@about');
RouteCollection::post('/login', 'AuthController@login');

// Route with parameter
RouteCollection::get('/users/{id}', 'UsersController@show');

// Resource routes (GET index, GET show/{id}, POST store, PUT update/{id}, DELETE destroy/{id})
RouteCollection::resource('/api/users', 'ApiController');
```

**Controller method signatures:**

```php
// No parameters
public function index() { ... }

// With route parameters + AJAX flag
public function show($params, $isAjax) {
    $id = $params['id'];
}
```

**File-based routing fallback:**  
If no registered route matches, the router checks for a view file at  
`src/views/pages/{route}/index.php`.

---

## Controllers

See [docs/controllers/README.md](controllers/README.md) for full API.

```php
namespace App\Controllers;

class PostController extends BaseController
{
    public function index()
    {
        $posts = (new PostModel())->all('created_at DESC', 20);
        return $this->view('posts/index', ['posts' => $posts], 'Posts');
    }

    public function store($params, $isAjax)
    {
        $data = $this->post();                // parsed POST / JSON body
        $errors = $this->validate($data, [
            'title'   => 'required|min:3',
            'content' => 'required',
        ]);
        if ($errors !== true) {
            return $this->jsonError('Validation failed', $errors, 400);
        }
        // ...
        return $this->jsonSuccess('Created', ['id' => $id], 201);
    }
}
```

---

## Models

See [docs/models/README.md](models/README.md) for full API.

```php
namespace App\Models;

class PostModel extends BaseModel
{
    protected $table    = 'posts';
    protected $fillable = ['title', 'content', 'user_id', 'status'];

    public function findByUser($userId)
    {
        return $this->where(['user_id' => $userId], 'created_at DESC', 50);
    }
}
```

---

## Views & Layouts

Views live at `src/views/pages/{name}/index.php`.  
The layout wrapper is `src/views/layouts/main.php`.

```php
// In a controller:
return $this->view('posts/index', ['posts' => $posts], 'Post List');
```

The layout exposes `$content` (rendered page HTML), `$title`, and `$baseUrl`.

**Component includes:**
```php
<?php include VIEW_PATH . '/components/navbar.php'; ?>
```

---

## Authentication

```php
use App\Utils\Auth;

// Login
Auth::login($email, $password, $remember = false);

// Register then auto-login (avoids brute-force delay)
$userId = $userModel->create([...]);
Auth::loginById($userId);

// Check auth
if (!Auth::check()) { Response::redirect('/login'); }

// Require auth (redirects or returns 401 for AJAX)
Auth::require();

// Get current user
$user = Auth::user();  // assoc array

// Logout
Auth::logout();

// Hash a password (Argon2id when available, bcrypt fallback)
$hash = Auth::hashPassword($plaintext);
```

**Remember-me** tokens are stored in the `remember_tokens` table (created by migration `0002`).

**CSRF** token is available as `$_SESSION['csrf_token']` and exposed in the layout as  
`<meta name="csrf-token" content="...">`.  
The JS engine (`app.js`) reads this meta tag and sends `X-CSRF-Token` on every AJAX request.

---

## Database Migrations

See [docs/migrations/README.md](migrations/README.md) for full API.

```bash
# Run all pending migrations
php migrate.php

# Check migration status
php migrate.php status
```

**Creating a migration:**

```php
// database/migrations/0003_create_posts_table.php
return new class {
    public function up(MigrationManager $m): void
    {
        $m->create('posts', function (TableBuilder $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('title');
            $t->text('content');
            $t->enum('status', ['draft', 'published'])->default('draft');
            $t->timestamps();
            $t->foreign('user_id')->references('id')->on('users')->onDelete('CASCADE');
        });
    }

    public function down(MigrationManager $m): void
    {
        $m->dropIfExists('posts');
    }
};
```

---

## Caching

**VelocityCache** (SQLite-backed, request-level):

```php
$cache = \App\Utils\VelocityCache::getInstance();

$cache->put('key', $value, 3600);      // TTL in seconds
$value = $cache->get('key');           // null on miss
$cache->forget('key');
$cache->flush();                       // clear all
```

**Config file cache** (`storage/config/`): JSON, auto-invalidated when source file changes.

**Query cache** (`BaseModel`): in-process, cleared on write operations.

---

## File Uploads

```php
use App\Utils\FileUpload;

$uploader = new FileUpload('avatar', [
    'allowed_types' => ['image/jpeg', 'image/png', 'image/webp'],
    'max_size'      => 5 * 1024 * 1024, // 5 MB
]);

if ($uploader->upload()) {
    $path = $uploader->getFilePath();
} else {
    $errors = $uploader->getErrors();
}
```

Uploads are stored in `storage/uploads/` with a random filename.  
The `storage/uploads/.htaccess` blocks PHP execution inside the upload directory.

---

## Logging

```php
use App\Utils\Logger;

Logger::info('User registered', ['user_id' => $id]);
Logger::warning('Slow query', ['ms' => 320]);
Logger::error('Payment failed', ['order' => $orderId]);
Logger::exception($e);                 // full stack trace
```

Daily log files: `logs/app-YYYY-MM-DD.log`  
Error-only log: `logs/error.log`

---

## REST API

See [docs/api/README.md](api/README.md) for all endpoints and request/response formats.

Quick reference:

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | `/api/users` | yes | List users (paginated) |
| POST | `/api/users` | yes | Create user |
| GET | `/api/users/{id}` | yes | Get single user |
| PUT | `/api/users/{id}` | yes | Update user |
| DELETE | `/api/users/{id}` | yes | Delete user |
| POST | `/api/upload` | yes | Upload file |

All mutating API calls require the `X-CSRF-Token` header (set from `<meta name="csrf-token">`).

---

## Testing

```bash
# Database integration tests (15 tests)
php testing/db_full_test.php

# Full system / HTTP tests (32 tests) — requires running server
php testing/system_test.php

# Performance benchmark
php testing/performance.php
```

---

## Performance Tuning

1. **Enable OPcache** — set `APP_ENV=production` in `.env.velocity`.
2. **MySQL query cache** — enabled by default in `BaseModel` (in-process).
3. **VelocityCache** — cache expensive computations for seconds/minutes.
4. **`all()` default limit** — `BaseModel::all()` caps at 1 000 rows by default; pass an explicit limit for larger datasets.
5. **Pagination** — use `$model->paginate($page, $perPage)` instead of `all()` for user-facing lists.
6. **Asset caching** — `public/.htaccess` sets `Cache-Control` and `Expires` headers for static assets.

---

## Security Checklist

- [x] `APP_DEBUG=false` in production (debug mode is opt-in, not opt-out)
- [x] CSRF protection on all POST/PUT/DELETE routes
- [x] Passwords hashed with Argon2id (bcrypt fallback)
- [x] SQL injection prevented by PDO prepared statements
- [x] Session regenerated on login (`session_regenerate_id`)
- [x] Remember-me tokens use selector/validator split (timing-safe)
- [x] Malformed remember-me cookies handled safely (no fatal error)
- [x] Client IP read only from `REMOTE_ADDR` (no header spoofing)
- [x] File uploads: MIME-type check, random filename, PHP execution blocked in upload dir
- [x] Config cache uses JSON (no `unserialize()` object injection)
- [x] Log files written with `LOCK_EX` (no race condition)
- [x] Sensitive directories blocked in `.htaccess` (`src/`, `storage/`, `logs/`, etc.)
- [x] `.env.velocity` blocked from direct HTTP access
- [ ] Enable HTTPS and set `secure` cookie flag in production
- [ ] Set `APP_ENV=production` to enable secure session cookies
- [ ] Review `rate_limiting` in `src/config/app.php` for public APIs
