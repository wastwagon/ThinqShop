<?php
/**
 * Update Shipments Table for Warehouse Support
 * Migration to add warehouse-related fields to shipments table
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check if columns exist before adding
    $stmt = $conn->query("SHOW COLUMNS FROM shipments LIKE 'forwarding_warehouse_id'");
    if ($stmt->rowCount() == 0) {
        $conn->exec("ALTER TABLE shipments ADD COLUMN forwarding_warehouse_id int(11) DEFAULT NULL AFTER delivery_address_id");
        $conn->exec("ALTER TABLE shipments ADD INDEX idx_forwarding_warehouse (forwarding_warehouse_id)");
        echo "✓ Added forwarding_warehouse_id column\n";
    } else {
        echo "✓ forwarding_warehouse_id column already exists\n";
    }
    
    $stmt = $conn->query("SHOW COLUMNS FROM shipments LIKE 'destination_warehouse_id'");
    if ($stmt->rowCount() == 0) {
        $conn->exec("ALTER TABLE shipments ADD COLUMN destination_warehouse_id int(11) DEFAULT NULL AFTER forwarding_warehouse_id");
        $conn->exec("ALTER TABLE shipments ADD INDEX idx_destination_warehouse (destination_warehouse_id)");
        echo "✓ Added destination_warehouse_id column\n";
    } else {
        echo "✓ destination_warehouse_id column already exists\n";
    }
    
    $stmt = $conn->query("SHOW COLUMNS FROM shipments LIKE 'shipping_method_type'");
    if ($stmt->rowCount() == 0) {
        $conn->exec("ALTER TABLE shipments ADD COLUMN shipping_method_type enum('air','sea') DEFAULT NULL AFTER service_type");
        echo "✓ Added shipping_method_type column\n";
    } else {
        echo "✓ shipping_method_type column already exists\n";
    }
    
    $stmt = $conn->query("SHOW COLUMNS FROM shipments LIKE 'shipping_rate_id'");
    if ($stmt->rowCount() == 0) {
        $conn->exec("ALTER TABLE shipments ADD COLUMN shipping_rate_id varchar(100) DEFAULT NULL AFTER shipping_method_type");
        echo "✓ Added shipping_rate_id column\n";
    } else {
        echo "✓ shipping_rate_id column already exists\n";
    }
    
    $stmt = $conn->query("SHOW COLUMNS FROM shipments LIKE 'product_declaration'");
    if ($stmt->rowCount() == 0) {
        $conn->exec("ALTER TABLE shipments ADD COLUMN product_declaration text DEFAULT NULL COMMENT 'JSON array of products with quantities and values' AFTER notes");
        echo "✓ Added product_declaration column\n";
    } else {
        echo "✓ product_declaration column already exists\n";
    }
    
    // Add foreign key constraints if they don't exist
    try {
        $conn->exec("ALTER TABLE shipments ADD CONSTRAINT fk_forwarding_warehouse FOREIGN KEY (forwarding_warehouse_id) REFERENCES warehouses(id) ON DELETE SET NULL");
        echo "✓ Added foreign key for forwarding_warehouse_id\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate foreign key') === false) {
            echo "Note: Foreign key constraint may already exist or table doesn't exist yet\n";
        }
    }
    
    try {
        $conn->exec("ALTER TABLE shipments ADD CONSTRAINT fk_destination_warehouse FOREIGN KEY (destination_warehouse_id) REFERENCES warehouses(id) ON DELETE SET NULL");
        echo "✓ Added foreign key for destination_warehouse_id\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate foreign key') === false) {
            echo "Note: Foreign key constraint may already exist or table doesn't exist yet\n";
        }
    }
    
    echo "\nMigration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

