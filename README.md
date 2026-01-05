# VelocityPHP Framework

A blazing-fast PHP framework with Next.js-style file-based routing, SPA-like AJAX navigation, and a complete REST API.

## Features

- **File-Based Routing** - Create pages by adding files, no configuration needed
- **AJAX Navigation** - SPA-like experience without page refreshes
- **REST API v1** - Complete API with Bearer token authentication
- **SQLite Caching** - High-performance caching with separate AJAX/HTML cache keys
- **Security** - CSRF protection, XSS prevention, rate limiting
- **Modern Architecture** - MVC pattern with clean separation of concerns

## Quick Start

```bash
# Clone the repository
git clone https://github.com/prasangapokharel/VelocityPHP.git
cd VelocityPHP

# Start the development server
php start.php

# Open in browser
http://localhost:8001
```

## Project Structure

```
VelocityPHP/
├── public/                 # Web root
│   ├── assets/            # CSS, JS, images
│   └── index.php          # Entry point
├── src/
│   ├── Api/V1/            # REST API v1
│   │   ├── Controllers/   # API controllers
│   │   ├── Middleware/    # API middleware
│   │   └── routes.php     # API routes
│   ├── config/            # Configuration files
│   ├── controllers/       # Web controllers
│   ├── models/            # Database models
│   ├── utils/             # Core utilities
│   ├── views/
│   │   ├── layouts/       # Page layouts
│   │   ├── components/    # Reusable components
│   │   └── pages/         # File-based pages
│   └── velocache/         # SQLite cache storage
├── database/              # Migrations and seeders
├── tests/                 # Test suites
└── start.php              # Development server
```

## File-Based Routing

Create pages by simply adding files to `src/views/pages/`:

```
src/views/pages/
├── index/index.php        → /
├── about/index.php        → /about
├── docs/index.php         → /docs
└── contact/index.php      → /contact
```

### Creating a New Page

1. Create directory: `mkdir src/views/pages/mypage`
2. Create file: `src/views/pages/mypage/index.php`
3. Access at: `http://localhost:8001/mypage`

### Page Template Example

```php
<?php
$title = "My Page Title";
$description = "Page description for SEO";
?>

<div class="container mx-auto px-lg py-xl">
    <h1 class="text-3xl font-bold">My Page</h1>
    <p>Your content here.</p>
</div>
```

## REST API v1

Base URL: `/api/v1`

### Authentication

```bash
# Register
curl -X POST http://localhost:8001/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"John","email":"john@example.com","password":"password123"}'

# Login
curl -X POST http://localhost:8001/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john@example.com","password":"password123"}'

# Response: {"success":true,"data":{"token":"...","token_type":"Bearer"}}
```

### Protected Routes

```bash
# Use Bearer token for authenticated requests
curl http://localhost:8001/api/v1/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN"

# Users CRUD
curl http://localhost:8001/api/v1/users \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/auth/register` | Register new user |
| POST | `/api/v1/auth/login` | Login and get token |
| GET | `/api/v1/auth/me` | Get current user |
| POST | `/api/v1/auth/refresh` | Refresh token |
| POST | `/api/v1/auth/logout` | Logout |
| GET | `/api/v1/users` | List users |
| POST | `/api/v1/users` | Create user |
| GET | `/api/v1/users/{id}` | Get user |
| PUT | `/api/v1/users/{id}` | Update user |
| DELETE | `/api/v1/users/{id}` | Delete user |

## AJAX Navigation

The framework automatically handles AJAX navigation for internal links. When clicking links:

- **Regular request**: Returns full HTML page
- **AJAX request**: Returns JSON `{"html":"...","title":"...","meta":[]}`

Add `data-no-ajax` attribute to disable AJAX for specific links:

```html
<a href="https://external.com" data-no-ajax>External Link</a>
```

## Configuration

### Environment Variables

Copy `.env.example` to `.env` and configure:

```env
DB_HOST=localhost
DB_NAME=velocity
DB_USER=root
DB_PASS=
DEBUG_MODE=true
```

### Database Setup

```bash
# Run migrations
php database/migrations/2024_01_01_000001_CreateUsersTable.php

# Seed database
php seed.php
```

## Running Tests

```bash
# Start server first
php start.php

# Run HTTP controller tests
php tests/HttpTestRunner.php

# Run API tests
php tests/ApiTestRunner.php
```

## Cache Management

```bash
# Clear cache (delete SQLite database)
rm -f src/velocache/velocity.db

# Cache is auto-created on next request
```

## Requirements

- PHP 8.0+
- SQLite extension
- PDO extension

## License

MIT License

## Repository

[https://github.com/prasangapokharel/VelocityPHP](https://github.com/prasangapokharel/VelocityPHP)
