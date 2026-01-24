-- Migration: Add bulk_discount_tiers field to products table
-- This allows admins to set quantity-based discount tiers for each product

ALTER TABLE `products` 
ADD COLUMN `bulk_discount_tiers` TEXT NULL COMMENT 'JSON array of discount tiers: [{"min_qty": 5, "discount_percent": 5}, {"min_qty": 10, "discount_percent": 10}]' 
AFTER `compare_price`;



