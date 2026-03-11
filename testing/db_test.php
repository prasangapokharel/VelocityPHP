<?php
/**
 * VelocityPHP — Database Connection Test
 * Usage: php testing/db_test.php
 *
 * Reads .env.velocity from the project root, attempts a PDO connection,
 * and reports success or failure with clear diagnostics.
 */

define('BASE_PATH', dirname(__DIR__));

// ─── Load .env.velocity ───────────────────────────────────────────────────────
$envFile = BASE_PATH . '/.env.velocity';
if (!file_exists($envFile)) {
    die("[FAIL] .env.velocity not found at: {$envFile}\n");
}

$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$env = [];
foreach ($lines as $line) {
    if (strpos(trim($line), '#') === 0) continue;
    if (strpos($line, '=') !== false) {
        [$key, $val] = explode('=', $line, 2);
        $env[trim($key)] = trim(trim($val), '"\'');
    }
}

$driver = $env['DB_CONNECTION'] ?? 'mysql';
$host   = $env['DB_HOST']       ?? 'localhost';
$port   = $env['DB_PORT']       ?? '3306';
$dbname = $env['DB_NAME']       ?? '';
$user   = $env['DB_USER']       ?? '';
$pass   = $env['DB_PASS']       ?? '';

echo "─────────────────────────────────────────\n";
echo "  VelocityPHP DB Connection Test\n";
echo "─────────────────────────────────────────\n";
echo "  Driver : {$driver}\n";
echo "  Host   : {$host}:{$port}\n";
echo "  DB     : {$dbname}\n";
echo "  User   : {$user}\n";
echo "─────────────────────────────────────────\n";

// ─── Attempt PDO connection ───────────────────────────────────────────────────
$dsn = "{$driver}:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT            => 5,
    ]);

    echo "  [OK]  Connected successfully!\n";

    // Server version
    $version = $pdo->query('SELECT VERSION() AS v')->fetchColumn();
    echo "  [OK]  Server version : {$version}\n";

    // List tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($tables)) {
        echo "  [WARN] No tables found. Have you run migrate.php?\n";
    } else {
        echo "  [OK]  Tables found  : " . implode(', ', $tables) . "\n";

        // Row counts for known tables
        foreach ($tables as $tbl) {
            $count = $pdo->query("SELECT COUNT(*) FROM `{$tbl}`")->fetchColumn();
            echo "         {$tbl}: {$count} rows\n";
        }
    }

} catch (PDOException $e) {
    echo "  [FAIL] Connection failed!\n";
    echo "  Error : " . $e->getMessage() . "\n";

    $code = $e->getCode();
    if ($code == 2002 || strpos($e->getMessage(), 'Connection refused') !== false) {
        echo "  Hint  : MySQL server is not running or the host/port is wrong.\n";
    } elseif ($code == 1045) {
        echo "  Hint  : Wrong DB_USER or DB_PASS in .env.velocity.\n";
    } elseif ($code == 1049) {
        echo "  Hint  : Database '{$dbname}' does not exist. Create it first:\n";
        echo "          CREATE DATABASE `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
    }

    exit(1);
}

echo "─────────────────────────────────────────\n";
echo "  All checks passed. DB is ready.\n";
echo "─────────────────────────────────────────\n";
exit(0);
