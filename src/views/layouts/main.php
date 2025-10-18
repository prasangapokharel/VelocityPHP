<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
    <meta name="description" content="VelocityPHP - Zero-refresh PHP framework">
    
    <title><?php echo $title ?? 'VelocityPHP App'; ?></title>
    
    <!-- Preconnect for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="stylesheet" href="/assets/css/pages.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
</head>
<body>
    <!-- Loading Bar -->
    <div id="loading-bar"></div>
    
    <!-- Main App Container -->
    <div id="app">
        <!-- Navigation -->
        <?php include VIEW_PATH . '/components/navbar.php'; ?>
        
        <!-- Main Content Area -->
        <main id="app-content">
            <?php echo $content; ?>
        </main>
        
        <!-- Footer -->
        <?php include VIEW_PATH . '/components/footer.php'; ?>
    </div>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" 
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" 
            crossorigin="anonymous"></script>
    <script src="/assets/js/app.js"></script>
    
    <!-- Page-specific scripts -->
    <?php if (isset($scripts)): ?>
        <?php foreach ($scripts as $script): ?>
            <script src="<?php echo $script; ?>"></script>
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
