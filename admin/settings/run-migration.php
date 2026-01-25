<?php
/**
 * Run Shipping Rates Migration
 * This file can be accessed via browser to run the migration
 */

require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Run Migration - Shipping Rates</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin-top: 10px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Run Shipping Rates Migration</h1>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
            echo "<h2>Migration Output:</h2>";
            echo "<pre>";
            
            try {
                $db = new Database();
                $conn = $db->getConnection();
                
                // Create shipping_rates table
                $sql = "CREATE TABLE IF NOT EXISTS `shipping_rates` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `method_type` enum('air','sea') NOT NULL,
                    `rate_id` varchar(50) NOT NULL,
                    `rate_name` varchar(255) NOT NULL,
                    `rate_value` decimal(10,2) NOT NULL,
                    `rate_type` enum('kg','cbm','unit') NOT NULL DEFAULT 'kg',
                    `currency` varchar(10) DEFAULT 'GHS',
                    `duration` varchar(50) DEFAULT NULL,
                    `description` text DEFAULT NULL,
                    `is_active` tinyint(1) DEFAULT 1,
                    `sort_order` int(11) DEFAULT 0,
                    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `unique_rate` (`method_type`, `rate_id`),
                    KEY `method_type` (`method_type`),
                    KEY `is_active` (`is_active`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
                
                $conn->exec($sql);
                echo "✓ Shipping rates table created successfully!\n";
                
                // Insert default sea rates
                $seaRates = [
                    ['sea_standard', 'Sea Standard', 245, 'cbm', '45-60 days', 'Standard sea freight per CBM']
                ];
                
                foreach ($seaRates as $rate) {
                    $stmt = $conn->prepare("
                        INSERT IGNORE INTO shipping_rates (method_type, rate_id, rate_name, rate_value, rate_type, duration, description, is_active)
                        VALUES ('sea', ?, ?, ?, ?, ?, ?, 1)
                    ");
                    $stmt->execute($rate);
                }
                echo "✓ Default sea rates inserted!\n";
                
                // Insert default air rates
                $airRates = [
                    ['air_express', 'Express (3-5 days)', 17, 'kg', '3-5 days', 'Express air freight'],
                    ['air_normal', 'Normal (7-14 days)', 13, 'kg', '7-14 days', 'Normal air freight'],
                    ['air_special', 'Special/Battery Goods', 20, 'kg', '7-14 days', 'Special items including batteries'],
                    ['air_phone', 'Phone', 150, 'unit', '7-14 days', 'Per phone unit'],
                    ['air_laptop', 'Laptop', 200, 'kg', '7-14 days', 'Per kg for laptops']
                ];
                
                foreach ($airRates as $rate) {
                    $stmt = $conn->prepare("
                        INSERT IGNORE INTO shipping_rates (method_type, rate_id, rate_name, rate_value, rate_type, duration, description, is_active)
                        VALUES ('air', ?, ?, ?, ?, ?, ?, 1)
                    ");
                    $stmt->execute($rate);
                }
                echo "✓ Default air rates inserted!\n";
                
                echo "\n✓ Migration completed successfully!\n";
                echo "</pre>";
                echo "<div class='success'><strong>Success!</strong> The shipping_rates table has been created and populated with default rates.</div>";
                echo "<a href='shipping-rates.php' class='btn'>Go to Shipping Rates Management</a>";
                
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage() . "\n";
                echo "</pre>";
                echo "<div class='error'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        } else {
            ?>
            <p>This will create the <code>shipping_rates</code> table and populate it with default shipping rates.</p>
            <form method="POST">
                <button type="submit" name="run_migration" class="btn">Run Migration</button>
            </form>
            <?php
        }
        ?>
    </div>
</body>
</html>

