# Layout Migration Guide
## Consistent Admin and User Dashboard Layouts

This guide explains how to migrate all admin and user pages to use the new consistent layout system.

## Overview

We've created reusable layout wrappers that ensure all dashboard pages have:
- ✅ Consistent sidebar navigation
- ✅ Consistent header with search, notifications, and profile
- ✅ Unified styling and design
- ✅ Flash message handling
- ✅ Error message display
- ✅ Responsive mobile support

## Files Created

### Layout Wrappers
1. **`includes/layouts/admin-layout.php`** - Admin dashboard layout wrapper
2. **`includes/layouts/user-layout.php`** - User dashboard layout wrapper

### CSS Files
1. **`assets/css/admin-dashboard.css`** - Admin dashboard styles
2. **`assets/css/user-dashboard.css`** - User dashboard styles

### Sidebar/Header Components (Already Exist)
- `includes/admin-sidebar.php`
- `includes/admin-header.php`
- `includes/user-sidebar.php`
- `includes/user-header.php`

## How to Use the New Layouts

### For Admin Pages

**Before:**
```php
<?php
require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$pageTitle = 'Page Title';
include __DIR__ . '/../../includes/header.php';
?>

<!-- Old navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    ...
</nav>

<div class="container-fluid">
    <!-- Page content -->
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
```

**After:**
```php
<?php
require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// ... your page logic ...

// Option 1: Inline content
ob_start();
?>
<div class="page-title-section">
    <h1 class="page-title">Page Title</h1>
</div>

<div class="card">
    <div class="card-body">
        <!-- Your page content here -->
    </div>
</div>
<?php
$content = ob_get_clean();

// Include layout
$pageTitle = 'Page Title - Admin - ' . APP_NAME;
include __DIR__ . '/../../includes/layouts/admin-layout.php';
```

**Or Option 2: External content file**
```php
<?php
// ... your page logic ...

$pageTitle = 'Page Title - Admin - ' . APP_NAME;
$pageContent = __DIR__ . '/content.php'; // External file
include __DIR__ . '/../../includes/layouts/admin-layout.php';
```

### For User Pages

**Before:**
```php
<?php
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$pageTitle = 'Page Title';
include __DIR__ . '/../../includes/header.php';
?>

<div class="container my-4">
    <!-- Page content -->
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
```

**After:**
```php
<?php
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// ... your page logic ...

ob_start();
?>
<div class="page-title-section">
    <h1 class="page-title">Page Title</h1>
</div>

<div class="card">
    <div class="card-body">
        <!-- Your page content here -->
    </div>
</div>
<?php
$content = ob_get_clean();

$pageTitle = 'Page Title - ' . APP_NAME;
include __DIR__ . '/../../includes/layouts/user-layout.php';
```

## Layout Features

### Automatic Features
- ✅ Sidebar and header are automatically included
- ✅ Flash messages are automatically displayed
- ✅ Error messages are automatically displayed (if `$errors` array is set)
- ✅ Bootstrap and Font Awesome are included
- ✅ Chart.js can be included optionally

### Optional Features

**Include Chart.js:**
```php
$includeCharts = true;
include __DIR__ . '/../../includes/layouts/admin-layout.php';
```

**Add Custom CSS:**
```php
$additionalCSS = [
    BASE_URL . '/assets/css/custom-page.css'
];
include __DIR__ . '/../../includes/layouts/admin-layout.php';
```

**Add Custom JavaScript:**
```php
$additionalJS = [
    BASE_URL . '/assets/js/custom-page.js'
];
include __DIR__ . '/../../includes/layouts/admin-layout.php';
```

**Inline CSS/JS:**
```php
$inlineCSS = '.custom-class { color: red; }';
$inlineJS = 'console.log("Hello");';
include __DIR__ . '/../../includes/layouts/admin-layout.php';
```

## Pages to Migrate

### Admin Pages
- [ ] `admin/ecommerce/products.php`
- [ ] `admin/ecommerce/products/add.php`
- [ ] `admin/ecommerce/products-edit.php`
- [ ] `admin/ecommerce/orders.php`
- [ ] `admin/ecommerce/orders/view.php`
- [ ] `admin/money-transfer/transfers.php`
- [ ] `admin/logistics/shipments.php`
- [ ] `admin/procurement/requests.php`
- [ ] `admin/payments/transactions.php`
- [ ] `admin/settings/general.php`
- [ ] `admin/users/manage.php` (if exists)

### User Pages
- [ ] `user/profile.php`
- [ ] `user/wallet.php`
- [ ] `user/wallet-topup-verify.php`
- [ ] `user/orders/index.php`
- [ ] `user/orders/view.php`
- [ ] `user/transfers/index.php`
- [ ] `user/shipments/index.php`
- [ ] `user/procurement/index.php`

### Already Using New Layout
- ✅ `admin/dashboard.php` (has inline styles but can be refactored)
- ✅ `user/dashboard.php` (has inline styles but can be refactored)

## CSS Classes Available

### Layout Structure
- `.admin-main-content` / `.user-main-content` - Main content area
- `.page-title-section` - Page title container
- `.page-title` - Page title heading

### Cards
- `.card` - Standard card container
- `.card-header` - Card header
- `.card-title` - Card title
- `.card-body` - Card body

### Metrics
- `.metrics-grid` - Grid container for metric cards
- `.metric-card` - Individual metric card
- `.metric-icon` - Metric icon container
- `.metric-title` - Metric title
- `.metric-value` - Metric value

### Data Tables
- `.data-section` - Section container for tables
- `.data-section-title` - Section title
- `.data-table` - Styled table
- `.stock-badge` - Stock status badge
- `.stock-badge.in-stock` - In stock badge
- `.stock-badge.out-of-stock` - Out of stock badge

## Benefits

1. **Consistency** - All pages look and behave the same
2. **Maintainability** - Update sidebar/menu in one place
3. **Speed** - Faster development with reusable components
4. **Mobile Support** - Responsive design built-in
5. **Error Handling** - Automatic flash message and error display

## Example: Complete Migration

**Old `admin/ecommerce/products.php`:**
```php
<?php
require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// ... logic ...

$pageTitle = 'Products - Admin - ' . APP_NAME;
include __DIR__ . '/../../includes/header.php';
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <!-- Old navbar -->
</nav>

<div class="container-fluid">
    <h2>Products</h2>
    <!-- Content -->
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
```

**New `admin/ecommerce/products.php`:**
```php
<?php
require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// ... same logic ...

ob_start();
?>
<div class="page-title-section">
    <h1 class="page-title">Products Management</h1>
    <a href="<?php echo BASE_URL; ?>/admin/ecommerce/products/add.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add New Product
    </a>
</div>

<div class="card">
    <div class="card-body">
        <!-- Same content, but using .data-table instead of .table -->
        <table class="data-table">
            <!-- Table content -->
        </table>
    </div>
</div>
<?php
$content = ob_get_clean();

$pageTitle = 'Products Management - Admin - ' . APP_NAME;
include __DIR__ . '/../../includes/layouts/admin-layout.php';
```

## Notes

- Remove old `<nav class="navbar...">` sections
- Remove `include __DIR__ . '/../../includes/header.php'` and `footer.php`
- Replace `.table` with `.data-table` for styled tables
- Use `.card` instead of `.container-fluid` for content sections
- Use `.page-title-section` for page headers
- Flash messages and errors are automatically handled

## Testing Checklist

After migrating each page:
- [ ] Sidebar appears correctly
- [ ] Header appears correctly
- [ ] Page content displays properly
- [ ] Flash messages work
- [ ] Errors display correctly
- [ ] Mobile responsive design works
- [ ] All links in sidebar work
- [ ] Menu highlights active page correctly







