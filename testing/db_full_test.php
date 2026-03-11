<?php
/**
 * VelocityPHP — Full Database Test Suite
 *
 * Tests 10 real operations against the live MySQL database:
 *   1.  DB connection (DSN built from .env.velocity)
 *   2.  Create test table via raw PDO
 *   3.  INSERT a row
 *   4.  SELECT the row back and verify data
 *   5.  UPDATE the row and verify
 *   6.  DELETE the row and verify
 *   7.  Transaction commit (insert + verify)
 *   8.  Transaction rollback (insert reverted)
 *   9.  MigrationManager::run() — users & remember_tokens tables
 *  10.  UserModel CRUD via BaseModel (insert, find, update, delete)
 *
 * Usage:
 *   php testing/db_full_test.php
 *
 * Exit codes: 0 = all passed, 1 = one or more failed
 */

// ── Bootstrap ─────────────────────────────────────────────────────────────────
define('BASE_PATH',   dirname(__DIR__));
define('SRC_PATH',    BASE_PATH . '/src');
define('CONFIG_PATH', SRC_PATH  . '/config');
define('PUBLIC_PATH', BASE_PATH . '/public');

// Load .env.velocity
foreach ([BASE_PATH . '/.env.velocity', BASE_PATH . '/.env'] as $envFile) {
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                [$k, $v] = explode('=', $line, 2);
                $k = trim($k);
                $v = trim($v, " \t\"'");
                if (!array_key_exists($k, $_ENV)) {
                    putenv("{$k}={$v}");
                    $_ENV[$k] = $v;
                    $_SERVER[$k] = $v;
                }
            }
        }
        break;
    }
}

require_once SRC_PATH . '/utils/Autoloader.php';
\App\Utils\Autoloader::register();

// Migration classes live in a single file; require it explicitly because
// MigrationManager is not a standalone file the autoloader can discover.
require_once SRC_PATH . '/database/Migration.php';

// ── Output helpers ─────────────────────────────────────────────────────────────
$passed = 0;
$failed = 0;

function pass(string $label): void {
    global $passed;
    $passed++;
    echo "\033[32m  [PASS]\033[0m {$label}\n";
}
function fail(string $label, string $reason = ''): void {
    global $failed;
    $failed++;
    $suffix = $reason ? " — {$reason}" : '';
    echo "\033[31m  [FAIL]\033[0m {$label}{$suffix}\n";
}
function section(int $n, string $title): void {
    echo "\n\033[1m\033[36mTest {$n}: {$title}\033[0m\n";
}

// ─────────────────────────────────────────────────────────────────────────────
// ── TEST 1: DB Connection ────────────────────────────────────────────────────
// ─────────────────────────────────────────────────────────────────────────────
section(1, 'Database connection (PDO)');

$pdo = null;
try {
    $driver   = getenv('DB_CONNECTION') ?: 'mysql';
    $host     = getenv('DB_HOST')       ?: 'localhost';
    $port     = getenv('DB_PORT')       ?: '3306';
    $dbName   = getenv('DB_NAME')       ?: 'velocity';
    $user     = getenv('DB_USER')       ?: 'root';
    $pass     = getenv('DB_PASS')       ?: '';

    $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec("SET NAMES utf8mb4");

    // Report what we connected to
    $serverVer = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    pass("Connected to MySQL {$serverVer} — db={$dbName} user={$user}@{$host}:{$port}");
} catch (\Throwable $e) {
    fail('PDO connect', $e->getMessage());
    echo "\n\033[33m  Cannot continue without a DB connection. Aborting.\033[0m\n\n";
    exit(1);
}

// ─────────────────────────────────────────────────────────────────────────────
// ── TEST 2: Create test table ────────────────────────────────────────────────
// ─────────────────────────────────────────────────────────────────────────────
section(2, 'CREATE TABLE velocity_test_run');

$testTable = 'velocity_test_run';
try {
    $pdo->exec("DROP TABLE IF EXISTS `{$testTable}`");
    $pdo->exec("
        CREATE TABLE `{$testTable}` (
            `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `name`       VARCHAR(100) NOT NULL,
            `score`      DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    // Verify the table really exists
    $check = $pdo->query("SHOW TABLES LIKE '{$testTable}'")->fetchColumn();
    if ($check === $testTable) {
        pass("Table `{$testTable}` created successfully");
    } else {
        fail("Table `{$testTable}` not found after CREATE");
    }
} catch (\Throwable $e) {
    fail('CREATE TABLE', $e->getMessage());
}

// ─────────────────────────────────────────────────────────────────────────────
// ── TEST 3: INSERT ───────────────────────────────────────────────────────────
// ─────────────────────────────────────────────────────────────────────────────
section(3, 'INSERT a row');

$insertedId = null;
try {
    $stmt = $pdo->prepare("INSERT INTO `{$testTable}` (name, score) VALUES (?, ?)");
    $stmt->execute(['VelocityPHP', 9.99]);
    $insertedId = (int)$pdo->lastInsertId();
    if ($insertedId > 0) {
        pass("Row inserted — id={$insertedId}");
    } else {
        fail('INSERT', 'lastInsertId() returned 0');
    }
} catch (\Throwable $e) {
    fail('INSERT', $e->getMessage());
}

// ─────────────────────────────────────────────────────────────────────────────
// ── TEST 4: SELECT & verify data ─────────────────────────────────────────────
// ─────────────────────────────────────────────────────────────────────────────
section(4, 'SELECT row back and verify data');

try {
    $stmt = $pdo->prepare("SELECT * FROM `{$testTable}` WHERE id = ?");
    $stmt->execute([$insertedId]);
    $row = $stmt->fetch();
    if ($row && $row['name'] === 'VelocityPHP' && (float)$row['score'] === 9.99) {
        pass("Row fetched — name='{$row['name']}' score={$row['score']}");
    } else {
        fail('SELECT', 'Row not found or data mismatch: ' . json_encode($row));
    }
} catch (\Throwable $e) {
    fail('SELECT', $e->getMessage());
}

// ─────────────────────────────────────────────────────────────────────────────
// ── TEST 5: UPDATE ───────────────────────────────────────────────────────────
// ─────────────────────────────────────────────────────────────────────────────
section(5, 'UPDATE the row and verify');

try {
    $stmt = $pdo->prepare("UPDATE `{$testTable}` SET name = ?, score = ? WHERE id = ?");
    $stmt->execute(['VelocityPHP-Updated', 10.00, $insertedId]);
    $affected = $stmt->rowCount();

    $stmt2 = $pdo->prepare("SELECT name, score FROM `{$testTable}` WHERE id = ?");
    $stmt2->execute([$insertedId]);
    $row = $stmt2->fetch();

    if ($affected === 1 && $row['name'] === 'VelocityPHP-Updated' && (float)$row['score'] === 10.00) {
        pass("Row updated — name='{$row['name']}' score={$row['score']}");
    } else {
        fail('UPDATE', "affected={$affected} row=" . json_encode($row));
    }
} catch (\Throwable $e) {
    fail('UPDATE', $e->getMessage());
}

// ─────────────────────────────────────────────────────────────────────────────
// ── TEST 6: DELETE ───────────────────────────────────────────────────────────
// ─────────────────────────────────────────────────────────────────────────────
section(6, 'DELETE the row and verify');

try {
    $stmt = $pdo->prepare("DELETE FROM `{$testTable}` WHERE id = ?");
    $stmt->execute([$insertedId]);
    $affected = $stmt->rowCount();

    $stmt2 = $pdo->prepare("SELECT id FROM `{$testTable}` WHERE id = ?");
    $stmt2->execute([$insertedId]);
    $gone = $stmt2->fetch();

    if ($affected === 1 && $gone === false) {
        pass("Row deleted — id={$insertedId} no longer exists");
    } else {
        fail('DELETE', "affected={$affected} row still exists: " . json_encode($gone));
    }
} catch (\Throwable $e) {
    fail('DELETE', $e->getMessage());
}

// ─────────────────────────────────────────────────────────────────────────────
// ── TEST 7: Transaction COMMIT ───────────────────────────────────────────────
// ─────────────────────────────────────────────────────────────────────────────
section(7, 'Transaction COMMIT');

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO `{$testTable}` (name, score) VALUES (?, ?)");
    $stmt->execute(['TxCommit', 7.77]);
    $txId = (int)$pdo->lastInsertId();
    $pdo->commit();

    // Verify persisted after commit
    $stmt2 = $pdo->prepare("SELECT name FROM `{$testTable}` WHERE id = ?");
    $stmt2->execute([$txId]);
    $row = $stmt2->fetch();

    if ($row && $row['name'] === 'TxCommit') {
        pass("Transaction committed — id={$txId} name='{$row['name']}'");
    } else {
        fail('COMMIT', 'Row not found after commit');
    }
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fail('COMMIT', $e->getMessage());
}

// ─────────────────────────────────────────────────────────────────────────────
// ── TEST 8: Transaction ROLLBACK ─────────────────────────────────────────────
// ─────────────────────────────────────────────────────────────────────────────
section(8, 'Transaction ROLLBACK');

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO `{$testTable}` (name, score) VALUES (?, ?)");
    $stmt->execute(['TxRollback', 8.88]);
    $txId = (int)$pdo->lastInsertId();
    $pdo->rollBack();

    // Verify NOT persisted after rollback
    $stmt2 = $pdo->prepare("SELECT id FROM `{$testTable}` WHERE id = ?");
    $stmt2->execute([$txId]);
    $row = $stmt2->fetch();

    if ($row === false) {
        pass("Transaction rolled back — id={$txId} was not persisted");
    } else {
        fail('ROLLBACK', 'Row still exists after rollback: ' . json_encode($row));
    }
} catch (\Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fail('ROLLBACK', $e->getMessage());
}

// ─────────────────────────────────────────────────────────────────────────────
// ── TEST 9: MigrationManager — run() creates users & remember_tokens ─────────
// ─────────────────────────────────────────────────────────────────────────────
section(9, 'MigrationManager::run() — create users & remember_tokens tables');

try {
    // Drop existing app tables so we test a real fresh run
    $pdo->exec("DROP TABLE IF EXISTS `remember_tokens`");
    $pdo->exec("DROP TABLE IF EXISTS `users`");
    $pdo->exec("DROP TABLE IF EXISTS `migrations`");

    $manager = new \App\Database\MigrationManager($pdo);
    $result  = $manager->run();

    if (isset($result['message'])) {
        // Already ran — shouldn't happen since we dropped above
        fail('MigrationManager::run()', 'Unexpected: ' . $result['message']);
    } else {
        $ran = $result['ran'] ?? [];
        $batch = $result['batch'] ?? '?';

        // Verify both tables physically exist
        $usersExist  = $pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn() === 'users';
        $tokensExist = $pdo->query("SHOW TABLES LIKE 'remember_tokens'")->fetchColumn() === 'remember_tokens';

        if ($usersExist && $tokensExist) {
            pass("Migrations ran (batch #{$batch}): " . implode(', ', $ran));
            pass("  Table `users` exists");
            pass("  Table `remember_tokens` exists");
        } else {
            $missing = array_filter(['users' => !$usersExist, 'remember_tokens' => !$tokensExist]);
            fail('MigrationManager::run()', 'Missing tables: ' . implode(', ', array_keys($missing)));
        }
    }
} catch (\Throwable $e) {
    fail('MigrationManager::run()', $e->getMessage());
}

// ─────────────────────────────────────────────────────────────────────────────
// ── TEST 10: UserModel CRUD via BaseModel ────────────────────────────────────
// ─────────────────────────────────────────────────────────────────────────────
section(10, 'UserModel CRUD via BaseModel (insert → find → update → delete)');

try {
    $model = new \App\Models\UserModel();

    // ── INSERT ───────────────────────────────────────────────────────────────
    $email = 'test_' . uniqid() . '@velocity.test';
    $newId = $model->create([
        'name'     => 'Test Runner',
        'email'    => $email,
        'password' => password_hash('secret123', PASSWORD_BCRYPT),
        'role'     => 'user',
        'status'   => 'active',
    ]);

    if ($newId && (int)$newId > 0) {
        pass("  UserModel::create() — id={$newId} email={$email}");
    } else {
        fail('UserModel::create()', 'No ID returned');
    }

    // ── FIND ─────────────────────────────────────────────────────────────────
    $user = $model->find($newId);
    if ($user && $user['email'] === $email && $user['name'] === 'Test Runner') {
        pass("  UserModel::find({$newId}) — name='{$user['name']}' role='{$user['role']}'");
    } else {
        fail("UserModel::find({$newId})", 'Row not found or data wrong: ' . json_encode($user));
    }

    // ── UPDATE ───────────────────────────────────────────────────────────────
    $ok = $model->update($newId, ['name' => 'Updated Runner', 'role' => 'admin']);
    $updated = $model->find($newId);
    if ($ok && $updated && $updated['name'] === 'Updated Runner' && $updated['role'] === 'admin') {
        pass("  UserModel::update({$newId}) — name='{$updated['name']}' role='{$updated['role']}'");
    } else {
        fail("UserModel::update({$newId})", 'Update failed or data wrong: ' . json_encode($updated));
    }

    // ── DELETE ───────────────────────────────────────────────────────────────
    $del = $model->delete($newId);
    $gone = $model->find($newId);
    if ($del && $gone === false) {
        pass("  UserModel::delete({$newId}) — row gone");
    } else {
        fail("UserModel::delete({$newId})", 'Row still exists: ' . json_encode($gone));
    }

} catch (\Throwable $e) {
    fail('UserModel CRUD', $e->getMessage());
}

// ── Cleanup: drop the scratch table ──────────────────────────────────────────
try {
    $pdo->exec("DROP TABLE IF EXISTS `{$testTable}`");
} catch (\Throwable $e) {
    // best-effort
}

// ── Summary ───────────────────────────────────────────────────────────────────
$total = $passed + $failed;
echo "\n" . str_repeat('─', 50) . "\n";
echo "\033[1mResults: {$passed}/{$total} passed";
if ($failed > 0) {
    echo "  \033[31m({$failed} FAILED)\033[0m\n";
    exit(1);
} else {
    echo " \033[32m— All tests passed!\033[0m\n";
    exit(0);
}
