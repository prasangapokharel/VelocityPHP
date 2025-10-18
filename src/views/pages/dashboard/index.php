<div class="container">
    <div class="page-header">
        <h1>Dashboard</h1>
        <p>Welcome to your dashboard - Notice: No page refresh!</p>
    </div>
    
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
                <h3>Users</h3>
                <p class="stat-number">1,234</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <h3>Analytics</h3>
                <p class="stat-number">5,678</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
                <h3>Revenue</h3>
                <p class="stat-number">$12,345</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📈</div>
            <div class="stat-content">
                <h3>Growth</h3>
                <p class="stat-number">+23%</p>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-8">
            <div class="card">
                <div class="card-header">Recent Activity</div>
                <div class="activity-list">
                    <div class="activity-item">
                        <div class="activity-icon">✅</div>
                        <div class="activity-content">
                            <strong>New user registered</strong>
                            <span class="activity-time">2 minutes ago</span>
                        </div>
                    </div>
                    
                    <div class="activity-item">
                        <div class="activity-icon">📝</div>
                        <div class="activity-content">
                            <strong>New post created</strong>
                            <span class="activity-time">15 minutes ago</span>
                        </div>
                    </div>
                    
                    <div class="activity-item">
                        <div class="activity-icon">🔔</div>
                        <div class="activity-content">
                            <strong>System update completed</strong>
                            <span class="activity-time">1 hour ago</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-4">
            <div class="card">
                <div class="card-header">Quick Actions</div>
                <div class="quick-actions">
                    <button class="btn btn-primary btn-block" onclick="NativeApp.showSuccess('AJAX test successful!')">Test AJAX</button>
                    <a href="/users" class="btn btn-secondary btn-block">View Users</a>
                    <a href="/about" class="btn btn-secondary btn-block">About</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.page-header {
    margin-bottom: 2rem;
}

.page-header h1 {
    margin-bottom: 0.5rem;
}

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: var(--shadow);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    font-size: 2.5rem;
}

.stat-content h3 {
    margin: 0;
    font-size: 0.9rem;
    color: var(--secondary-color);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-number {
    font-size: 1.8rem;
    font-weight: 700;
    margin: 0.25rem 0 0;
    color: var(--primary-color);
}

.activity-list {
    padding: 1rem 0;
}

.activity-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    font-size: 1.5rem;
}

.activity-content {
    flex: 1;
}

.activity-content strong {
    display: block;
    margin-bottom: 0.25rem;
}

.activity-time {
    font-size: 0.85rem;
    color: var(--secondary-color);
}

.quick-actions {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 1rem 0;
}

.btn-block {
    width: 100%;
    justify-content: center;
}
</style>

<script>
function testAjaxAction() {
    // Log the action
    <?php
    \App\Utils\Logger::info('Dashboard AJAX action triggered', [
        'action' => 'test',
        'user' => 'guest'
    ]);
    ?>
    
    // Example AJAX call
    NativeApp.api.post('/api/test', { action: 'test' })
        .done(function(response) {
            NativeApp.showSuccess('AJAX action completed successfully!');
        })
        .fail(function() {
            NativeApp.showError('AJAX action failed');
        });
}

// Trigger event when dashboard loads
$(document).ready(function() {
    console.log('Dashboard loaded via AJAX - Zero refresh!');
    
    // Log page view
    <?php
    \App\Utils\Logger::info('Dashboard page viewed', [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    ?>
});
</script>
