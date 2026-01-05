<h1 class="text-3xl font-bold text-neutral-900 mb-lg">Documentation</h1>

<section class="mb-xl">
    <p class="text-lg text-neutral-600 mb-lg">
        Complete guide to building applications with VelocityPHP framework.
    </p>
</section>

<!-- File-Based Routing -->
<section class="mb-xl">
    <h2 class="text-2xl font-semibold text-neutral-900 mb-md">File-Based Routing</h2>
    <p class="text-neutral-600 mb-md">
        VelocityPHP uses Next.js-style file-based routing. Create a file and it becomes a route automatically.
    </p>
    
    <div class="card mb-md">
        <div class="card-header">
            <h3 class="card-title text-lg">Directory Structure</h3>
        </div>
        <div class="card-content">
            <div class="bg-neutral-900 rounded-md p-md">
                <pre class="text-neutral-100 text-sm"><code>src/views/pages/
  index/index.php         -> /
  about/index.php         -> /about
  documentation/index.php -> /documentation
  blog/[id]/index.php     -> /blog/123 (dynamic route)</code></pre>
            </div>
        </div>
    </div>
    
    <div class="alert alert-info mb-md">
        <div class="alert-title">Auto-Refresh</div>
        <div class="alert-description">
            When you create or modify a page file, changes are reflected immediately without server restart.
        </div>
    </div>
</section>

<!-- Creating Pages -->
<section class="mb-xl">
    <h2 class="text-2xl font-semibold text-neutral-900 mb-md">Creating Pages</h2>
    
    <div class="card mb-md">
        <div class="card-header">
            <h3 class="card-title text-lg">Step 1: Create Directory</h3>
        </div>
        <div class="card-content">
            <div class="bg-neutral-900 rounded-md p-md">
                <pre class="text-neutral-100 text-sm"><code>mkdir -p src/views/pages/mypage</code></pre>
            </div>
        </div>
    </div>
    
    <div class="card mb-md">
        <div class="card-header">
            <h3 class="card-title text-lg">Step 2: Create index.php</h3>
        </div>
        <div class="card-content">
            <div class="bg-neutral-900 rounded-md p-md">
                <pre class="text-neutral-100 text-sm"><code>&lt;h1 class="text-3xl font-bold text-neutral-900 mb-lg"&gt;My Page&lt;/h1&gt;

&lt;section class="mb-xl"&gt;
    &lt;p class="text-neutral-600"&gt;Your content here...&lt;/p&gt;
&lt;/section&gt;</code></pre>
            </div>
        </div>
    </div>
    
    <div class="card mb-md">
        <div class="card-header">
            <h3 class="card-title text-lg">Step 3: Access Your Page</h3>
        </div>
        <div class="card-content">
            <p class="text-neutral-600">
                Navigate to <code class="bg-neutral-100 text-neutral-900 px-xs py-xs rounded-sm">/mypage</code> - URL stays in browser, content loads via AJAX.
            </p>
        </div>
    </div>
</section>

<!-- API Documentation -->
<section class="mb-xl">
    <h2 class="text-2xl font-semibold text-neutral-900 mb-md">REST API v1</h2>
    <p class="text-neutral-600 mb-md">
        Full REST API with JWT authentication and rate limiting.
    </p>
    
    <div class="card mb-md">
        <div class="card-header">
            <h3 class="card-title text-lg">Authentication Endpoints</h3>
        </div>
        <div class="card-content">
            <table class="table w-full">
                <thead>
                    <tr class="table-header-row">
                        <th class="table-header-cell">Method</th>
                        <th class="table-header-cell">Endpoint</th>
                        <th class="table-header-cell">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-body-row">
                        <td class="table-body-cell"><span class="badge badge-success badge-sm">POST</span></td>
                        <td class="table-body-cell"><code>/api/v1/auth/register</code></td>
                        <td class="table-body-cell">Register new user</td>
                    </tr>
                    <tr class="table-body-row">
                        <td class="table-body-cell"><span class="badge badge-success badge-sm">POST</span></td>
                        <td class="table-body-cell"><code>/api/v1/auth/login</code></td>
                        <td class="table-body-cell">Login and get token</td>
                    </tr>
                    <tr class="table-body-row">
                        <td class="table-body-cell"><span class="badge badge-info badge-sm">GET</span></td>
                        <td class="table-body-cell"><code>/api/v1/auth/me</code></td>
                        <td class="table-body-cell">Get current user (auth required)</td>
                    </tr>
                    <tr class="table-body-row">
                        <td class="table-body-cell"><span class="badge badge-success badge-sm">POST</span></td>
                        <td class="table-body-cell"><code>/api/v1/auth/logout</code></td>
                        <td class="table-body-cell">Logout (auth required)</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card mb-md">
        <div class="card-header">
            <h3 class="card-title text-lg">Users CRUD (Auth Required)</h3>
        </div>
        <div class="card-content">
            <table class="table w-full">
                <thead>
                    <tr class="table-header-row">
                        <th class="table-header-cell">Method</th>
                        <th class="table-header-cell">Endpoint</th>
                        <th class="table-header-cell">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-body-row">
                        <td class="table-body-cell"><span class="badge badge-info badge-sm">GET</span></td>
                        <td class="table-body-cell"><code>/api/v1/users</code></td>
                        <td class="table-body-cell">List users (paginated)</td>
                    </tr>
                    <tr class="table-body-row">
                        <td class="table-body-cell"><span class="badge badge-info badge-sm">GET</span></td>
                        <td class="table-body-cell"><code>/api/v1/users/{id}</code></td>
                        <td class="table-body-cell">Get single user</td>
                    </tr>
                    <tr class="table-body-row">
                        <td class="table-body-cell"><span class="badge badge-success badge-sm">POST</span></td>
                        <td class="table-body-cell"><code>/api/v1/users</code></td>
                        <td class="table-body-cell">Create user</td>
                    </tr>
                    <tr class="table-body-row">
                        <td class="table-body-cell"><span class="badge badge-warning badge-sm">PUT</span></td>
                        <td class="table-body-cell"><code>/api/v1/users/{id}</code></td>
                        <td class="table-body-cell">Update user</td>
                    </tr>
                    <tr class="table-body-row">
                        <td class="table-body-cell"><span class="badge badge-destructive badge-sm">DELETE</span></td>
                        <td class="table-body-cell"><code>/api/v1/users/{id}</code></td>
                        <td class="table-body-cell">Delete user</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title text-lg">Example: Login Request</h3>
        </div>
        <div class="card-content">
            <div class="bg-neutral-900 rounded-md p-md">
                <pre class="text-neutral-100 text-sm"><code>curl -X POST http://localhost:8001/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password123"}'

# Response:
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {"id": 1, "name": "User", "email": "user@example.com"},
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "Bearer"
  }
}</code></pre>
            </div>
        </div>
    </div>
</section>

<!-- CLI Commands -->
<section class="mb-xl">
    <h2 class="text-2xl font-semibold text-neutral-900 mb-md">CLI Commands</h2>
    
    <div class="card">
        <div class="card-content pt-lg">
            <table class="table w-full">
                <thead>
                    <tr class="table-header-row">
                        <th class="table-header-cell">Command</th>
                        <th class="table-header-cell">Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-body-row">
                        <td class="table-body-cell"><code>php start.php</code></td>
                        <td class="table-body-cell">Start development server on port 8001</td>
                    </tr>
                    <tr class="table-body-row">
                        <td class="table-body-cell"><code>php start.php 8080</code></td>
                        <td class="table-body-cell">Start server on custom port</td>
                    </tr>
                    <tr class="table-body-row">
                        <td class="table-body-cell"><code>php seed.php</code></td>
                        <td class="table-body-cell">Run database seeders</td>
                    </tr>
                    <tr class="table-body-row">
                        <td class="table-body-cell"><code>php tests/TestRunner.php</code></td>
                        <td class="table-body-cell">Run test suite (138 tests)</td>
                    </tr>
                    <tr class="table-body-row">
                        <td class="table-body-cell"><code>php tests/ApiTestRunner.php</code></td>
                        <td class="table-body-cell">Run API tests (50 tests)</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section>
    <a href="/" class="btn btn-outline btn-md">Back to Home</a>
</section>
