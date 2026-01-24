<?php
/**
 * Admin Dashboard Layout Wrapper
 * Modern Premium Design - Reusable Layout
 * ThinQShopping Platform
 * 
 * Usage:
 * <?php
 * $pageTitle = 'Page Title';
 * $pageContent = 'path/to/content.php'; // or inline content
 * include __DIR__ . '/../includes/layouts/admin-layout.php';
 * ?>
 * 
 * OR use $content variable directly for inline content
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    if (!defined('SESSION_NAME')) {
        require_once __DIR__ . '/../../config/constants.php';
    }
    session_name(SESSION_NAME);
    session_start();
}

// Ensure admin is authenticated
if (!isset($_SESSION['admin_id'])) {
    require_once __DIR__ . '/../admin-auth-check.php';
}

// Get page title or use default
$pageTitle = $pageTitle ?? 'Admin Panel - ' . APP_NAME;

// If content is a file path, include it; otherwise use $content variable
if (isset($pageContent) && is_string($pageContent) && file_exists($pageContent)) {
    ob_start();
    include $pageContent;
    $content = ob_get_clean();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js (optional - include only if needed) -->
    <?php if (isset($includeCharts) && $includeCharts): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <?php endif; ?>
    
    <!-- Admin Dashboard Styles -->
    <?php
    $adminCssFile = __DIR__ . '/../../assets/css/admin-dashboard.css';
    $adminCssVersion = file_exists($adminCssFile) ? md5_file($adminCssFile) : time();
    ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin-dashboard.css?v=<?php echo time(); ?>&rev=<?php echo substr($adminCssVersion, 0, 8); ?>">
    
    <!-- Global Modal Fix CSS - Must be after Bootstrap and dashboard CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/modal-fix.css?v=<?php echo time(); ?>">

    <!-- Components CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/components/admin-sidebar.css?v=<?php echo time(); ?>">

    <!-- Professional UI Standard - Global Consistency -->
    <link rel="stylesheet" href="<?php echo asset('assets/css/professional-ui-standard.css'); ?>?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo asset('assets/css/mobile-first-optimization.css'); ?>?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo asset('assets/css/brand-color-override.css'); ?>?v=<?php echo time(); ?>">
    
    <?php if (isset($additionalCSS) && is_array($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if (isset($inlineCSS)): ?>
    <style>
        <?php echo $inlineCSS; ?>
    </style>
    <?php endif; ?>
</head>
<body>
    <?php include __DIR__ . '/../admin-sidebar.php'; ?>
    <?php include __DIR__ . '/../admin-header.php'; ?>

    <div class="admin-main-content">
        <?php 
        // Display flash messages
        $flash = getFlashMessage();
        if ($flash): 
        ?>
            <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'danger' ? 'danger' : 'info'); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php 
        // Display errors if any
        if (isset($errors) && !empty($errors)): 
        ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php 
        // Display page content
        if (isset($content) && !empty(trim($content))) {
            echo $content;
        } else {
            // Always show debug info if content is missing
            echo "<div class='alert alert-danger'>";
            echo "<h4>Content Not Found</h4>";
            echo "<p><strong>Debug Info:</strong></p>";
            echo "<ul>";
            echo "<li>Content variable is set: " . (isset($content) ? 'YES' : 'NO') . "</li>";
            echo "<li>Content is empty: " . (empty($content) ? 'YES' : 'NO') . "</li>";
            if (isset($content)) {
                echo "<li>Content length: " . strlen($content) . " bytes</li>";
                echo "<li>Content preview (first 200 chars): " . htmlspecialchars(substr($content, 0, 200)) . "</li>";
            }
            echo "</ul>";
            echo "<p>This usually means the page content was not captured correctly. Please check the page file.</p>";
            echo "</div>";
        }
        ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Global Modal Fix JS - Must be after Bootstrap JS -->
    <script src="<?php echo BASE_URL; ?>/assets/js/modal-fix.js?v=<?php echo time(); ?>"></script>
    
    <?php if (isset($additionalJS) && is_array($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if (isset($inlineJS)): ?>
    <script>
        <?php echo $inlineJS; ?>
    </script>
    <?php endif; ?>
    
    <!-- Mobile Bottom Menu (shown only on mobile) - Backup in case header doesn't include it -->
    <?php if (!isset($mobileMenuIncluded)): ?>
        <?php include __DIR__ . '/../mobile-menu.php'; ?>
    <?php endif; ?>
</body>
</html>



