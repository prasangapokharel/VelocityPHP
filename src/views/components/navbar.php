<nav class="bg-white border-b border-neutral-200 sticky top-0 z-50 shadow-sm">
    <div class="container">
        <div class="flex justify-between items-center py-md">
            <div class="flex items-center gap-md">
                <a href="/" class="flex items-center gap-xs text-neutral-900 text-xl font-bold no-underline hover:text-neutral-700 transition-colors">
                    <span class="text-2xl">⚡</span>
                    <span>VelocityPhp</span>
                </a>
            </div>
            
            <ul class="flex gap-sm list-none m-0 p-0 items-center">
                <li>
                    <a href="/" class="nav-link" data-page="/">
                        Home
                    </a>
                </li>
                <li>
                    <a href="/about" class="nav-link" data-page="/about">
                        About
                    </a>
                </li>
            </ul>
            
            <div class="flex gap-sm items-center">
                <a href="https://github.com/prasangapokharel/VelocityPHP" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm flex items-center gap-xs" data-no-ajax>
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                    </svg>
                    GitHub
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
(function() {
    function updateActiveNav() {
        const currentPath = window.location.pathname;
        document.querySelectorAll('.nav-link').forEach(link => {
            const linkPath = link.getAttribute('href');
            if (linkPath === currentPath || (linkPath !== '/' && currentPath.startsWith(linkPath))) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    }
    
    updateActiveNav();
    
    document.addEventListener('content:updated', updateActiveNav);
    window.addEventListener('popstate', updateActiveNav);
})();
</script>
