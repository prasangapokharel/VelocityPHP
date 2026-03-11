<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 — Server Error · VelocityPHP</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,.08), 0 10px 30px -5px rgba(0,0,0,.06);
            padding: 3rem 3.5rem;
            text-align: center;
            max-width: 480px;
            width: 100%;
        }

        .icon {
            font-size: 3.5rem;
            line-height: 1;
            margin-bottom: 1.25rem;
        }

        .code {
            font-size: 5rem;
            font-weight: 800;
            letter-spacing: -0.05em;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 1rem;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.75rem;
        }

        p {
            color: #64748b;
            line-height: 1.65;
            font-size: 1rem;
            margin-bottom: 2rem;
        }

        .actions {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.65rem 1.4rem;
            border-radius: 8px;
            font-size: 0.925rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease;
            cursor: pointer;
        }

        .btn-primary {
            background: #1e293b;
            color: #ffffff;
        }
        .btn-primary:hover {
            background: #334155;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(30,41,59,.25);
        }

        .btn-ghost {
            background: transparent;
            color: #64748b;
            border: 1.5px solid #e2e8f0;
        }
        .btn-ghost:hover {
            border-color: #cbd5e1;
            color: #1e293b;
            transform: translateY(-1px);
        }

        .notice {
            margin-top: 1.5rem;
            padding: 0.875rem 1.25rem;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            border-radius: 8px;
            font-size: 0.85rem;
            color: #92400e;
            text-align: left;
            line-height: 1.55;
        }

        .brand {
            margin-top: 2.5rem;
            font-size: 0.8rem;
            color: #94a3b8;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
        }
        .brand a {
            color: #64748b;
            text-decoration: none;
            font-weight: 600;
        }
        .brand a:hover { color: #1e293b; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">⚠️</div>
        <div class="code">500</div>
        <h1>Internal Server Error</h1>
        <p>Something went wrong on our end. We&rsquo;ve logged the error and will look into it. Please try again in a moment.</p>
        <div class="actions">
            <a href="/" class="btn btn-primary">
                ← Go Home
            </a>
            <a href="javascript:location.reload()" class="btn btn-ghost">
                Retry
            </a>
        </div>
        <div class="notice">
            If this keeps happening, please check the <strong>logs/error.log</strong> file or enable <code>APP_DEBUG=true</code> in your <code>.env.velocity</code> for a detailed stack trace.
        </div>
    </div>
    <div class="brand">
        Powered by <a href="https://github.com/prasangapokharel/VelocityPHP" target="_blank" rel="noopener">⚡ VelocityPHP</a>
    </div>
</body>
</html>
