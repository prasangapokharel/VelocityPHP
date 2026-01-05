<?php
/**
 * VelocityPhp Development Server
 * Simple, clean local development environment
 * 
 * Usage: php start.php [port]
 * 
 * @package VelocityPhp
 * @version 1.0.0
 */

// Configuration
$host = '0.0.0.0';
$port = 8001;
$documentRoot = __DIR__ . '/public';
$routerFile = __DIR__ . '/router.php';

// Allow custom port from command line
if (isset($argv[1]) && is_numeric($argv[1])) {
    $port = (int)$argv[1];
}

/**
 * Get network IP address (Windows compatible, no socket extension required)
 */
function getNetworkIP(): ?string
{
    // Method 1: Windows ipconfig
    if (PHP_OS_FAMILY === 'Windows') {
        $output = @shell_exec('ipconfig 2>nul');
        if ($output && preg_match_all('/IPv4[^:]*:\s*(\d+\.\d+\.\d+\.\d+)/i', $output, $matches)) {
            foreach ($matches[1] as $ip) {
                if ($ip !== '127.0.0.1' && strpos($ip, '192.168.') === 0) {
                    return $ip;
                }
            }
            foreach ($matches[1] as $ip) {
                if ($ip !== '127.0.0.1' && strpos($ip, '10.') === 0) {
                    return $ip;
                }
            }
            foreach ($matches[1] as $ip) {
                if ($ip !== '127.0.0.1') {
                    return $ip;
                }
            }
        }
    } else {
        // Linux/Mac
        $output = @shell_exec('hostname -I 2>/dev/null');
        if ($output) {
            $ips = explode(' ', trim($output));
            foreach ($ips as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && $ip !== '127.0.0.1') {
                    return $ip;
                }
            }
        }
    }
    
    // Method 2: gethostbyname fallback
    $hostname = @gethostname();
    if ($hostname) {
        $ip = @gethostbyname($hostname);
        if ($ip !== $hostname && $ip !== '127.0.0.1') {
            return $ip;
        }
    }
    
    return null;
}

// Clear screen
echo "\033[2J\033[H";

// Simple banner
echo "\n";
echo "\033[1;36m";
echo "  ╔═══════════════════════════════════════════════════════╗\n";
echo "  ║           VELOCITYPHP DEVELOPMENT SERVER              ║\n";
echo "  ╚═══════════════════════════════════════════════════════╝\n";
echo "\033[0m\n";

// Check PHP version
echo "  PHP Version: " . phpversion();
if (version_compare(phpversion(), '7.4.0', '>=')) {
    echo " \033[32m✓\033[0m\n";
} else {
    echo " \033[31m✗ (7.4+ required)\033[0m\n";
    exit(1);
}

// Check if port is in use
$socket = @fsockopen('127.0.0.1', $port, $errno, $errstr, 1);
if ($socket) {
    fclose($socket);
    echo "\n\033[31m  ✗ Port {$port} is already in use!\033[0m\n\n";
    echo "  Solutions:\n";
    echo "    1. Kill the process: \033[33mnpx kill-port {$port}\033[0m\n";
    echo "    2. Use different port: \033[33mphp start.php 8080\033[0m\n\n";
    
    // Show available ports
    $altPorts = [8080, 8888, 3000, 5000];
    foreach ($altPorts as $alt) {
        $test = @fsockopen('127.0.0.1', $alt, $e, $es, 1);
        if (!$test) {
            echo "    → Port {$alt} is available\n";
        } else {
            fclose($test);
        }
    }
    echo "\n";
    exit(1);
}

// Get network IP
$networkIP = getNetworkIP();

// Display URLs
echo "\n";
echo "  \033[1;32m✓ Server Ready\033[0m\n\n";
echo "  \033[1mLocal:\033[0m    \033[36mhttp://localhost:{$port}\033[0m\n";
if ($networkIP) {
    echo "  \033[1mNetwork:\033[0m  \033[36mhttp://{$networkIP}:{$port}\033[0m\n";
}
echo "\n";
echo "  \033[90mDocument Root: {$documentRoot}\033[0m\n";
echo "  \033[90mPress Ctrl+C to stop\033[0m\n";
echo "\n";
echo "  \033[33m───────────────────────────────────────────────────────\033[0m\n\n";

// Build and run command
$cmd = file_exists($routerFile)
    ? sprintf('php -S %s:%d -t "%s" "%s"', $host, $port, $documentRoot, $routerFile)
    : sprintf('php -S %s:%d -t "%s"', $host, $port, $documentRoot);

passthru($cmd);
