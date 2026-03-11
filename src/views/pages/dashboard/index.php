<?php use App\Utils\Auth; ?>
<div class="container">
    <div class="mb-xl">
        <h1 class="text-3xl font-bold mb-sm">Dashboard</h1>
        <p class="text-neutral-600">Welcome back, <?php echo htmlspecialchars(Auth::user()['name'] ?? 'User', ENT_QUOTES, 'UTF-8'); ?></p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-lg mb-xl">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Account</h2>
            </div>
            <div class="card-content">
                <p class="text-neutral-700 mb-xs"><strong>Name:</strong> <?php echo htmlspecialchars(Auth::user()['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="text-neutral-700 mb-xs"><strong>Email:</strong> <?php echo htmlspecialchars(Auth::user()['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="text-neutral-700 mb-xs"><strong>Role:</strong> <?php echo htmlspecialchars(Auth::user()['role'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Quick Links</h2>
            </div>
            <div class="card-content flex flex-col gap-sm">
                <a href="/users" class="btn btn-outline btn-sm">Manage Users</a>
                <a href="/crypto" class="btn btn-outline btn-sm">Crypto Prices</a>
                <a href="/about" class="btn btn-outline btn-sm">About</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Session</h2>
            </div>
            <div class="card-content">
                <p class="text-neutral-700 mb-sm text-sm">You are logged in.</p>
                <a href="/logout" class="btn btn-secondary btn-sm" data-no-ajax>Logout</a>
            </div>
        </div>
    </div>
</div>
