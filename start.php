<?php
/**
 * VelocityPHP Production Server Launcher
 *
 * Starts the PHP built-in server in production-simulation mode.
 * For development with debug output, use: php dev.php
 *
 * Usage:
 *   php start.php            # Start on default port 8000
 *   php start.php 9000       # Start on custom port
 *
 * @package VelocityPHP
 * @version 1.1.0
 */

// ── Configuration ─────────────────────────────────────────────────────────────
$host         = '0.0.0.0';
$port         = isset($argv[1]) && ctype_digit($argv[1]) ? (int) $argv[1] : 8000;
$documentRoot = __DIR__ . '/public';
$serverUrl    = "http://localhost:{$port}";

// ── Detect local network IP ───────────────────────────────────────────────────
function velocityGetLocalIp(): ?string {
    // Method 1: stream_socket_client UDP trick (no sockets extension needed)
    $sock = @stream_socket_client('udp://8.8.8.8:80', $errno, $errstr, 1);
    if ($sock) {
        $addr = stream_socket_get_name($sock, false);
        fclose($sock);
        if ($addr && ($ip = strstr($addr, ':', true)) && $ip !== '0.0.0.0') {
            return $ip;
        }
    }
    // Method 2: OS command fallback
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $out = shell_exec('powershell -Command "(Get-NetIPAddress -AddressFamily IPv4 | Where-Object { $_.IPAddress -notmatch \'^127\' } | Select-Object -First 1).IPAddress" 2>nul');
    } else {
        $out = shell_exec("hostname -I 2>/dev/null | awk '{print $1}'");
    }
    $ip = trim((string)$out);
    return ($ip && filter_var($ip, FILTER_VALIDATE_IP)) ? $ip : null;
}
$localIp   = velocityGetLocalIp();
$networkUrl = $localIp ? "http://{$localIp}:{$port}" : null;

// ── Terminal colours (Windows + Unix compatible) ──────────────────────────────
$colors = [
    'green'   => "\033[32m",
    'yellow'  => "\033[33m",
    'blue'    => "\033[34m",
    'cyan'    => "\033[36m",
    'magenta' => "\033[35m",
    'red'     => "\033[31m",
    'bold'    => "\033[1m",
    'reset'   => "\033[0m",
];

// ── Clear screen ──────────────────────────────────────────────────────────────
echo "\033[2J\033[H";

// ── Banner ────────────────────────────────────────────────────────────────────
echo "\n";
echo $colors['cyan'] . $colors['bold'];
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                                                                ║\n";
echo "║                  ⚡ VELOCITYPHP FRAMEWORK ⚡                    ║\n";
echo "║                                                                ║\n";
echo "║              Ultra-Fast Production-Ready Framework            ║\n";
echo "║                                                                ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo $colors['reset'] . "\n";

// ── PHP version check ─────────────────────────────────────────────────────────
$phpVersion      = phpversion();
$requiredVersion = '7.4.0';
echo $colors['blue'] . "PHP Version: " . $colors['reset'] . $phpVersion;

if (version_compare($phpVersion, $requiredVersion, '>=')) {
    echo " " . $colors['green'] . "✓\n" . $colors['reset'];
} else {
    echo " " . $colors['red'] . "✗\n" . $colors['reset'];
    echo $colors['red'] . "Error: PHP {$requiredVersion} or higher is required\n" . $colors['reset'];
    exit(1);
}

// ── Required extensions ───────────────────────────────────────────────────────
$requiredExtensions = ['pdo', 'json', 'mbstring'];
$missingExtensions  = array_filter($requiredExtensions, fn($e) => !extension_loaded($e));

if (!empty($missingExtensions)) {
    echo $colors['red'] . "Missing required extensions: " . implode(', ', $missingExtensions) . "\n" . $colors['reset'];
    exit(1);
}

// sqlite3 is used for VelocityCache — warn if missing but don't exit
if (!extension_loaded('sqlite3')) {
    echo $colors['yellow'] . "⚠  sqlite3 extension not loaded — VelocityCache (SQLite) will be disabled.\n" .
         "   Page caching will fall back to no-cache mode. Install php-sqlite3 to enable it.\n" . $colors['reset'];
}

// ── Recommended extensions ────────────────────────────────────────────────────
$recommendedExtensions = ['opcache', 'apcu'];
echo "\n" . $colors['blue'] . "Performance Extensions:\n" . $colors['reset'];

foreach ($recommendedExtensions as $ext) {
    $loaded = extension_loaded($ext);
    echo "  • " . ucfirst($ext) . ": ";
    echo $loaded
        ? $colors['green'] . "✓ enabled\n" . $colors['reset']
        : $colors['yellow'] . "– not installed (recommended for production)\n" . $colors['reset'];
}

// ── Port availability check ───────────────────────────────────────────────────
echo "\n" . $colors['blue'] . "Checking port availability...\n" . $colors['reset'];
$connection = @fsockopen('127.0.0.1', $port, $errno, $errstr, 1);

if (is_resource($connection)) {
    fclose($connection);
    echo $colors['red'] . "\nPort {$port} is already in use!\n" . $colors['reset'];
    echo "Try: php start.php <other-port>\n\n";

    $alternativePorts = [8080, 8888, 3000, 5000, 8002, 8003];
    echo $colors['cyan'] . "Available alternative ports:\n" . $colors['reset'];
    foreach ($alternativePorts as $altPort) {
        $altConn = @fsockopen($host, $altPort, $errno, $errstr, 1);
        if (!is_resource($altConn)) {
            echo $colors['green'] . "  ✓ Port {$altPort} is available\n" . $colors['reset'];
        } else {
            fclose($altConn);
        }
    }
    exit(1);
}

echo $colors['green'] . "✓ Port {$port} is available\n" . $colors['reset'];

// ── .env.velocity warning ──────────────────────────────────────────────────────
if (!file_exists(__DIR__ . '/.env.velocity')) {
    echo $colors['yellow'] . "\nNo .env.velocity file found — copy .env.velocity.example to .env.velocity first.\n" . $colors['reset'];
}

// ── Server info box ───────────────────────────────────────────────────────────
echo "\n";
echo $colors['green'] . $colors['bold'] . "✓ Starting Server...\n\n" . $colors['reset'];
echo $colors['blue'] . "┌────────────────────────────────────────────────────────────────┐\n" . $colors['reset'];
$rows = [
    "Framework"  => $colors['cyan'] . $colors['bold'] . "VelocityPHP v1.1.0" . $colors['reset'],
    "Mode"       => $colors['magenta'] . "Production" . $colors['reset'],
    "Local"      => $colors['green'] . $colors['bold'] . $serverUrl . $colors['reset'],
    "Network"    => $networkUrl
                        ? $colors['green'] . $colors['bold'] . $networkUrl . $colors['reset']
                        : $colors['yellow'] . "unavailable" . $colors['reset'],
    "Root"       => $colors['yellow'] . $documentRoot . $colors['reset'],
];
foreach ($rows as $label => $value) {
    echo $colors['blue'] . "│ " . $colors['reset'];
    echo str_pad($label . ":", 14) . $value . "\n";
}
echo $colors['blue'] . "└────────────────────────────────────────────────────────────────┘\n" . $colors['reset'];

// ── Quick links — auto-discovered from pages directory ───────────────────────
echo "\n" . $colors['cyan'] . $colors['bold'] . "Routes:\n" . $colors['reset'];
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
    echo "  " . $colors['blue'] . $serverUrl . $route . $colors['reset'] . "\n";
}

// ── Tips ──────────────────────────────────────────────────────────────────────
echo "\n" . $colors['yellow'] . $colors['bold'] . "Tips:\n" . $colors['reset'];
echo $colors['yellow'] . "  • For development with debug output: php dev.php\n" . $colors['reset'];
echo $colors['yellow'] . "  • Enable OPcache for 50-100% faster execution\n" . $colors['reset'];
echo $colors['yellow'] . "  • Set APP_ENV=production in .env.velocity for production mode\n" . $colors['reset'];

echo "\n" . $colors['cyan'] . "Press Ctrl+C to stop\n\n" . $colors['reset'];
echo $colors['green'] . $colors['bold'] . str_repeat("═", 66) . "\n\n" . $colors['reset'];

// ── Launch server ─────────────────────────────────────────────────────────────
chdir($documentRoot);
$command = sprintf(
    'php -S %s:%d -t %s',
    escapeshellarg('0.0.0.0'),
    $port,
    escapeshellarg($documentRoot)
);
passthru($command);
