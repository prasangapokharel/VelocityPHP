<?php
/**
 * VelocityPHP Development Server
 *
 * Starts the PHP built-in server in development mode with:
 *   - APP_ENV=development
 *   - APP_DEBUG=true
 *   - Full PHP error display
 *   - Verbose banner with debug info
 *
 * Usage:
 *   php dev.php              # Start on default port 8000
 *   php dev.php 3000         # Start on custom port
 *
 * @package VelocityPHP
 * @version 1.1.0
 */

// ── Enforce development environment ──────────────────────────────────────────
putenv('APP_ENV=development');
putenv('APP_DEBUG=true');
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// ── Configuration ─────────────────────────────────────────────────────────────
$host         = 'localhost';
$port         = isset($argv[1]) && ctype_digit($argv[1]) ? (int) $argv[1] : 8000;
$documentRoot = __DIR__ . '/public';
$serverUrl    = "http://{$host}:{$port}";

// ── Terminal colours (Windows + Unix compatible) ──────────────────────────────
$c = [
    'red'     => "\033[31m",
    'green'   => "\033[32m",
    'yellow'  => "\033[33m",
    'blue'    => "\033[34m",
    'magenta' => "\033[35m",
    'cyan'    => "\033[36m",
    'white'   => "\033[37m",
    'bold'    => "\033[1m",
    'reset'   => "\033[0m",
];

// ── Clear screen ──────────────────────────────────────────────────────────────
echo "\033[2J\033[H";

// ── Banner ────────────────────────────────────────────────────────────────────
echo "\n";
echo $c['cyan'] . $c['bold'];
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                                                                ║\n";
echo "║              ⚡  VELOCITYPHP  —  DEV SERVER  ⚡                ║\n";
echo "║                                                                ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo $c['reset'] . "\n";

// ── PHP version check ─────────────────────────────────────────────────────────
$phpVer = phpversion();
echo $c['blue'] . "PHP:          " . $c['reset'] . $phpVer;
if (version_compare($phpVer, '7.4.0', '>=')) {
    echo " " . $c['green'] . "✓\n" . $c['reset'];
} else {
    echo " " . $c['red'] . "✗  PHP 7.4+ required\n" . $c['reset'];
    exit(1);
}

// ── Required extension check ──────────────────────────────────────────────────
$required = ['pdo', 'json', 'mbstring', 'sqlite3'];
$missing  = array_filter($required, fn($e) => !extension_loaded($e));
if ($missing) {
    echo $c['red'] . "Missing extensions: " . implode(', ', $missing) . "\n" . $c['reset'];
    exit(1);
}

// ── Recommended extension status ─────────────────────────────────────────────
$recommended = ['opcache', 'apcu', 'zip', 'curl'];
echo "\n" . $c['blue'] . "Extensions:\n" . $c['reset'];
foreach ($required as $ext) {
    echo "  " . $c['green'] . "✓ " . $c['reset'] . str_pad($ext, 12) . " (required)\n";
}
foreach ($recommended as $ext) {
    $ok = extension_loaded($ext);
    echo "  " . ($ok ? $c['green'] . "✓ " : $c['yellow'] . "– ") . $c['reset'];
    echo str_pad($ext, 12) . ($ok ? "available\n" : "not loaded (optional)\n");
}

// ── Port availability check ───────────────────────────────────────────────────
echo "\n";
$sock = @fsockopen($host, $port, $errno, $errstr, 1);
if (is_resource($sock)) {
    fclose($sock);
    echo $c['red'] . "Port {$port} is already in use. Try: php dev.php <other-port>\n" . $c['reset'];
    exit(1);
}
echo $c['green'] . "✓ Port {$port} available\n" . $c['reset'];

// ── .env check ────────────────────────────────────────────────────────────────
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    echo $c['yellow'] . "⚠  No .env file found. Copy .env.example → .env before starting.\n" . $c['reset'];
}

// ── Server info box ───────────────────────────────────────────────────────────
echo "\n";
echo $c['blue'] . "┌────────────────────────────────────────────────────────────────┐\n" . $c['reset'];
$rows = [
    "Mode"        => $c['magenta'] . "DEVELOPMENT  (errors visible, debug ON)" . $c['reset'],
    "URL"         => $c['green']   . $c['bold'] . $serverUrl . $c['reset'],
    "Root"        => $c['yellow']  . $documentRoot . $c['reset'],
    "PHP"         => $c['cyan']    . $phpVer . $c['reset'],
    "OPcache"     => extension_loaded('opcache') ? $c['green'] . "enabled" . $c['reset'] : $c['yellow'] . "disabled" . $c['reset'],
];
foreach ($rows as $label => $value) {
    echo $c['blue'] . "│ " . $c['reset'];
    echo str_pad($label . ":", 14) . $value . "\n";
}
echo $c['blue'] . "└────────────────────────────────────────────────────────────────┘\n" . $c['reset'];

// ── Quick links ───────────────────────────────────────────────────────────────
echo "\n" . $c['cyan'] . $c['bold'] . "Quick Links:\n" . $c['reset'];

// Discover page routes automatically from src/views/pages/
$pagesDir = $documentRoot . '/../src/views/pages';
$routes   = ['/'];
if (is_dir($pagesDir)) {
    foreach (new DirectoryIterator($pagesDir) as $entry) {
        if ($entry->isDir() && !$entry->isDot()) {
            $routes[] = '/' . $entry->getFilename();
        }
    }
}
sort($routes);
foreach ($routes as $route) {
    echo "  " . $c['blue'] . $serverUrl . $route . $c['reset'] . "\n";
}

// ── Tips ──────────────────────────────────────────────────────────────────────
echo "\n" . $c['yellow'] . "Dev Tips:\n" . $c['reset'];
echo $c['yellow'] . "  • Errors are displayed in the browser (APP_DEBUG=true)\n" . $c['reset'];
echo $c['yellow'] . "  • Restart this script after adding new routes or config changes\n" . $c['reset'];
echo $c['yellow'] . "  • Use php start.php for production-style testing\n" . $c['reset'];
echo "\n" . $c['cyan'] . "Press Ctrl+C to stop\n\n" . $c['reset'];
echo $c['green'] . $c['bold'] . str_repeat("═", 66) . "\n\n" . $c['reset'];

// ── Launch server ─────────────────────────────────────────────────────────────
chdir($documentRoot);
$cmd = sprintf(
    'php -S %s:%d -t %s',
    escapeshellarg($host),
    $port,
    escapeshellarg($documentRoot)
);
passthru($cmd);
