# Changelog

All notable changes to VelocityPHP are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.1.0] - 2026-03-11

### Added
- `VelocityCache::remember()` helper for cache-or-compute pattern
- `VelocityCache::forget()` for single-key invalidation
- Rate limiting middleware (`RateLimitMiddleware`) with configurable thresholds
- CORS middleware (`CorsMiddleware`) with wildcard and per-origin support
- `Response::json()` helper with proper Content-Type headers
- `Validator` class with fluent rule chaining (required, email, min, max, regex)
- `Security::sanitize()` for XSS-safe input handling
- GitHub Actions CI workflow for PHP 7.4 / 8.0 / 8.1 / 8.2
- `.github/ISSUE_TEMPLATE` for bug reports and feature requests
- `.github/PULL_REQUEST_TEMPLATE.md`
- `CONTRIBUTING.md` with full contribution guidelines
- `CODE_OF_CONDUCT.md`
- Topics and homepage added to repository metadata

### Changed
- `QueryBuilder` now uses named PDO parameters consistently
- `Router` improved regex matching — no longer greedy on optional trailing slashes
- `BaseController::view()` now passes a default `title` variable to all views
- `composer.json` updated with author info, homepage, and keywords

### Fixed
- Session fixation vulnerability in `Auth::login()`
- `VelocityCache` not respecting TTL = 0 as "forever"
- Missing 404 handler for undefined API routes
- `Logger` writing to wrong path on Windows hosts

---

## [1.0.0] - 2025-10-18

### Added
- Initial public release of VelocityPHP framework
- MVC architecture with file-based routing
- `QueryBuilder` with PDO prepared statements
- `VelocityCache` SQLite-backed cache engine
- Session-based authentication system
- Middleware pipeline interface
- AJAX SPA-like navigation (zero full page reloads)
- Built-in REST API layer via `ApiController`
- `CryptoService` and `CryptoController` for crypto data pages
- `.env.example` configuration template
- Apache `.htaccess` with URL rewriting
- Shared hosting compatibility
- `start.php` dev server launcher

[1.1.0]: https://github.com/prasangapokharel/VelocityPHP/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/prasangapokharel/VelocityPHP/releases/tag/v1.0.0
