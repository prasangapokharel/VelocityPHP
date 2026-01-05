<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <meta name="description" content="VelocityPhp - Ultra-fast production-ready PHP framework">
    <title><?php echo $title ?? 'VelocityPhp'; ?></title>
    <!-- Preconnect for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="dns-prefetch" href="https://code.jquery.com">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <!-- Stylesheets - Load synchronously for immediate rendering -->
    <link rel="stylesheet" href="/assets/css/theme.css">
    <link rel="stylesheet" href="/assets/css/global.css">
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">

    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4" defer></script>
</head>
<body>
    <!-- Loading Bar -->
    <div id="loading-bar"></div>
    
    <!-- Main App Container -->
    <div id="app">
        <!-- Main Content Area -->
        <main id="app-content">
            <?php echo $content; ?>
        </main>
        
        <!-- Footer -->
        <?php include VIEW_PATH . '/components/footer.php'; ?>
    </div>
    
    <!-- Scripts - Optimized loading -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" 
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" 
            crossorigin="anonymous"
            defer></script>
    <?php 
    // Cache app config to avoid repeated requires
    static $appConfigCache = null;
    if ($appConfigCache === null) {
        $appConfigCache = require CONFIG_PATH . '/app.php';
    }
    $appConfig = $appConfigCache;
    ?>
    <script>
        window.DEBUG_MODE = <?php echo ($appConfig['debug'] ?? false) ? 'true' : 'false'; ?>;
    </script>
    <script src="/assets/js/app.js" defer></script>
    
    <!-- Page-specific scripts -->
    <?php if (isset($scripts)): ?>
        <?php foreach ($scripts as $script): ?>
            <script src="<?php echo htmlspecialchars($script, ENT_QUOTES, 'UTF-8'); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Inline scripts -->
    <?php if (isset($inlineScripts)): ?>
        <script>
            <?php echo $inlineScripts; ?>
        </script>
    <?php endif; ?>
</body>
</html>
