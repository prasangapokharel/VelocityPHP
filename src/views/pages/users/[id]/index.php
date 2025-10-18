<?php
// Dynamic route example: /users/1, /users/2, etc.
// The $id variable is automatically extracted from the URL
?>

<div class="container">
    <div class="page-header">
        <h1>User Profile #<?php echo htmlspecialchars($id ?? 'N/A'); ?></h1>
        <p>Viewing details for user ID: <?php echo htmlspecialchars($id ?? 'N/A'); ?></p>
    </div>
    
    <div class="row">
        <div class="col-4">
            <div class="card">
                <div style="text-align: center; padding: 2rem;">
                    <div class="user-avatar">
                        <img src="https://ui-avatars.com/api/?name=User+<?php echo $id; ?>&size=150&background=2563eb&color=fff" 
                             alt="User Avatar" 
                             style="border-radius: 50%;">
                    </div>
                    <h3 style="margin-top: 1rem;">User <?php echo htmlspecialchars($id); ?></h3>
                    <p style="color: var(--secondary-color);">user<?php echo $id; ?>@example.com</p>
                </div>
            </div>
        </div>
        
        <div class="col-8">
            <div class="card">
                <div class="card-header">User Information</div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <strong>User ID:</strong>
                        <span><?php echo htmlspecialchars($id); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <strong>Name:</strong>
                        <span>User <?php echo htmlspecialchars($id); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <strong>Email:</strong>
                        <span>user<?php echo $id; ?>@example.com</span>
                    </div>
                    
                    <div class="info-item">
                        <strong>Role:</strong>
                        <span class="badge badge-primary">Member</span>
                    </div>
                    
                    <div class="info-item">
                        <strong>Joined:</strong>
                        <span><?php echo date('F j, Y'); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <strong>Status:</strong>
                        <span class="badge badge-success">Active</span>
                    </div>
                </div>
                
                <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color);">
                    <a href="/users" class="btn btn-secondary">← Back to Users</a>
                    <button class="btn btn-primary" onclick="editUserProfile()">Edit Profile</button>
                </div>
            </div>
            
            <div class="card" style="margin-top: 1.5rem;">
                <div class="card-header">Recent Activity</div>
                
                <div class="activity-list">
                    <div class="activity-item">
                        <div class="activity-icon">📝</div>
                        <div class="activity-content">
                            <strong>Updated profile</strong>
                            <span class="activity-time">2 days ago</span>
                        </div>
                    </div>
                    
                    <div class="activity-item">
                        <div class="activity-icon">🔐</div>
                        <div class="activity-content">
                            <strong>Changed password</strong>
                            <span class="activity-time">1 week ago</span>
                        </div>
                    </div>
                    
                    <div class="activity-item">
                        <div class="activity-icon">✅</div>
                        <div class="activity-content">
                            <strong>Account created</strong>
                            <span class="activity-time">1 month ago</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    padding: 1.5rem 0;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.info-item strong {
    color: var(--secondary-color);
    font-size: 0.9rem;
}

.info-item span {
    font-size: 1rem;
}

.badge-success {
    background: #dcfce7;
    color: #166534;
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
</style>

<script>
function editUserProfile() {
    const userId = <?php echo json_encode($id); ?>;
    NativeApp.showSuccess('Edit profile for user ' + userId + ' - This would open an edit form');
}

// Log when dynamic route loads
$(document).ready(function() {
    console.log('Dynamic user page loaded: User ID = <?php echo $id; ?>');
});
</script>
