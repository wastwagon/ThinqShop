<?php
/**
 * Database Migration: Add Shipping Methods and Settings
 * Run this file once via browser or command line to update the database
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Shipping Methods Migration</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            h2 { color: #333; }
            .success { color: green; font-weight: bold; }
            .error { color: red; font-weight: bold; }
            .info { color: #666; }
        </style>
    </head>
    <body>";
    
    echo "<h2>Updating Database Schema...</h2>";
    
    // Check if country column exists
    $stmt = $conn->query("SHOW COLUMNS FROM addresses LIKE 'country'");
    if ($stmt->rowCount() == 0) {
        echo "<p>Adding country field to addresses table...</p>";
        $conn->exec("ALTER TABLE `addresses` ADD COLUMN `country` varchar(100) DEFAULT 'Ghana' AFTER `region`");
        echo "<p class='success'>✓ Country field added to addresses table!</p>";
    } else {
        echo "<p class='info'>Country field already exists in addresses table.</p>";
    }
    
    // Check if shipping_methods table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'shipping_methods'");
    if ($stmt->rowCount() == 0) {
        echo "<p>Creating shipping_methods table...</p>";
        $conn->exec("
            CREATE TABLE IF NOT EXISTS `shipping_methods` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `method_name` varchar(100) NOT NULL,
              `method_code` varchar(50) NOT NULL,
              `description` text DEFAULT NULL,
              `base_price` decimal(10,2) NOT NULL,
              `per_kg_price` decimal(10,2) NOT NULL,
              `min_days` int(11) NOT NULL COMMENT 'Minimum delivery days',
              `max_days` int(11) NOT NULL COMMENT 'Maximum delivery days',
              `available_countries` text DEFAULT NULL COMMENT 'JSON array of country codes',
              `is_active` tinyint(1) DEFAULT 1,
              `sort_order` int(11) DEFAULT 0,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `method_code` (`method_code`),
              KEY `is_active` (`is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p class='success'>✓ shipping_methods table created!</p>";
        
        // Insert default shipping methods
        echo "<p>Inserting default shipping methods...</p>";
        $conn->exec("
            INSERT INTO `shipping_methods` (`method_name`, `method_code`, `description`, `base_price`, `per_kg_price`, `min_days`, `max_days`, `available_countries`, `is_active`, `sort_order`) VALUES
            ('Economy Shipping', 'economy', 'Most affordable option for non-urgent shipments. Standard tracking included.', 50.00, 15.00, 15, 25, '[\"GH\",\"CN\",\"US\",\"UK\",\"NG\",\"ZA\",\"KE\",\"TZ\",\"UG\"]', 1, 1),
            ('Standard Shipping', 'standard', 'Balanced option with faster delivery and full tracking. Recommended for most shipments.', 75.00, 20.00, 10, 18, '[\"GH\",\"CN\",\"US\",\"UK\",\"NG\",\"ZA\",\"KE\",\"TZ\",\"UG\"]', 1, 2),
            ('Express Shipping', 'express', 'Faster delivery with priority handling and real-time tracking updates.', 120.00, 30.00, 5, 10, '[\"GH\",\"CN\",\"US\",\"UK\",\"NG\",\"ZA\",\"KE\",\"TZ\",\"UG\"]', 1, 3),
            ('Priority Shipping', 'priority', 'Fastest option with expedited processing and dedicated customer support.', 200.00, 50.00, 3, 7, '[\"GH\",\"CN\",\"US\",\"UK\",\"NG\",\"ZA\",\"KE\",\"TZ\",\"UG\"]', 1, 4)
        ");
        echo "<p class='success'>✓ Default shipping methods inserted!</p>";
    } else {
        echo "<p class='info'>shipping_methods table already exists.</p>";
    }
    
    // Check if shipping_settings table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'shipping_settings'");
    if ($stmt->rowCount() == 0) {
        echo "<p>Creating shipping_settings table...</p>";
        $conn->exec("
            CREATE TABLE IF NOT EXISTS `shipping_settings` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `setting_key` varchar(100) NOT NULL,
              `setting_value` text DEFAULT NULL,
              `setting_type` varchar(50) DEFAULT 'text',
              `description` text DEFAULT NULL,
              `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `setting_key` (`setting_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p class='success'>✓ shipping_settings table created!</p>";
        
        // Insert default settings
        echo "<p>Inserting default shipping settings...</p>";
        $conn->exec("
            INSERT INTO `shipping_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
            ('free_shipping_threshold', '500.00', 'decimal', 'Minimum order amount for free shipping (GHS)'),
            ('cod_fee_percentage', '2.5', 'decimal', 'Cash on Delivery fee percentage'),
            ('insurance_enabled', '1', 'boolean', 'Enable shipping insurance'),
            ('insurance_rate', '0.5', 'decimal', 'Insurance rate as percentage of shipment value'),
            ('fuel_surcharge_enabled', '1', 'boolean', 'Enable fuel surcharge'),
            ('fuel_surcharge_rate', '3.0', 'decimal', 'Fuel surcharge as percentage of shipping cost'),
            ('overseas_surcharge', '15.0', 'decimal', 'Overseas shipping surcharge percentage'),
            ('currency_conversion_rate', '1', 'decimal', 'Base currency conversion rate (if applicable)')
        ");
        echo "<p class='success'>✓ Default shipping settings inserted!</p>";
    } else {
        echo "<p class='info'>shipping_settings table already exists.</p>";
    }
    
    echo "<hr>";
    echo "<h2 class='success'>✓ Database migration completed successfully!</h2>";
    echo "<p>You can now use the enhanced shipping system with address management and shipping methods.</p>";
    echo "<p><a href='/ThinQShopping/modules/logistics/booking/'>Go to Booking Form</a></p>";
    
    echo "</body></html>";
    
} catch (PDOException $e) {
    echo "<h2 class='error'>Error:</h2>";
    echo "<p class='error'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</body></html>";
}








