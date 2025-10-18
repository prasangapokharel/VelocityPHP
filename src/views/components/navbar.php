<nav class="navbar">
    <div class="container">
        <div class="navbar-brand">
            <a href="/" class="logo">
                <h2>🚀 VelocityPHP</h2>
            </a>
        </div>
        
        <ul class="navbar-menu">
            <li><a href="/" class="nav-link">Home</a></li>
            <li><a href="/dashboard" class="nav-link">Dashboard</a></li>
            <li><a href="/users" class="nav-link">Users</a></li>
            <li><a href="/about" class="nav-link">About</a></li>
            <li><a href="/logs" class="nav-link">📋 Logs</a></li>
        </ul>
        
        <div class="navbar-actions">
            <a href="https://github.com" target="_blank" class="btn btn-primary" data-no-ajax>⭐ GitHub</a>
        </div>
    </div>
</nav>

<style>
.navbar {
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 1rem 0;
    position: sticky;
    top: 0;
    z-index: 1000;
}

.navbar .container {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.navbar-brand .logo {
    text-decoration: none;
    color: var(--primary-color);
}

.navbar-brand h2 {
    margin: 0;
    font-size: 24px;
}

.navbar-menu {
    display: flex;
    list-style: none;
    gap: 2rem;
    margin: 0;
}

.navbar-menu .nav-link {
    text-decoration: none;
    color: var(--text-color);
    font-weight: 500;
    transition: color 0.3s ease;
}

.navbar-menu .nav-link:hover {
    color: var(--primary-color);
}

.navbar-actions {
    display: flex;
    gap: 1rem;
}

@media (max-width: 768px) {
    .navbar .container {
        flex-direction: column;
        gap: 1rem;
    }
    
    .navbar-menu {
        gap: 1rem;
    }
}
</style>
