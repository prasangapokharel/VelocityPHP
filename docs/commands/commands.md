# VelocityPhp CLI Commands

Complete reference for all CLI commands available in VelocityPhp framework.

---

## Table of Contents

1. [Development Server](#development-server)
2. [Database Seeding](#database-seeding)
3. [API Documentation](#api-documentation)
4. [Testing](#testing)
5. [Quick Reference](#quick-reference)

---

## Development Server

Start the built-in PHP development server with automatic network detection.

### Basic Usage

```bash
php start.php
```

### What It Does

- Starts PHP built-in server on `0.0.0.0:8001`
- Detects your local network IP for mobile/device testing
- Uses `router.php` for proper URL routing
- Displays both localhost and network URLs

### Output Example

```
╔════════════════════════════════════════════════════════════════╗
║             VelocityPhp Development Server                     ║
╚════════════════════════════════════════════════════════════════╝

Server running at:
  → Local:    http://localhost:8001
  → Network:  http://192.168.1.100:8001

Press Ctrl+C to stop the server
```

### Configuration

Edit `start.php` to change:
- **Port**: Default `8001`
- **Host**: Default `0.0.0.0` (all interfaces)
- **Document Root**: Default `public/`

---

## Database Seeding

Generate test data for development and testing.

### Commands

```bash
# Show help
php seed.php help

# Seed all tables (users, categories, posts, comments)
php seed.php all

# Seed with custom count
php seed.php all 50

# Seed specific tables
php seed.php users 20        # Create 20 users
php seed.php categories      # Create default categories
php seed.php posts 30        # Create 30 posts
php seed.php comments 100    # Create 100 comments

# Clear and re-seed (fresh start)
php seed.php fresh

# Fresh seed with custom count
php seed.php fresh 100

# Truncate tables (clear data)
php seed.php truncate
```

### Available Seeders

| Command | Description | Default Count |
|---------|-------------|---------------|
| `users [n]` | Create fake users | 10 |
| `categories` | Create predefined categories | 10 |
| `posts [n]` | Create posts with content | 20 |
| `comments [n]` | Create comments on posts | 50 |
| `all [n]` | Run all seeders | varies |
| `fresh [n]` | Truncate + seed all | varies |
| `truncate` | Clear seeded data | - |

### Generated Data

**Users:**
- Random names from common first/last name pools
- Unique email addresses
- Password: `password123` (hashed)
- Random roles: user, moderator, admin
- Random status: active, inactive

**Posts:**
- Auto-generated titles using templates
- Lorem ipsum content (3-6 paragraphs)
- Random excerpt
- Assigned to random users
- Random categories attached

**Comments:**
- Attached to published posts
- Mix of registered and guest comments
- Random approval status

### Example Workflow

```bash
# Development setup
php seed.php fresh 50    # Fresh DB with 50 users, 100 posts, 250 comments

# Quick testing
php seed.php users 5     # Just add 5 more test users

# Reset for demo
php seed.php truncate    # Clear all seeded data
php seed.php all         # Re-seed with defaults
```

---

## API Documentation

Generate API documentation from your routes.

### Commands

```bash
# Console output (default)
php api-docs.php

# JSON format
php api-docs.php json

# HTML format (save to file)
php api-docs.php html > public/docs/api.html
```

### Output Formats

#### Console (Terminal)
```
╔════════════════════════════════════════════════════════════════════════════╗
║                    VELOCITYPHP API DOCUMENTATION                           ║
╚════════════════════════════════════════════════════════════════════════════╝

═══ WEB ROUTES ═══

  METHOD     URI                            ACTION                         NAME
  ------------------------------------------------------------------------------------------
  GET        /                              HomeController@index           home
  GET        /about                         HomeController@about           about
  GET        /login                         AuthController@showLogin       login

═══ API ROUTES ═══

  METHOD     URI                            ACTION                         NAME
  ------------------------------------------------------------------------------------------
  POST       /api/auth/login                AuthController@login           api.auth.login
  POST       /api/auth/register             AuthController@register        api.auth.register
  GET        /api/users/                    UsersController@index          api.users.index
             └─ Middleware: AuthMiddleware
```

#### JSON
```json
{
  "generated_at": "2026-01-05 12:00:00",
  "total_routes": 12,
  "routes": [
    {
      "methods": ["GET"],
      "uri": "/",
      "name": "home",
      "controller": "HomeController",
      "method": "index",
      "middleware": [],
      "is_api": false
    }
  ]
}
```

#### HTML
Generates a styled HTML page with:
- Color-coded HTTP methods
- Route grouping (Web/API)
- Middleware indicators
- Summary statistics

---

## Testing

Run the comprehensive test suite.

### Commands

```bash
# Run all tests
php tests/TestRunner.php

# Run HTTP tests (requires server running)
php start.php &                    # Start server in background
php tests/HttpTestRunner.php       # Run HTTP tests
```

### Test Categories

| Category | Tests |
|----------|-------|
| PSR Standards | Autoloading, naming conventions, no globals |
| Database | Connection, queries, transactions |
| Migrations | Table creation, indexes, constraints |
| Security | CSRF, XSS, SQL injection, rate limiting |
| Authentication | Register, login, logout, sessions |
| CRUD | Create, Read, Update, Delete operations |
| File Upload | Validation, size limits, MIME types |
| Integration | Full user flow testing |

### Test Output

```
╔════════════════════════════════════════════════════════════════╗
║          VELOCITYPHP COMPREHENSIVE TEST SUITE                  ║
╚════════════════════════════════════════════════════════════════╝

=== PSR STANDARDS ===
[PASS] PSR-4 autoloading works
[PASS] Class names use StudlyCaps
[PASS] No global variables in classes

=== SECURITY LAYERS ===
[PASS] CSRF token generation works
[PASS] XSS protection removes scripts
[PASS] SQL injection patterns detected

...

╔════════════════════════════════════════════════════════════════╗
║                      TEST SUMMARY                              ║
╚════════════════════════════════════════════════════════════════╝

  Total Tests: 138
  Passed: 138
  Failed: 0
  Success Rate: 100%

╔════════════════════════════════════════════════════════════════╗
║              ALL TESTS PASSED SUCCESSFULLY!                   ║
╚════════════════════════════════════════════════════════════════╝
```

---

## Quick Reference

### All Commands at a Glance

```bash
# Server
php start.php                    # Start dev server

# Seeding
php seed.php help               # Show seeding help
php seed.php all                # Seed all tables
php seed.php fresh              # Reset and seed
php seed.php users 10           # Seed 10 users
php seed.php truncate           # Clear seeded data

# Documentation
php api-docs.php                # Console API docs
php api-docs.php json           # JSON format
php api-docs.php html           # HTML format

# Testing
php tests/TestRunner.php        # Run all tests
php tests/HttpTestRunner.php    # HTTP tests (server required)
```

### Common Workflows

**Fresh Development Setup:**
```bash
# 1. Start fresh
php seed.php fresh 20

# 2. Start server
php start.php

# 3. Open browser
# http://localhost:8001
```

**Before Committing:**
```bash
# Run tests
php tests/TestRunner.php

# If all pass, commit
git add .
git commit -m "feat: your changes"
```

**Generate Documentation:**
```bash
# Update API docs
php api-docs.php html > public/docs/api.html
```

---

## Environment Variables

Commands respect `.env` file settings:

```env
# Database (for seeding/testing)
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=native
DB_USERNAME=root
DB_PASSWORD=

# App (for testing)
APP_DEBUG=true
APP_ENV=local
```

---

## Troubleshooting

### Server won't start
```bash
# Check if port is in use
netstat -an | grep 8001

# Use different port (edit start.php)
```

### Seeding fails
```bash
# Check database connection
php -r "new PDO('mysql:host=localhost;dbname=native', 'root', '');"

# Check tables exist
mysql -u root native -e "SHOW TABLES;"
```

### Tests fail
```bash
# Check environment
php -v                          # PHP 7.4+ required

# Check extensions
php -m | grep -E "(pdo|json|mbstring)"

# Run with verbose errors
php -d display_errors=1 tests/TestRunner.php
```
