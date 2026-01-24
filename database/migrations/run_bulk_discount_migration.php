<?php
/**
 * Migration Script: Add bulk_discount_tiers column to products table
 * Run this script once to enable bulk discount functionality
 */

require_once __DIR__ . '/../../config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "Starting migration: Add bulk_discount_tiers column...\n\n";

try {
    // Check if column already exists
    $stmt = $conn->query("SHOW COLUMNS FROM products LIKE 'bulk_discount_tiers'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Column 'bulk_discount_tiers' already exists. Migration not needed.\n";
        exit(0);
    }
    
    // Add the column (ALTER TABLE doesn't need a transaction in MySQL)
    $sql = "ALTER TABLE `products` 
            ADD COLUMN `bulk_discount_tiers` TEXT NULL COMMENT 'JSON array of discount tiers: [{\"min_qty\": 5, \"discount_percent\": 5}, {\"min_qty\": 10, \"discount_percent\": 10}]' 
            AFTER `compare_price`";
    
    echo "Executing: ALTER TABLE products ADD COLUMN bulk_discount_tiers...\n";
    $conn->exec($sql);
    
    echo "\n✓ Migration completed successfully!\n";
    echo "✓ bulk_discount_tiers column added to products table\n\n";
    echo "You can now set bulk discounts for products in the admin panel.\n";
    
} catch (Exception $e) {
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

