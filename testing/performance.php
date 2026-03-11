<?php
/**
 * VelocityPHP Performance Benchmark
 *
 * Measures routing, DB query, ORM, cache, and response-time performance.
 * Run from project root:
 *   php testing/performance.php
 *
 * Requires a running MySQL server with the velocity database and a live
 * HTTP server on port 8000 (php start.php).
 */

declare(strict_types=1);

define('BASE_PATH',   dirname(__DIR__));
define('SRC_PATH',    BASE_PATH . '/src');
define('CONFIG_PATH', SRC_PATH . '/config');
define('VIEW_PATH',   SRC_PATH . '/views');
define('PUBLIC_PATH', BASE_PATH . '/public');

require_once SRC_PATH . '/utils/Autoloader.php';
\App\Utils\Autoloader::register();

// Bootstrap environment
\App\Config\Config::init();

// ─── Helpers ─────────────────────────────────────────────────────────────────

function bench(string $label, callable $fn, int $iterations = 1000): array
{
    // Warm-up
    $fn();

    $start = hrtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        $fn();
    }
    $elapsed = hrtime(true) - $start; // nanoseconds

    $totalMs  = $elapsed / 1_000_000;
    $avgUs    = $elapsed / $iterations / 1_000;
    $opsPerSec = $iterations / ($elapsed / 1_000_000_000);

    return [
        'label'       => $label,
        'iterations'  => $iterations,
        'total_ms'    => round($totalMs, 3),
        'avg_us'      => round($avgUs, 3),
        'ops_per_sec' => round($opsPerSec, 0),
    ];
}

function httpGet(string $url, int $timeoutSec = 5): array
{
    if (!function_exists('curl_init')) {
        return ['status' => 0, 'time_ms' => 0, 'size' => 0, 'error' => 'cURL not available'];
    }
    $start = hrtime(true);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_TIMEOUT         => $timeoutSec,
        CURLOPT_CONNECTTIMEOUT  => 2,
        CURLOPT_FOLLOWLOCATION  => true,
        CURLOPT_HEADER          => false,
    ]);
    $body    = curl_exec($ch);
    $info    = curl_getinfo($ch);
    $err     = curl_error($ch);
    curl_close($ch);
    $elapsed = (hrtime(true) - $start) / 1_000_000;

    return [
        'status'   => $info['http_code'] ?? 0,
        'time_ms'  => round($elapsed, 2),
        'size'     => strlen((string)$body),
        'error'    => $err,
    ];
}

function printTable(array $rows): void
{
    if (empty($rows)) {
        return;
    }
    $cols = array_keys($rows[0]);
    $widths = [];
    foreach ($cols as $col) {
        $widths[$col] = strlen($col);
    }
    foreach ($rows as $row) {
        foreach ($cols as $col) {
            $widths[$col] = max($widths[$col], strlen((string)($row[$col] ?? '')));
        }
    }

    $line = '+' . implode('+', array_map(fn($w) => str_repeat('-', $w + 2), $widths)) . '+';
    echo $line . "\n";
    $header = '|';
    foreach ($cols as $col) {
        $header .= ' ' . str_pad($col, $widths[$col]) . ' |';
    }
    echo $header . "\n" . $line . "\n";
    foreach ($rows as $row) {
        $r = '|';
        foreach ($cols as $col) {
            $r .= ' ' . str_pad((string)($row[$col] ?? ''), $widths[$col]) . ' |';
        }
        echo $r . "\n";
    }
    echo $line . "\n";
}

function pass(string $msg): void { echo "\033[32m[PASS]\033[0m $msg\n"; }
function fail(string $msg): void { echo "\033[31m[FAIL]\033[0m $msg\n"; }
function head(string $msg): void { echo "\n\033[1;34m=== $msg ===\033[0m\n"; }

// ─── 1. Autoloader / Config ───────────────────────────────────────────────────
head('1. Autoloader & Config');

$results = [];

$results[] = bench('Config::get() [cached]', function () {
    \App\Config\Config::get('app.name');
}, 5000);

$results[] = bench('Config::get() nested key', function () {
    \App\Config\Config::get('app.performance.db_pool_size');
}, 5000);

printTable($results);

// ─── 2. Routing ───────────────────────────────────────────────────────────────
head('2. RouteCollection dispatch (in-process)');

$results = [];
$routes = new \App\Utils\RouteCollection();
$routes->get('/', 'HomeController@index');
$routes->get('/about', 'HomeController@about');
$routes->get('/dashboard', 'DashboardController@index');
$routes->get('/users', 'UsersController@index');
$routes->get('/api/users/{id}', 'ApiController@getUser');
$routes->post('/login', 'AuthController@login');
$routes->post('/register', 'AuthController@register');

$results[] = bench('Static route match (/)', function () use ($routes) {
    $routes->dispatch('GET', '/');
}, 10000);

$results[] = bench('Static route match (/dashboard)', function () use ($routes) {
    $routes->dispatch('GET', '/dashboard');
}, 10000);

$results[] = bench('Parametric route match (/api/users/42)', function () use ($routes) {
    $routes->dispatch('GET', '/api/users/42');
}, 10000);

$results[] = bench('POST route match (/login)', function () use ($routes) {
    $routes->dispatch('POST', '/login');
}, 10000);

$results[] = bench('Miss (404 path)', function () use ($routes) {
    $routes->dispatch('GET', '/does-not-exist');
}, 10000);

printTable($results);

// ─── 3. VelocityCache ─────────────────────────────────────────────────────────
head('3. VelocityCache (SQLite)');

$cache   = \App\Utils\VelocityCache::getInstance();
$results = [];

// Seed a value so reads are warm
$cache->put('perf_test_key', str_repeat('x', 256), 3600);

$results[] = bench('Cache::put() 256-byte value', function () use ($cache) {
    $cache->put('bench_put', str_repeat('y', 256), 3600);
}, 20);

$results[] = bench('Cache::get() hit', function () use ($cache) {
    $cache->get('perf_test_key');
}, 2000);

$results[] = bench('Cache::get() miss', function () use ($cache) {
    $cache->get('key_that_does_not_exist_xyz');
}, 2000);

$results[] = bench('Cache::forget()', function () use ($cache) {
    $cache->forget('bench_put');
}, 20);

printTable($results);

// ─── 4. Database / ORM ────────────────────────────────────────────────────────
head('4. Database & ORM (MySQL)');

$results = [];
$user    = new \App\Models\UserModel();

// Count benchmark (uses cached query path after first call)
$results[] = bench('UserModel::count()', function () use ($user) {
    $user->count();
}, 200);

// Single-row find by PK
$first = $user->query("SELECT id FROM users LIMIT 1");
if (!empty($first)) {
    $id = $first[0]['id'];
    $results[] = bench('UserModel::find($id)', function () use ($user, $id) {
        $user->find($id);
    }, 500);
}

// Paginate (page 1, 10 per page)
$results[] = bench('UserModel::paginate(1, 10)', function () use ($user) {
    $user->paginate(1, 10);
}, 200);

// Raw query
$results[] = bench('Raw SELECT COUNT(*)', function () use ($user) {
    $user->query("SELECT COUNT(*) as n FROM users");
}, 300);

printTable($results);

// ─── 5. HTTP Response Times (live server) ─────────────────────────────────────
head('5. HTTP Response Times (http://localhost:8000)');

$baseUrl  = 'http://localhost:8000';
$httpRows = [];

$endpoints = [
    ['GET', '/'],
    ['GET', '/about'],
    ['GET', '/login'],
    ['GET', '/register'],
    ['GET', '/api/users'],
    ['GET', '/does-not-exist'],
];

$serverUp = false;
$probe = httpGet($baseUrl . '/', 2);
if ($probe['status'] > 0 && $probe['error'] === '') {
    $serverUp = true;
}

if (!$serverUp) {
    echo "  [SKIP] HTTP server not reachable on port 8000. Start with: php start.php\n";
} else {
    foreach ($endpoints as [$method, $path]) {
        // Take the median of 5 requests to reduce noise
        $times = [];
        for ($i = 0; $i < 5; $i++) {
            $r = httpGet($baseUrl . $path);
            $times[] = $r['time_ms'];
        }
        sort($times);
        $median = $times[2];
        $r = httpGet($baseUrl . $path); // one more for status/size
        $httpRows[] = [
            'method'     => $method,
            'path'       => $path,
            'status'     => $r['status'],
            'median_ms'  => $median,
            'size_bytes' => $r['size'],
        ];
    }
    printTable($httpRows);

    // Throughput test for the fastest endpoint
    head('5b. Throughput — GET / (sequential, 50 requests)');
    $start = hrtime(true);
    for ($i = 0; $i < 50; $i++) {
        httpGet($baseUrl . '/');
    }
    $elapsed = (hrtime(true) - $start) / 1_000_000_000;
    $rps = round(50 / $elapsed, 1);
    echo "  50 requests in {$elapsed}s → {$rps} req/s\n";
}

// ─── 6. Security helpers ──────────────────────────────────────────────────────
head('6. Security helpers');

// Simulate session for CSRF
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$results = [];

$results[] = bench('CSRF token generation (random_bytes)', function () {
    bin2hex(random_bytes(32));
}, 5000);

$results[] = bench('Auth::hashPassword() [bcrypt cost 12]', function () {
    \App\Utils\Auth::hashPassword('BenchmarkPassword!1');
}, 3); // bcrypt is intentionally slow

$results[] = bench('password_verify() [bcrypt]', function () {
    $hash = password_hash('test', PASSWORD_BCRYPT, ['cost' => 12]);
    password_verify('test', $hash);
}, 3);

printTable($results);

// ─── Summary ─────────────────────────────────────────────────────────────────
head('Benchmark complete');
echo "  PHP " . PHP_VERSION . "  |  " . date('Y-m-d H:i:s') . "\n";
echo "  OPcache: " . (function_exists('opcache_get_status') && opcache_get_status() !== false ? 'enabled' : 'disabled') . "\n";
$stats = \App\Models\BaseModel::getStats();
echo "  Total DB queries executed: {$stats['queries']}  |  Cache hits: {$stats['cache_hits']}\n\n";
