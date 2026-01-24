# Product Categories Multi-Select Setup

## Overview
This update adds the ability to:
1. Manage product categories in Admin Settings
2. Assign multiple categories to products (multi-select)
3. Maintain backward compatibility with existing single-category products

## What Was Added

### 1. Database Migration
- **File**: `database/migrations/create_product_categories_table.sql`
- Creates a `product_categories` junction table for many-to-many relationships
- Migrates existing `category_id` data to the new table
- Maintains `category_id` in products table for backward compatibility

### 2. Admin Settings - Product Categories Management
- **File**: `admin/settings/categories.php`
- Add, edit, and delete categories
- View category hierarchy and product counts
- Accessible from Admin Settings → Product Categories tab

### 3. Updated Product Forms
- **Files**: 
  - `admin/ecommerce/products/add.php`
  - `admin/ecommerce/products-edit.php`
- Changed from single-select dropdown to multi-select dropdown
- Products can now belong to multiple categories
- First selected category is set as primary (for backward compatibility)

## Setup Instructions

### Step 1: Run the Database Migration

**Option A: Using PHP Script (Recommended)**
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/ThinQShopping
php database/migrations/run_product_categories_migration.php
```

**Option B: Using phpMyAdmin**
1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Select your database (`thinqshopping_db`)
3. Go to SQL tab
4. Copy and paste the contents of `database/migrations/create_product_categories_table.sql`
5. Click "Go" to execute

**Option C: Using MySQL Command Line**
```bash
mysql -u root -p thinqshopping_db < database/migrations/create_product_categories_table.sql
```

### Step 2: Access Product Categories Management

1. Log in to Admin Dashboard
2. Go to **Settings** → **Product Categories** tab
3. Or directly: `http://localhost/ThinQShopping/admin/settings/categories.php`

### Step 3: Add Categories (Optional)

If you want to add new categories:
1. Go to Admin Settings → Product Categories
2. Use the form on the left to add new categories
3. Categories can have parent categories for hierarchy

### Step 4: Update Existing Products (Optional)

1. Go to Products → Edit any product
2. You'll now see a multi-select dropdown for categories
3. Select multiple categories (hold Ctrl/Cmd to select multiple)
4. Save the product

## How to Use Multi-Select Categories

### In Product Add/Edit Forms:
1. The category dropdown is now a multi-select list
2. **To select multiple categories:**
   - **Windows/Linux**: Hold `Ctrl` and click on categories
   - **Mac**: Hold `Cmd` and click on categories
3. The first selected category becomes the primary category
4. At least one category must be selected

### In Category Management:
- **Add Category**: Fill the form and click "Add Category"
- **Edit Category**: Click the edit icon (pencil) next to a category
- **Delete Category**: Click the delete icon (trash) - only works if category has no products

## Technical Details

### Database Structure

**New Table: `product_categories`**
```sql
- id (primary key)
- product_id (foreign key to products)
- category_id (foreign key to categories)
- is_primary (boolean - first category is primary)
- created_at (timestamp)
```

### Backward Compatibility

- The `products.category_id` field is still maintained
- It stores the primary (first) category
- Existing code that uses `category_id` will continue to work
- The `product_categories` table is checked first, falls back to `category_id` if table doesn't exist

### Code Changes

1. **Product Forms**: Changed from `name="category_id"` to `name="category_ids[]"`
2. **Form Processing**: Now handles arrays of category IDs
3. **Database Operations**: Inserts/updates in `product_categories` table
4. **Display Logic**: Falls back gracefully if `product_categories` table doesn't exist

## Troubleshooting

### Migration Fails
- Check database connection in `config/database.php`
- Ensure you have proper database permissions
- Check if `product_categories` table already exists

### Categories Not Showing in Dropdown
- Ensure categories are marked as `is_active = 1`
- Check database connection
- Clear browser cache

### Multi-Select Not Working
- Ensure JavaScript is enabled in browser
- Try a different browser
- Check browser console for errors

## Files Modified

1. `admin/settings/general.php` - Added "Product Categories" tab
2. `admin/settings/categories.php` - New category management page
3. `admin/ecommerce/products/add.php` - Multi-select categories
4. `admin/ecommerce/products-edit.php` - Multi-select categories
5. `database/migrations/create_product_categories_table.sql` - Migration SQL
6. `database/migrations/run_product_categories_migration.php` - Migration script

## Next Steps (Optional Enhancements)

- Add category icons/images
- Add category descriptions on frontend
- Filter products by multiple categories on shop page
- Display all categories a product belongs to on product detail page
- Add category breadcrumbs



