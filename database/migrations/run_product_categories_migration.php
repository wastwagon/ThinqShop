<?php
/**
 * Migration Script: Create product_categories junction table
 * Run this script once to enable multi-category support for products
 */

require_once __DIR__ . '/../../config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "Starting migration: Create product_categories table...\n\n";

try {
    $conn->beginTransaction();
    
    // Read the SQL migration file
    $sqlFile = __DIR__ . '/create_product_categories_table.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Migration SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split SQL statements (handle multiple statements)
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    foreach ($statements as $statement) {
        if (!empty(trim($statement))) {
            echo "Executing: " . substr($statement, 0, 50) . "...\n";
            $conn->exec($statement);
        }
    }
    
    $conn->commit();
    
    echo "\n✓ Migration completed successfully!\n";
    echo "✓ product_categories table created\n";
    echo "✓ Existing product categories migrated\n\n";
    echo "You can now use multi-select categories in product forms.\n";
    
} catch (Exception $e) {
    $conn->rollBack();
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    echo "Rolling back changes...\n";
    exit(1);
}



