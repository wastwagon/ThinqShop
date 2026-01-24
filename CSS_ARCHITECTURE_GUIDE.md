# CSS Architecture Quick Reference

## 📁 File Organization

### **Main Entry Point**
- `assets/css/main-new.css` - Imports all base styles and common components

### **Base Styles**
- `assets/css/base/reset.css` - CSS reset
- `assets/css/base/variables.css` - CSS custom properties
- `assets/css/base/typography.css` - Font styles

### **Components** (`assets/css/components/`)
Reusable UI components used across multiple pages:
- `buttons.css` - Button styles
- `cards.css` - Card components
- `forms.css` - Form elements
- `navigation.css` - Navigation menus
- `quick-view.css` - Product quick view modal
- `admin-sidebar.css` - Admin sidebar navigation

### **Pages** (`assets/css/pages/`)
Page-specific styles:

**User Pages:**
- `user-orders.css`
- `user-order-view.css`
- `user-shipments.css`
- `user-procurement-view.css`
- `user-procurement-quote-view.css`
- `user-profile.css`
- `user-tickets.css`
- `user-ticket-create.css`
- `user-ticket-view.css`
- `user-transfer-view.css`
- `user-wallet.css`
- `user-wishlist.css`

**Admin Pages:**
- `admin-dashboard.css`
- `admin-notifications.css`
- `admin-ticket-view.css`

**Public Pages:**
- `legal.css` (terms & privacy)
- `help.css`
- `procurement-request.css`

---

## 🔧 Usage Patterns

### **Pattern 1: Page-Specific CSS**
Use `$additionalCSS` array to include page-specific styles:

```php
<?php
// Start output buffering
ob_start();
?>
<!-- Your page content here -->
<?php
$content = ob_get_clean();

// Add page-specific CSS
$additionalCSS = [
    BASE_URL . '/assets/css/pages/my-page.css'
];

// Include layout
$pageTitle = 'My Page - ' . APP_NAME;
include __DIR__ . '/includes/layouts/user-layout.php';
?>
```

### **Pattern 2: Component CSS in Layouts**
For shared components, link CSS directly in layout files:

```php
<!-- In admin-layout.php -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/components/admin-sidebar.css">
```

### **Pattern 3: Multiple CSS Files**
You can include multiple CSS files:

```php
$additionalCSS = [
    BASE_URL . '/assets/css/pages/my-page.css',
    BASE_URL . '/assets/css/components/my-component.css'
];
```

---

## 🎨 CSS Naming Conventions

### **BEM Methodology**
Use Block Element Modifier (BEM) naming:

```css
/* Block */
.card { }

/* Element */
.card__header { }
.card__body { }
.card__footer { }

/* Modifier */
.card--featured { }
.card--large { }
```

### **Utility Classes**
Use descriptive utility classes:

```css
.text-center { text-align: center; }
.mb-3 { margin-bottom: 1rem; }
.fw-bold { font-weight: bold; }
```

---

## 📋 CSS File Template

```css
/* ===================================
   [Page/Component Name]
   Description: Brief description
   =================================== */

/* Main Container */
.page-container {
    /* styles */
}

/* Sections */
.section-header {
    /* styles */
}

.section-content {
    /* styles */
}

/* Components */
.component-name {
    /* styles */
}

/* Responsive */
@media (max-width: 768px) {
    .page-container {
        /* mobile styles */
    }
}
```

---

## ✅ Best Practices

1. **One CSS file per page** - Keep page-specific styles in dedicated files
2. **Reuse components** - Use existing component styles when possible
3. **Mobile-first** - Write base styles for mobile, then add desktop styles
4. **Use variables** - Leverage CSS custom properties from `variables.css`
5. **Avoid !important** - Use proper specificity instead
6. **Comment sections** - Add comments to organize CSS sections
7. **Keep it DRY** - Don't repeat yourself, extract common patterns

---

## 🔍 Finding Styles

**To find where a style is defined:**
1. Check `assets/css/components/` for reusable components
2. Check `assets/css/pages/` for page-specific styles
3. Check `assets/css/base/` for global styles

**To add new styles:**
1. Determine if it's a component (reusable) or page-specific
2. Create/update the appropriate CSS file
3. Link it using `$additionalCSS` or in the layout file

---

## 🚀 Quick Start

### **Creating a New Page**

1. **Create PHP file:**
```php
<?php
require_once __DIR__ . '/includes/auth-check.php';
ob_start();
?>
<div class="my-page">
    <h1>My Page</h1>
</div>
<?php
$content = ob_get_clean();
$additionalCSS = [BASE_URL . '/assets/css/pages/my-page.css'];
$pageTitle = 'My Page - ' . APP_NAME;
include __DIR__ . '/includes/layouts/user-layout.php';
?>
```

2. **Create CSS file:** `assets/css/pages/my-page.css`
```css
/* My Page Styles */
.my-page {
    padding: 2rem;
}
```

3. **Test the page** - Verify styles are loading correctly

---

## 📞 Support

If you encounter any issues:
1. Check browser console for CSS errors
2. Verify file paths are correct
3. Clear browser cache
4. Check that `BASE_URL` is defined correctly

---

**Happy Styling! 🎨**
