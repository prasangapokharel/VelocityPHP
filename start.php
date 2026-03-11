<?php
/**
 * VelocityPHP Production Server Launcher
 *
 * Starts the PHP built-in server in production-simulation mode.
 * For development with debug output, use: php dev.php
 *
 * Usage:
 *   php start.php            # Start on default port 8001
 *   php start.php 9000       # Start on custom port
 *
 * @package VelocityPHP
 * @version 1.1.0
 */

// ── Configuration ─────────────────────────────────────────────────────────────
$host         = 'localhost';
$port         = isset($argv[1]) && ctype_digit($argv[1]) ? (int) $argv[1] : 8001;
$documentRoot = __DIR__ . '/public';
$serverUrl    = "http://{$host}:{$port}";

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
$requiredExtensions = ['pdo', 'json', 'mbstring', 'sqlite3'];
$missingExtensions  = array_filter($requiredExtensions, fn($e) => !extension_loaded($e));

if (!empty($missingExtensions)) {
    echo $colors['red'] . "Missing required extensions: " . implode(', ', $missingExtensions) . "\n" . $colors['reset'];
    exit(1);
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
$connection = @fsockopen($host, $port, $errno, $errstr, 1);

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

// ── .env warning ──────────────────────────────────────────────────────────────
if (!file_exists(__DIR__ . '/.env')) {
    echo $colors['yellow'] . "\nNo .env file found — copy .env.example to .env first.\n" . $colors['reset'];
}

// ── Server info box ───────────────────────────────────────────────────────────
echo "\n";
echo $colors['green'] . $colors['bold'] . "✓ Starting Server...\n\n" . $colors['reset'];
echo $colors['blue'] . "┌────────────────────────────────────────────────────────────────┐\n" . $colors['reset'];
$rows = [
    "Framework"  => $colors['cyan'] . $colors['bold'] . "VelocityPHP v1.1.0" . $colors['reset'],
    "Mode"       => $colors['magenta'] . "Production" . $colors['reset'],
    "URL"        => $colors['green'] . $colors['bold'] . $serverUrl . $colors['reset'],
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
echo $colors['yellow'] . "  • Set APP_ENV=production in .env for production mode\n" . $colors['reset'];

echo "\n" . $colors['cyan'] . "Press Ctrl+C to stop\n\n" . $colors['reset'];
echo $colors['green'] . $colors['bold'] . str_repeat("═", 66) . "\n\n" . $colors['reset'];

// ── Launch server ─────────────────────────────────────────────────────────────
chdir($documentRoot);
$command = sprintf(
    'php -S %s:%d -t %s',
    escapeshellarg($host),
    $port,
    escapeshellarg($documentRoot)
);
passthru($command);


// Configuration
$host = 'localhost';
$port = 8001;
$documentRoot = __DIR__ . '/public';

// Colors for terminal output (Windows compatible)
$colors = [
    'green' => "\033[32m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'cyan' => "\033[36m",
    'magenta' => "\033[35m",
    'red' => "\033[31m",
    'bold' => "\033[1m",
    'reset' => "\033[0m"
];

// Clear screen for cleaner output
echo "\033[2J\033[H";

// Banner
echo "\n";
echo $colors['cyan'] . $colors['bold'];
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                                                                ║\n";
echo "║                  ⚡ VELOCITYPHP FRAMEWORK ⚡                    ║\n";
echo "║                                                                ║\n";
echo "║              Ultra-Fast Production-Ready Framework            ║\n";
echo "║                                                                ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo $colors['reset'];
echo "\n";

// Check PHP version
$phpVersion = phpversion();
$requiredVersion = '7.4.0';
echo $colors['blue'] . "PHP Version: " . $colors['reset'] . $phpVersion;

if (version_compare($phpVersion, $requiredVersion, '>=')) {
    echo " " . $colors['green'] . "✓\n" . $colors['reset'];
} else {
    echo " " . $colors['red'] . "✗\n" . $colors['reset'];
    echo $colors['red'] . "⚠️  Error: PHP {$requiredVersion} or higher is required\n" . $colors['reset'];
    exit(1);
}

// Check required extensions
$requiredExtensions = ['pdo', 'json', 'mbstring'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

if (!empty($missingExtensions)) {
    echo $colors['red'] . "⚠️  Missing required extensions: " . implode(', ', $missingExtensions) . "\n" . $colors['reset'];
    exit(1);
}

// Check recommended extensions
$recommendedExtensions = ['opcache', 'apcu'];
echo "\n" . $colors['blue'] . "Performance Extensions:\n" . $colors['reset'];

foreach ($recommendedExtensions as $ext) {
    $loaded = extension_loaded($ext);
    echo "  • " . ucfirst($ext) . ": ";
    if ($loaded) {
        echo $colors['green'] . "✓ enabled\n" . $colors['reset'];
    } else {
        echo $colors['yellow'] . "✗ not installed (recommended for production)\n" . $colors['reset'];
    }
}

// Check if port is available
echo "\n" . $colors['blue'] . "Checking port availability...\n" . $colors['reset'];
$connection = @fsockopen($host, $port);

if (is_resource($connection)) {
    fclose($connection);
    echo $colors['red'] . "\n⚠️  Port {$port} is already in use!\n" . $colors['reset'];
    echo "Please choose a different port or stop the service using port {$port}.\n\n";
    
    // Try alternative ports
    $alternativePorts = [8080, 8888, 3000, 5000, 8002, 8003];
    echo $colors['cyan'] . "Available alternative ports:\n" . $colors['reset'];
    
    foreach ($alternativePorts as $altPort) {
        $altConnection = @fsockopen($host, $altPort);
        if (!is_resource($altConnection)) {
            echo $colors['green'] . "  ✓ Port {$altPort} is available\n" . $colors['reset'];
            echo $colors['cyan'] . "  Run: " . $colors['yellow'] . "php start.php {$altPort}\n\n" . $colors['reset'];
        } else {
            fclose($altConnection);
        }
    }
    exit(1);
}

echo $colors['green'] . "✓ Port {$port} is available\n" . $colors['reset'];

// Allow custom port from command line
if (isset($argv[1])) {
    $customPort = (int)$argv[1];
    if ($customPort > 0 && $customPort <= 65535) {
        $port = $customPort;
        echo $colors['cyan'] . "Using custom port: {$port}\n" . $colors['reset'];
    }
}

// Server URL
$serverUrl = "http://{$host}:{$port}";

// Display server information
echo "\n";
echo $colors['green'] . $colors['bold'] . "✓ Server Starting...\n" . $colors['reset'];
echo "\n";
echo $colors['blue'] . "┌────────────────────────────────────────────────────────────────┐\n" . $colors['reset'];
echo $colors['blue'] . "│ " . $colors['reset'];
echo "Framework:      " . $colors['cyan'] . $colors['bold'] . "VelocityPhp v1.0.0" . $colors['reset'] . "\n";
echo $colors['blue'] . "│ " . $colors['reset'];
echo "Document Root:  " . $colors['yellow'] . $documentRoot . $colors['reset'] . "\n";
echo $colors['blue'] . "│ " . $colors['reset'];
echo "Server Address: " . $colors['green'] . $colors['bold'] . $serverUrl . $colors['reset'] . "\n";
echo $colors['blue'] . "│ " . $colors['reset'];
echo "Environment:    " . $colors['magenta'] . "Development" . $colors['reset'] . "\n";
echo $colors['blue'] . "└────────────────────────────────────────────────────────────────┘\n" . $colors['reset'];
echo "\n";

// Instructions
echo $colors['cyan'] . $colors['bold'] . "Quick Links:\n" . $colors['reset'];
echo "  🏠 Home:      " . $colors['blue'] . "{$serverUrl}/" . $colors['reset'] . "\n";
echo "  📊 Dashboard: " . $colors['blue'] . "{$serverUrl}/dashboard" . $colors['reset'] . "\n";
echo "  👥 Users:     " . $colors['blue'] . "{$serverUrl}/users" . $colors['reset'] . "\n";
echo "  📝 About:     " . $colors['blue'] . "{$serverUrl}/about" . $colors['reset'] . "\n";
echo "\n";

// Performance tips
echo $colors['yellow'] . $colors['bold'] . "⚡ Performance Tips:\n" . $colors['reset'];
echo $colors['yellow'] . "  • Enable OPcache for production (50-100% faster)\n" . $colors['reset'];
echo $colors['yellow'] . "  • Use APCu for enhanced caching\n" . $colors['reset'];
echo $colors['yellow'] . "  • Set APP_ENV=production in .env for production mode\n" . $colors['reset'];
echo "\n";

echo $colors['cyan'] . "Press " . $colors['bold'] . "Ctrl+C" . $colors['reset'] . $colors['cyan'] . " to stop the server\n" . $colors['reset'];
echo "\n";
echo $colors['green'] . $colors['bold'] . "════════════════════════════════════════════════════════════════\n" . $colors['reset'];
echo "\n";

// Change to public directory
chdir($documentRoot);

// Start server command
$command = sprintf(
    'php -S %s:%d -t %s',
    escapeshellarg($host),
    $port,
    escapeshellarg($documentRoot)
);

// Execute server
passthru($command);
