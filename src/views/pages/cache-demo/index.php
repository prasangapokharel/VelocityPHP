<?php
/**
 * Cache Demo Page
 * Demonstrates the FileCache system working from frontend
 */

use App\Utils\FileCache;

$cache = FileCache::getInstance();

// Generate test data
$testKey = 'demo_page_' . date('His');
$testData = [
    'product' => 'VelocityPHP Cache Demo',
    'price' => 99.99,
    'features' => ['Fast', 'Secure', 'Production Ready'],
    'timestamp' => date('Y-m-d H:i:s')
];

// Test cache operations with timing
$results = [];

// TEST 1: Write to cache
$writeStart = microtime(true);
$writeSuccess = $cache->set($testKey, $testData, 'data', 300);
$writeTime = (microtime(true) - $writeStart) * 1000;
$results['write'] = [
    'success' => $writeSuccess,
    'time_ms' => round($writeTime, 4)
];

// TEST 2: Read from cache
$readStart = microtime(true);
$cachedData = $cache->get($testKey, 'data');
$readTime = (microtime(true) - $readStart) * 1000;
$results['read'] = [
    'success' => $cachedData !== null,
    'time_ms' => round($readTime, 4),
    'data' => $cachedData
];

// TEST 3: Has check
$hasStart = microtime(true);
$hasCache = $cache->has($testKey, 'data');
$hasTime = (microtime(true) - $hasStart) * 1000;
$results['has'] = [
    'success' => $hasCache,
    'time_ms' => round($hasTime, 4)
];

// TEST 4: Remember pattern
$rememberStart = microtime(true);
$rememberData = $cache->remember('demo_remember_test', function() {
    return ['computed' => true, 'value' => rand(1000, 9999)];
}, 60);
$rememberTime = (microtime(true) - $rememberStart) * 1000;
$results['remember'] = [
    'success' => $rememberData !== null,
    'time_ms' => round($rememberTime, 4),
    'data' => $rememberData
];

// TEST 5: User cache
$userStart = microtime(true);
$cache->setUser(12345, ['id' => 12345, 'name' => 'Demo User', 'role' => 'admin'], 60);
$userData = $cache->getUser(12345);
$userTime = (microtime(true) - $userStart) * 1000;
$results['user_cache'] = [
    'success' => $userData !== null && $userData['name'] === 'Demo User',
    'time_ms' => round($userTime, 4)
];

// TEST 6: Delete
$deleteStart = microtime(true);
$deleteSuccess = $cache->delete($testKey, 'data');
$deleteTime = (microtime(true) - $deleteStart) * 1000;
$results['delete'] = [
    'success' => $deleteSuccess,
    'time_ms' => round($deleteTime, 4)
];

// Get cache stats
$stats = $cache->getStats();

// Cleanup
$cache->delete('demo_remember_test', 'data');
$cache->deleteUser(12345);

// Count results
$passed = 0;
$failed = 0;
foreach ($results as $test) {
    if ($test['success']) $passed++;
    else $failed++;
}
?>

<h1 class="text-3xl font-bold text-neutral-900 mb-lg">Cache System Demo</h1>

<div class="mb-xl">
    <p class="text-lg text-neutral-600 mb-md">
        This page demonstrates the VelocityPHP FileCache system working in real-time from the frontend.
    </p>
</div>

<!-- Test Results Summary -->
<section class="mb-xl">
    <div class="card <?= $failed === 0 ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' ?>">
        <div class="card-content">
            <div class="flex items-center gap-md">
                <span class="text-4xl"><?= $failed === 0 ? '✓' : '✗' ?></span>
                <div>
                    <h3 class="text-xl font-bold <?= $failed === 0 ? 'text-green-700' : 'text-red-700' ?>">
                        <?= $failed === 0 ? 'All Tests Passed!' : $failed . ' Test(s) Failed' ?>
                    </h3>
                    <p class="text-sm <?= $failed === 0 ? 'text-green-600' : 'text-red-600' ?>">
                        <?= $passed ?>/<?= count($results) ?> tests passed
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Individual Test Results -->
<section class="mb-xl">
    <h2 class="text-2xl font-semibold text-neutral-900 mb-md">Test Results</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-md">
        <?php foreach ($results as $name => $result): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title flex items-center gap-sm">
                    <span class="<?= $result['success'] ? 'text-green-600' : 'text-red-600' ?>">
                        <?= $result['success'] ? '✓' : '✗' ?>
                    </span>
                    <?= ucfirst(str_replace('_', ' ', $name)) ?>
                </h3>
            </div>
            <div class="card-content">
                <p class="text-sm text-neutral-600">
                    Time: <code class="bg-neutral-100 px-xs py-xs rounded-sm font-mono">
                        <?= $result['time_ms'] ?>ms
                    </code>
                </p>
                <?php if (isset($result['data']) && is_array($result['data'])): ?>
                <p class="text-xs text-neutral-500 mt-sm">
                    Data: <?= count($result['data']) ?> fields
                </p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Performance Metrics -->
<section class="mb-xl">
    <h2 class="text-2xl font-semibold text-neutral-900 mb-md">Performance Metrics</h2>
    
    <div class="card bg-neutral-900 border-neutral-800">
        <div class="card-content pt-lg">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-lg text-center">
                <div>
                    <p class="text-3xl font-bold text-green-400"><?= $results['write']['time_ms'] ?>ms</p>
                    <p class="text-sm text-neutral-400">Write Time</p>
                </div>
                <div>
                    <p class="text-3xl font-bold text-blue-400"><?= $results['read']['time_ms'] ?>ms</p>
                    <p class="text-sm text-neutral-400">Read Time</p>
                </div>
                <div>
                    <p class="text-3xl font-bold text-yellow-400"><?= $results['has']['time_ms'] ?>ms</p>
                    <p class="text-sm text-neutral-400">Has Check</p>
                </div>
                <div>
                    <p class="text-3xl font-bold text-red-400"><?= $results['delete']['time_ms'] ?>ms</p>
                    <p class="text-sm text-neutral-400">Delete Time</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Cache Statistics -->
<section class="mb-xl">
    <h2 class="text-2xl font-semibold text-neutral-900 mb-md">Cache Statistics</h2>
    
    <div class="card">
        <div class="card-content">
            <div class="grid grid-cols-2 md:grid-cols-3 gap-lg">
                <div>
                    <p class="text-2xl font-bold text-neutral-900"><?= $stats['total_files'] ?></p>
                    <p class="text-sm text-neutral-600">Total Files</p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-neutral-900"><?= $stats['total_size_human'] ?></p>
                    <p class="text-sm text-neutral-600">Total Size</p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-neutral-900"><?= count($stats['by_type']) ?></p>
                    <p class="text-sm text-neutral-600">Cache Types</p>
                </div>
            </div>
            
            <hr class="my-md border-neutral-200">
            
            <h4 class="font-semibold text-neutral-900 mb-sm">By Type:</h4>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-sm">
                <?php foreach ($stats['by_type'] as $type => $typeStats): ?>
                <div class="bg-neutral-50 p-sm rounded-md">
                    <p class="font-semibold text-neutral-900"><?= ucfirst($type) ?></p>
                    <p class="text-xs text-neutral-600"><?= $typeStats['files'] ?> files (<?= $typeStats['size_human'] ?>)</p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- Cached Data Preview -->
<section class="mb-xl">
    <h2 class="text-2xl font-semibold text-neutral-900 mb-md">Cached Data Preview</h2>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Test Data (Read from Cache)</h3>
        </div>
        <div class="card-content">
            <pre class="bg-neutral-100 p-md rounded-md text-sm overflow-auto"><code><?= htmlspecialchars(json_encode($results['read']['data'], JSON_PRETTY_PRINT)) ?></code></pre>
        </div>
    </div>
</section>

<!-- Security Info -->
<section class="mb-xl">
    <h2 class="text-2xl font-semibold text-neutral-900 mb-md">Security Features</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
        <div class="card">
            <div class="card-content">
                <h4 class="font-semibold text-green-700 mb-sm">✓ .htaccess Protection</h4>
                <p class="text-sm text-neutral-600">Cache directory has .htaccess blocking all direct access</p>
            </div>
        </div>
        <div class="card">
            <div class="card-content">
                <h4 class="font-semibold text-green-700 mb-sm">✓ Directory Traversal Protection</h4>
                <p class="text-sm text-neutral-600">Keys are sanitized to prevent path traversal attacks</p>
            </div>
        </div>
        <div class="card">
            <div class="card-content">
                <h4 class="font-semibold text-green-700 mb-sm">✓ PHP Execution Prevention</h4>
                <p class="text-sm text-neutral-600">Cache files have BASE_PATH check to prevent direct execution</p>
            </div>
        </div>
        <div class="card">
            <div class="card-content">
                <h4 class="font-semibold text-green-700 mb-sm">✓ Atomic Writes</h4>
                <p class="text-sm text-neutral-600">Uses temp files + rename for atomic cache updates</p>
            </div>
        </div>
    </div>
</section>

<section>
    <a href="/" class="btn btn-primary btn-md">← Back to Home</a>
</section>
