# Bulk Purchase Discount Setup Guide

## Overview
This feature allows admins to set quantity-based discount tiers for individual products. Customers will see real-time notifications about bulk discounts as they adjust quantities.

## Database Migration

**Run this SQL to add the bulk_discount_tiers field:**

```sql
ALTER TABLE `products` 
ADD COLUMN `bulk_discount_tiers` TEXT NULL COMMENT 'JSON array of discount tiers: [{"min_qty": 5, "discount_percent": 5}, {"min_qty": 10, "discount_percent": 10}]' 
AFTER `compare_price`;
```

**Or use the migration file:**
- Location: `database/migrations/add_bulk_discount_tiers.sql`
- Run via phpMyAdmin or MySQL command line

## Admin Features

### Setting Bulk Discounts

1. **Go to Product Edit/Add Page:**
   - Admin → Products → Edit Product (or Add New Product)

2. **Find "Bulk Purchase Discounts" Section:**
   - Located after "Pricing & Inventory" section

3. **Add Discount Tiers:**
   - Click "Add Discount Tier" button
   - Enter:
     - **Minimum Quantity**: The minimum number of items required (e.g., 5)
     - **Discount Percentage**: The discount percentage (e.g., 10 for 10% off)
   - Click "Add Discount Tier" again to add more tiers
   - Use the X button to remove tiers

4. **Example Setup:**
   - Tier 1: Min Qty 5, Discount 5%
   - Tier 2: Min Qty 10, Discount 10%
   - Tier 3: Min Qty 20, Discount 15%

5. **Save the Product:**
   - Click "Update Product" or "Save Product"
   - Discount tiers are automatically sorted by minimum quantity

## Frontend Features

### Customer Experience

When a customer views a product with bulk discounts:

1. **Quantity Selection:**
   - Customer enters quantity in the quantity field

2. **Real-time Notifications:**
   - **If quantity qualifies for discount:**
     - Green success alert shows:
       - Discount percentage applied
       - Amount saved
       - Total price after discount
   
   - **If quantity doesn't qualify:**
     - Blue info alert shows:
       - How many more items needed
       - Discount percentage they'll get

3. **Example Notifications:**
   - **Qualified:** "Bulk Discount Applied! You're getting 10% off for ordering 10 or more items. You save GHS 65.00! Total: GHS 585.00"
   - **Not Qualified:** "Bulk Discount Available! Add 3 more items to get 10% off on your order!"

## Technical Details

### Data Structure

Bulk discount tiers are stored as JSON in the `bulk_discount_tiers` field:

```json
[
  {"min_qty": 5, "discount_percent": 5},
  {"min_qty": 10, "discount_percent": 10},
  {"min_qty": 20, "discount_percent": 15}
]
```

### Discount Calculation

- **Discount Amount** = (Product Price × Quantity × Discount Percent) / 100
- **Discounted Price** = (Product Price × Quantity) - Discount Amount

### Files Modified

1. **Admin:**
   - `admin/ecommerce/products-edit.php` - Edit product form
   - `admin/ecommerce/products/add.php` - Add product form

2. **Frontend:**
   - `product-detail.php` - Product detail page with notifications

3. **Database:**
   - `database/migrations/add_bulk_discount_tiers.sql` - Migration script

## Usage Example

**Admin sets up:**
- Product: PS5 Console
- Price: GHS 6,500
- Bulk Discount Tiers:
  - 5+ items: 5% off
  - 10+ items: 10% off
  - 20+ items: 15% off

**Customer experience:**
- Selects 3 items → Sees: "Add 2 more items to get 5% off!"
- Selects 5 items → Sees: "Bulk Discount Applied! You're getting 5% off. You save GHS 1,625.00!"
- Selects 10 items → Sees: "Bulk Discount Applied! You're getting 10% off. You save GHS 6,500.00!"

## Notes

- Discounts are calculated based on the base product price
- Multiple tiers are supported per product
- Tiers are automatically sorted by minimum quantity
- Notifications update in real-time as quantity changes
- Works with products that have or don't have variants



