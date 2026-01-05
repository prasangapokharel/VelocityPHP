<?php
/**
 * VelocityPhp Database Seeder CLI
 * Run: php seed.php [command]
 * 
 * Commands:
 *   all       - Run all seeders (default)
 *   users     - Seed only users
 *   posts     - Seed only posts
 *   comments  - Seed only comments
 *   truncate  - Clear seeded data (keeps users)
 *   fresh     - Truncate and re-seed
 */

// Bootstrap the application
define('ROOT_PATH', __DIR__);
define('BASE_PATH', __DIR__);
define('SRC_PATH', __DIR__ . '/src');
define('CONFIG_PATH', ROOT_PATH . '/src/config');

// Load autoloader
require_once ROOT_PATH . '/src/utils/Autoloader.php';
\App\Utils\Autoloader::register();

// Load environment
if (file_exists(ROOT_PATH . '/.env')) {
    $lines = file(ROOT_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            putenv(trim($line));
        }
    }
}

use App\Database\Seeder;

// Parse command line arguments
$command = $argv[1] ?? 'all';
$count = isset($argv[2]) ? (int)$argv[2] : null;

echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║             VelocityPhp Database Seeder                       ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";

try {
    switch ($command) {
        case 'all':
            Seeder::seedUsers($count ?? 10);
            Seeder::seedCategories();
            Seeder::seedPosts($count ? $count * 2 : 20);
            Seeder::seedComments($count ? $count * 5 : 50);
            break;
            
        case 'users':
            Seeder::seedUsers($count ?? 10);
            break;
            
        case 'posts':
            Seeder::seedPosts($count ?? 20);
            break;
            
        case 'comments':
            Seeder::seedComments($count ?? 50);
            break;
            
        case 'categories':
            Seeder::seedCategories();
            break;
            
        case 'truncate':
            Seeder::truncate();
            break;
            
        case 'fresh':
            Seeder::truncate();
            Seeder::seedUsers($count ?? 10);
            Seeder::seedCategories();
            Seeder::seedPosts($count ? $count * 2 : 20);
            Seeder::seedComments($count ? $count * 5 : 50);
            break;
            
        case 'help':
        default:
            echo "\nUsage: php seed.php [command] [count]\n\n";
            echo "Commands:\n";
            echo "  all [count]      - Run all seeders (default count: 10 users, 20 posts, 50 comments)\n";
            echo "  users [count]    - Seed only users (default: 10)\n";
            echo "  posts [count]    - Seed only posts (default: 20)\n";
            echo "  comments [count] - Seed only comments (default: 50)\n";
            echo "  categories       - Seed categories\n";
            echo "  truncate         - Clear seeded data\n";
            echo "  fresh [count]    - Truncate and re-seed all\n";
            echo "  help             - Show this help\n\n";
            echo "Examples:\n";
            echo "  php seed.php all\n";
            echo "  php seed.php users 50\n";
            echo "  php seed.php fresh 100\n\n";
            break;
    }
    
    echo "\n✓ Seeding completed successfully!\n\n";
    
} catch (\Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
    exit(1);
}
