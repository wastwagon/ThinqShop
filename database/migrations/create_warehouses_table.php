<?php
/**
 * Create Warehouses Table
 * Migration to add warehouses table for forwarding and destination warehouses
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Create warehouses table
    $sql = "CREATE TABLE IF NOT EXISTS `warehouses` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `warehouse_name` varchar(255) NOT NULL,
        `warehouse_code` varchar(50) DEFAULT NULL,
        `receiver_name` varchar(255) NOT NULL,
        `receiver_phone` varchar(20) NOT NULL,
        `address_english` text NOT NULL,
        `address_chinese` text NOT NULL,
        `district` varchar(100) DEFAULT NULL,
        `city` varchar(100) NOT NULL,
        `country` varchar(100) NOT NULL,
        `warehouse_type` enum('forwarding','destination') NOT NULL DEFAULT 'forwarding',
        `is_active` tinyint(1) DEFAULT 1,
        `sort_order` int(11) DEFAULT 0,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `warehouse_type` (`warehouse_type`),
        KEY `is_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "✓ Warehouses table created successfully!\n";
    
    // Insert default forwarding warehouse (Guangzhou)
    $stmt = $conn->prepare("
        INSERT INTO warehouses (
            warehouse_name, warehouse_code, receiver_name, receiver_phone,
            address_english, address_chinese, district, city, country, warehouse_type, is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'forwarding', 1)
    ");
    
    $stmt->execute([
        'ThinQShopping Main 1',
        'TQ-GZ-001',
        'ThinQ',
        '18320709024',
        'Room 08, Shop No. 499-523, Fourth Floor, San Yuan Li Avenue, Yuexiu District, Guangzhou',
        '广州市越秀区三元里大道499-523号四楼08号商铺',
        'Yuexiu District',
        'Guangzhou',
        'China'
    ]);
    echo "✓ Default forwarding warehouse inserted!\n";
    
    // Insert default destination warehouse (Lapaz - Accra - Ghana)
    $stmt = $conn->prepare("
        INSERT INTO warehouses (
            warehouse_name, warehouse_code, receiver_name, receiver_phone,
            address_english, address_chinese, district, city, country, warehouse_type, is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'destination', 1)
    ");
    
    $stmt->execute([
        'Lapaz Warehouse',
        'TQ-LP-001',
        'ThinQShopping',
        '+233XXXXXXXXX',
        'Lapaz, Accra, Ghana',
        'Lapaz, Accra, Ghana',
        'Lapaz',
        'Accra',
        'Ghana'
    ]);
    echo "✓ Default destination warehouse inserted!\n";
    
    echo "\nMigration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

