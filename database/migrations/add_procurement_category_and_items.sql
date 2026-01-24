-- Migration: Add Procurement Category and Multiple Products Support
-- Date: 2025-11-09

-- Add category field to procurement_requests table
ALTER TABLE `procurement_requests` 
ADD COLUMN `category` enum('products_purchase','product_branding') DEFAULT 'products_purchase' AFTER `user_id`,
ADD COLUMN `needed_by` date DEFAULT NULL AFTER `budget_range`;

-- Create procurement_request_items table for multiple products
CREATE TABLE IF NOT EXISTS `procurement_request_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `specifications` text DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `reference_images` text DEFAULT NULL COMMENT 'JSON array',
  `item_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `request_id` (`request_id`),
  CONSTRAINT `procurement_request_items_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `procurement_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add branding-specific fields to procurement_requests for Product Branding category
ALTER TABLE `procurement_requests` 
ADD COLUMN `branding_type` varchar(100) DEFAULT NULL COMMENT 'logo, packaging, labels, etc.' AFTER `category`,
ADD COLUMN `branding_quantity` int(11) DEFAULT NULL AFTER `branding_type`,
ADD COLUMN `branding_material` varchar(255) DEFAULT NULL AFTER `branding_quantity`,
ADD COLUMN `branding_size` varchar(100) DEFAULT NULL AFTER `branding_material`,
ADD COLUMN `branding_color_scheme` varchar(255) DEFAULT NULL AFTER `branding_size`,
ADD COLUMN `branding_logo_file` varchar(255) DEFAULT NULL AFTER `branding_color_scheme`,
ADD COLUMN `branding_artwork_files` text DEFAULT NULL COMMENT 'JSON array' AFTER `branding_logo_file`,
ADD COLUMN `branding_notes` text DEFAULT NULL AFTER `branding_artwork_files`;



