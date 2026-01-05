<h1 class="text-3xl font-bold text-neutral-900 mb-lg">Welcome to VelocityPHP</h1>

<section class="mb-xl">
    <p class="text-lg text-neutral-600 mb-lg">
        A blazing-fast PHP framework with Next.js-style file-based routing. 
        Create pages by simply adding files - no configuration needed.
    </p>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-lg mb-xl">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title text-lg">File-Based Routing</h3>
            </div>
            <div class="card-content">
                <p class="text-sm text-neutral-600">
                    Create <code class="bg-neutral-100 text-neutral-900 px-xs py-xs rounded-sm">/pages/about/index.php</code> 
                    and it's automatically available at <code class="bg-neutral-100 text-neutral-900 px-xs py-xs rounded-sm">/about</code>
                </p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title text-lg">Zero Refresh Navigation</h3>
            </div>
            <div class="card-content">
                <p class="text-sm text-neutral-600">
                    Navigate between pages without full page reloads. True SPA experience with PHP backend.
                </p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title text-lg">REST API v1</h3>
            </div>
            <div class="card-content">
                <p class="text-sm text-neutral-600">
                    Built-in API with JWT authentication, rate limiting, and CRUD operations.
                </p>
            </div>
        </div>
    </div>
</section>

<section class="mb-xl">
    <h2 class="text-2xl font-semibold text-neutral-900 mb-md">Quick Start</h2>
    
    <div class="card bg-neutral-900 border-neutral-800">
        <div class="card-content pt-lg">
            <pre class="text-neutral-100 text-sm"><code># Create a new page
mkdir -p src/views/pages/mypage
echo '&lt;h1&gt;My Page&lt;/h1&gt;' > src/views/pages/mypage/index.php

# Access at /mypage - that's it!</code></pre>
        </div>
    </div>
</section>

<section class="mb-xl">
    <h2 class="text-2xl font-semibold text-neutral-900 mb-md">Navigation</h2>
    <div class="flex gap-md">
        <a href="/documentation" class="btn btn-primary btn-md">Documentation</a>
    </div>
</section>
