<?php
/**
 * VelocityPHP Migration CLI
 *
 * Usage:
 *   php migrate                  # Run all pending migrations
 *   php migrate rollback         # Roll back the last batch
 *   php migrate rollback 3       # Roll back the last 3 migrations
 *   php migrate status           # Show migration status
 *   php migrate fresh            # Drop all tables and re-run all migrations
 *
 * @package VelocityPHP
 * @version 1.1.0
 */

// ── Bootstrap ─────────────────────────────────────────────────────────────────

define('BASE_PATH',   __DIR__);
define('SRC_PATH',    BASE_PATH . '/src');
define('CONFIG_PATH', SRC_PATH  . '/config');

// Load .env.velocity (with .env fallback)
foreach ([BASE_PATH . '/.env.velocity', BASE_PATH . '/.env'] as $envFile) {
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                [$name, $value] = explode('=', $line, 2);
                $name  = trim($name);
                $value = trim($value, " \t\"'");
                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    putenv("{$name}={$value}");
                    $_ENV[$name]    = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
        break;
    }
}

// Autoload
require_once SRC_PATH . '/utils/Autoloader.php';
\App\Utils\Autoloader::register();

// ── Output helpers ────────────────────────────────────────────────────────────
define('CLR_GREEN',  "\033[32m");
define('CLR_YELLOW', "\033[33m");
define('CLR_RED',    "\033[31m");
define('CLR_CYAN',   "\033[36m");
define('CLR_BOLD',   "\033[1m");
define('CLR_RESET',  "\033[0m");

function mig_out(string $text): void    { echo $text . "\n"; }
function mig_success(string $text): void { echo CLR_GREEN  . '✓ ' . $text . CLR_RESET . "\n"; }
function mig_warn(string $text): void    { echo CLR_YELLOW . '! ' . $text . CLR_RESET . "\n"; }
function mig_fail(string $text): void    { echo CLR_RED    . '✗ ' . $text . CLR_RESET . "\n"; }
function mig_info(string $text): void    { echo CLR_CYAN   . '  ' . $text . CLR_RESET . "\n"; }

// ── Build database connection ─────────────────────────────────────────────────
try {
    $dbConfig = require CONFIG_PATH . '/database.php';
    $driver   = $dbConfig['default'];
    $config   = $dbConfig['connections'][$driver];

    switch ($driver) {
        case 'mysql':
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $config['host'], $config['port'], $config['database'], $config['charset'] ?? 'utf8mb4');
            break;
        case 'pgsql':
            $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s',
                $config['host'], $config['port'], $config['database']);
            break;
        case 'sqlite':
            $dsn = 'sqlite:' . $config['database'];
            break;
        default:
            mig_fail("Unsupported driver: {$driver}");
            exit(1);
    }

    $pdo = new PDO(
        $dsn,
        $config['username'] ?? null,
        $config['password'] ?? null,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    if ($driver === 'mysql') {
        $pdo->exec("SET NAMES utf8mb4");
    }

} catch (\Exception $e) {
    mig_fail("Database connection failed: " . $e->getMessage());
    exit(1);
}

// ── Migration manager ─────────────────────────────────────────────────────────

$manager = new \App\Database\MigrationManager($pdo);

// ── Parse command ─────────────────────────────────────────────────────────────
$command = $argv[1] ?? 'run';
$arg2    = $argv[2] ?? null;

mig_out('');
mig_out(CLR_BOLD . CLR_CYAN . '⚡ VelocityPHP Migrations' . CLR_RESET);
mig_out(str_repeat('─', 50));

switch ($command) {
    // ── php migrate ───────────────────────────────────────────────────────────
    case 'run':
    default:
        mig_out("Running pending migrations…");
        try {
            $result = $manager->run();
            if (isset($result['message'])) {
                mig_warn($result['message']);
            } else {
                foreach ($result['ran'] as $m) {
                    mig_success($m);
                }
                mig_out('');
                mig_info("Batch #{$result['batch']} — " . count($result['ran']) . " migration(s) ran.");
            }
        } catch (\Exception $e) {
            mig_fail($e->getMessage());
            exit(1);
        }
        break;

    // ── php migrate rollback [steps] ──────────────────────────────────────────
    case 'rollback':
        $steps = (int)($arg2 ?? 1);
        mig_out("Rolling back {$steps} migration(s)…");
        try {
            $result = $manager->rollback($steps);
            if (isset($result['message'])) {
                mig_warn($result['message']);
            } else {
                foreach ($result['rolled_back'] as $m) {
                    mig_success("Rolled back: {$m}");
                }
                mig_out('');
                mig_info(count($result['rolled_back']) . " migration(s) rolled back.");
            }
        } catch (\Exception $e) {
            mig_fail($e->getMessage());
            exit(1);
        }
        break;

    // ── php migrate status ────────────────────────────────────────────────────
    case 'status':
        mig_out("Migration status:");
        mig_out('');
        try {
            $rows = $manager->status();
            if (empty($rows)) {
                mig_warn("No migration files found in database/migrations/");
            } else {
                foreach ($rows as $row) {
                    $statusLabel = $row['ran']
                        ? CLR_GREEN  . '[ran]    ' . CLR_RESET
                        : CLR_YELLOW . '[pending]' . CLR_RESET;
                    mig_out("  {$statusLabel} {$row['migration']}");
                }
            }
        } catch (\Exception $e) {
            mig_fail($e->getMessage());
            exit(1);
        }
        break;

    // ── php migrate fresh ─────────────────────────────────────────────────────
    case 'fresh':
        mig_out(CLR_RED . "WARNING: This will drop ALL tables and re-run every migration." . CLR_RESET);
        mig_out("Type 'yes' to continue: ");
        $confirm = trim(fgets(STDIN));
        if ($confirm !== 'yes') {
            mig_warn("Aborted.");
            exit(0);
        }
        mig_out("Rolling back all migrations…");
        try {
            $maxPasses = 20;
            for ($i = 0; $i < $maxPasses; $i++) {
                $result = $manager->rollback(999);
                if (isset($result['message'])) break; // nothing left to roll back
            }
            mig_success("All tracked migrations rolled back.");
            mig_out("Running all migrations…");
            $result = $manager->run();
            if (isset($result['message'])) {
                mig_warn($result['message']);
            } else {
                foreach ($result['ran'] as $m) {
                    mig_success($m);
                }
                mig_info("Batch #{$result['batch']} — " . count($result['ran']) . " migration(s) ran.");
            }
        } catch (\Exception $e) {
            mig_fail($e->getMessage());
            exit(1);
        }
        break;
}

mig_out('');
