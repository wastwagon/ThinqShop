# 🎨 CSS Refactoring - Quick Reference Guide

## 🌐 **Test Page Created!**

**URL:** `http://localhost:8080/css-test.html`

**What it shows:**
- ✅ All new CSS components
- ✅ Product cards with correct sizes
- ✅ Buttons (all variants)
- ✅ Forms
- ✅ Navigation elements
- ✅ Utility classes
- ✅ Everything working together

---

## 📊 **Size Comparison**

### **Product Cards:**
```
OLD:  Price 20px, Old Price 14px
NEW:  Price 12.25px, Old Price 9.625px ✅
```

### **Product Detail:**
```
OLD:  Price 32px, Buttons 1rem padding
NEW:  Price 15.75px, Buttons 0.5rem×0.75rem ✅
```

### **Quantity Controls:**
```
OLD:  44px × 44px
NEW:  32px × 32px ✅
```

---

## 🎯 **BEM Class Names Quick Reference**

### **Product Cards:**
```html
.product-card
  .product-card__image
    .product-card__discount-badge
  .product-card__content
    .product-card__category
    .product-card__name
    .product-card__rating
      .product-card__stars
      .product-card__rating-value
    .product-card__price-section
      .product-card__price-row
        .product-card__price
        .product-card__price--old
      .product-card__stock
        .product-card__stock--in-stock
        .product-card__stock--low-stock
        .product-card__stock--out-of-stock
```

### **Buttons:**
```html
.btn
  .btn--primary
  .btn--secondary
  .btn--success
  .btn--danger
  .btn--warning
  .btn--ghost
  .btn--sm
  .btn--lg
  .btn--block
```

### **Forms:**
```html
.form-group
  .form-label
    .form-label--required
  .form-input
  .form-select
  .form-textarea
  .form-checkbox
  .form-radio
  .form-help
  .form-error
```

---

## 🔧 **Common Utility Classes**

### **Display & Flex:**
```css
.d-flex          /* display: flex */
.flex-column     /* flex-direction: column */
.justify-center  /* justify-content: center */
.align-center    /* align-items: center */
.gap-sm          /* gap: 0.5rem */
.gap-md          /* gap: 0.75rem */
.gap-lg          /* gap: 1rem */
```

### **Spacing:**
```css
.m-0, .mt-sm, .mb-md, .ml-lg, .mr-xl
.p-0, .pt-sm, .pb-md, .pl-lg, .pr-xl
```

### **Text:**
```css
.text-xs, .text-sm, .text-base, .text-lg, .text-xl
.text-primary, .text-success, .text-danger
.font-bold, .font-semibold
.text-center, .text-left, .text-right
```

### **Colors:**
```css
.bg-primary, .bg-success, .bg-white
.text-primary, .text-muted
```

---

## 📝 **Testing Instructions**

### **1. Open Test Page**
```
http://localhost:8080/css-test.html
```

### **2. Check These Items:**
- [ ] Product card prices are small (12.25px)
- [ ] Buttons are compact
- [ ] Forms look good
- [ ] Everything is responsive
- [ ] Mobile view works
- [ ] All colors correct

### **3. Compare with Current Site:**
```
Current Site:  http://localhost:8080/
Test Page:     http://localhost:8080/css-test.html
```

### **4. Report Issues:**
If anything looks wrong, note:
- Which component?
- What's wrong?
- What should it be?

---

## ✅ **If Test Page Looks Good**

### **Next Steps:**
1. ✅ Confirm test page looks correct
2. Update actual pages to use new CSS
3. Test each page
4. Remove old CSS files
5. Done!

---

## 🚀 **Migration Process**

### **When You're Ready:**

**Step 1: Update Header**
```php
// In includes/header.php
// Replace all CSS links with:
<link rel="stylesheet" href="<?php echo asset('assets/css/main-new.css'); ?>?v=<?php echo time(); ?>">
```

**Step 2: Update PHP Files**
- Remove inline `<style>` tags
- Use new BEM class names
- Use utility classes

**Step 3: Test Everything**
- Homepage
- Shop page
- Product detail
- Cart
- Checkout

**Step 4: Clean Up**
- Remove old CSS files
- Remove inline styles
- Done!

---

## 📞 **Need Help?**

### **Common Issues:**

**Problem:** Styles not loading
**Solution:** Clear browser cache (Cmd/Ctrl + Shift + R)

**Problem:** Sizes still wrong
**Solution:** Check if old CSS is still loading

**Problem:** Classes not working
**Solution:** Verify class names match BEM structure

---

## 🎨 **Design System Values**

### **Colors:**
```css
--color-primary: #0e2945
--color-success: #059669
--color-warning: #d97706
--color-danger: #dc2626
```

### **Font Sizes:**
```css
--font-size-xs: 0.6875rem    (9.625px)
--font-size-sm: 0.75rem      (10.5px)
--font-size-md: 0.8125rem    (11.375px)
--font-size-base: 0.875rem   (12.25px)
--font-size-lg: 0.9375rem    (13.125px)
--font-size-xl: 1rem         (14px)
--font-size-2xl: 1.125rem    (15.75px)
```

### **Spacing:**
```css
--spacing-xs: 0.25rem
--spacing-sm: 0.5rem
--spacing-md: 0.75rem
--spacing-lg: 1rem
--spacing-xl: 1.5rem
```

---

## ✅ **Success Checklist**

- [ ] Test page opens correctly
- [ ] Product cards look good
- [ ] Prices are correct size (12.25px)
- [ ] Buttons are compact
- [ ] Forms work properly
- [ ] Mobile responsive works
- [ ] All components display correctly
- [ ] Ready to migrate live site

---

**Open the test page and let me know how it looks!** 🎨✨

**URL:** `http://localhost:8080/css-test.html`
