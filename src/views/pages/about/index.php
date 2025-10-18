<div class="container">
    <div class="page-header">
        <h1>About Native MVC</h1>
        <p>Learn about this powerful zero-refresh framework</p>
    </div>
    
    <div class="card">
        <h2>What is Native MVC?</h2>
        <p>Native MVC is a highly turbo-powered, production-ready PHP framework that brings Single Page Application (SPA) experience to traditional PHP development. Built with jQuery and AJAX, it provides seamless navigation without page refreshes while maintaining the simplicity and power of PHP.</p>
    </div>
    
    <div class="card">
        <h2>Key Features</h2>
        <ul class="feature-list">
            <li>⚡ <strong>Zero Page Refresh:</strong> True SPA experience with instant navigation</li>
            <li>📁 <strong>Next.js-Style Routing:</strong> File-based routes with automatic discovery</li>
            <li>🎯 <strong>MVC Architecture:</strong> Clean separation of concerns</li>
            <li>🔒 <strong>Production Ready:</strong> Built-in security and performance optimization</li>
            <li>🚀 <strong>High Performance:</strong> View caching, preloading, and optimization</li>
            <li>📱 <strong>Responsive Design:</strong> Mobile-first approach</li>
            <li>🔧 <strong>Easy to Extend:</strong> Modular architecture for customization</li>
            <li>📊 <strong>Database Abstraction:</strong> Support for MySQL, PostgreSQL, SQLite</li>
        </ul>
    </div>
    
    <div class="card">
        <h2>Getting Started</h2>
        <p>Creating new pages is incredibly simple. Just add a folder in <code>src/views/pages/yourpage/</code> with an <code>index.php</code> file, and your page is automatically routed to <code>/yourpage</code> with zero configuration!</p>
        
        <div class="cta-section">
            <a href="/dashboard" class="btn btn-primary">Try the Dashboard</a>
            <a href="/users" class="btn btn-secondary">View Users Example</a>
        </div>
    </div>
</div>

<style>
.feature-list {
    list-style: none;
    padding: 0;
}

.feature-list li {
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border-color);
}

.feature-list li:last-child {
    border-bottom: none;
}

.tech-stack {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 2rem;
    margin-top: 1rem;
}

.tech-item h3 {
    color: var(--primary-color);
    margin-bottom: 0.5rem;
}

.tech-item ul {
    list-style: none;
    padding: 0;
}

.tech-item ul li {
    padding: 0.25rem 0;
    color: var(--secondary-color);
}

.cta-section {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

@media (max-width: 768px) {
    .cta-section {
        flex-direction: column;
    }
}
</style>

<script>
$(document).ready(function() {
    console.log('About page loaded via AJAX');
});
</script>
