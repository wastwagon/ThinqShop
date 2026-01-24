<?php
/**
 * User Dashboard Layout Wrapper
 * ThinQShopping Platform
 */

// Ensure constants + session loaded
if (!defined('SESSION_NAME')) {
    require_once __DIR__ . '/../../config/constants.php';
}

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Ensure helper functions are available (asset, getFlashMessage, etc.)
if (!function_exists('getFlashMessage')) {
    require_once __DIR__ . '/../../includes/functions.php';
}

// Ensure user is authenticated (many pages already include auth-check but this is a safeguard)
if (!isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../auth-check.php';
}

// Normalize title/content inputs
$pageTitle = $pageTitle ?? (APP_NAME . ' - Dashboard');

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
    
    <!-- Bootstrap & Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- User Dashboard Styles -->
    <?php
    $userCssFile = __DIR__ . '/../../assets/css/user-dashboard.css';
    $userCssVersion = file_exists($userCssFile) ? md5_file($userCssFile) : time();
    ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/user-dashboard.css?v=<?php echo substr($userCssVersion, 0, 12); ?>">
    
    <!-- Professional UI Standard - Global Consistency -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/professional-ui-standard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/mobile-first-optimization.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/brand-color-override.css?v=<?php echo time(); ?>">
    
    <?php if (isset($additionalCSS) && is_array($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $cssUrl): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($cssUrl); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if (!empty($inlineCSS)): ?>
    <style>
        <?php echo $inlineCSS; ?>
    </style>
    <?php endif; ?>
</head>
<body>
    <?php include __DIR__ . '/../user-sidebar.php'; ?>
    <?php include __DIR__ . '/../user-header.php'; ?>
    
    <div class="user-main-content">
        <?php 
        $flash = getFlashMessage();
        if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'danger' ? 'danger' : 'info'); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($errors) && !empty($errors)): ?>
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
        if (isset($content) && trim($content) !== '') {
            echo $content;
        } else {
            echo '<div class="alert alert-warning"><strong>No content to display.</strong> Please ensure the page sets the $content variable before including the layout.</div>';
        }
        ?>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (isset($additionalJS) && is_array($additionalJS)): ?>
        <?php foreach ($additionalJS as $jsUrl): ?>
            <script src="<?php echo htmlspecialchars($jsUrl); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?php if (!empty($inlineJS)): ?>
    <script>
        <?php echo $inlineJS; ?>
    </script>
    <?php endif; ?>
    
    <?php if (!isset($mobileMenuIncluded)): ?>
        <?php include __DIR__ . '/../mobile-menu.php'; ?>
    <?php endif; ?>
</body>
</html>

