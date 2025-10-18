<?php
/**
 * Local Development Server Starter
 * Starts the PHP built-in web server for development
 * 
 * @package NativeMVC
 */

// Configuration
$host = 'localhost';
$port = 8000;
$documentRoot = __DIR__ . '/public';

// Colors for terminal output (Windows compatible)
$colors = [
    'green' => "\033[32m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'cyan' => "\033[36m",
    'reset' => "\033[0m"
];

// Banner
echo "\n";
echo $colors['cyan'] . "╔═══════════════════════════════════════════════════════════════╗\n" . $colors['reset'];
echo $colors['cyan'] . "║                                                               ║\n" . $colors['reset'];
echo $colors['cyan'] . "║            🚀 NATIVE MVC DEVELOPMENT SERVER 🚀                ║\n" . $colors['reset'];
echo $colors['cyan'] . "║                                                               ║\n" . $colors['reset'];
echo $colors['cyan'] . "╚═══════════════════════════════════════════════════════════════╝\n" . $colors['reset'];
echo "\n";

// Check PHP version
$phpVersion = phpversion();
echo $colors['blue'] . "PHP Version: " . $colors['reset'] . $phpVersion . "\n";

if (version_compare($phpVersion, '7.4.0', '<')) {
    echo $colors['yellow'] . "⚠️  Warning: PHP 7.4 or higher is recommended\n" . $colors['reset'];
}

// Check if port is available
$connection = @fsockopen($host, $port);
if (is_resource($connection)) {
    fclose($connection);
    echo $colors['yellow'] . "\n⚠️  Port {$port} is already in use!\n" . $colors['reset'];
    echo "Please choose a different port or stop the service using port {$port}.\n\n";
    
    // Try alternative ports
    $alternativePorts = [8080, 8888, 3000, 5000];
    foreach ($alternativePorts as $altPort) {
        $altConnection = @fsockopen($host, $altPort);
        if (!is_resource($altConnection)) {
            echo $colors['green'] . "✓ Port {$altPort} is available!\n" . $colors['reset'];
            echo "Run: php start.php {$altPort}\n\n";
            exit(1);
        } else {
            fclose($altConnection);
        }
    }
    exit(1);
}

// Allow custom port from command line
if (isset($argv[1])) {
    $customPort = (int)$argv[1];
    if ($customPort > 0 && $customPort <= 65535) {
        $port = $customPort;
    }
}

// Server URL
$serverUrl = "http://{$host}:{$port}";

// Display server information
echo "\n";
echo $colors['green'] . "✓ Server Starting...\n" . $colors['reset'];
echo "\n";
echo $colors['blue'] . "┌─────────────────────────────────────────────────────────────┐\n" . $colors['reset'];
echo $colors['blue'] . "│ " . $colors['reset'] . "Document Root:  " . $colors['yellow'] . $documentRoot . $colors['reset'] . "\n";
echo $colors['blue'] . "│ " . $colors['reset'] . "Server Address: " . $colors['green'] . $serverUrl . $colors['reset'] . "\n";
echo $colors['blue'] . "│ " . $colors['reset'] . "Environment:    " . $colors['cyan'] . "Development" . $colors['reset'] . "\n";
echo $colors['blue'] . "└─────────────────────────────────────────────────────────────┘\n" . $colors['reset'];
echo "\n";

// Instructions
echo $colors['cyan'] . "Quick Links:\n" . $colors['reset'];
echo "  🏠 Home:      {$serverUrl}/\n";
echo "  📊 Dashboard: {$serverUrl}/dashboard\n";
echo "  👥 Users:     {$serverUrl}/users\n";
echo "  ℹ️  About:     {$serverUrl}/about\n";
echo "\n";

echo $colors['yellow'] . "Press Ctrl+C to stop the server\n" . $colors['reset'];
echo "\n";
echo $colors['green'] . "════════════════════════════════════════════════════════════════\n" . $colors['reset'];
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
