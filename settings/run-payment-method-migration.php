<?php
/**
 * Run Payment Method Enum Migration
 * Utility to update the payment_method enum in shipments table
 */

require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$message = '';
$type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
    try {
        // Get current ENUM values
        $stmt = $conn->query("SHOW COLUMNS FROM `shipments` LIKE 'payment_method'");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $currentEnum = $row['Type'];
        
        $message = "Current payment_method enum: " . $currentEnum . "\n\n";
        
        // Update the enum to include all required values
        $sql = "ALTER TABLE `shipments` MODIFY COLUMN `payment_method` ENUM('card','mobile_money','bank_transfer','wallet','cod') NOT NULL DEFAULT 'wallet'";
        $conn->exec($sql);
        
        $message .= "✓ Successfully updated payment_method enum to include all required values!\n";
        $message .= "✓ Values now include: card, mobile_money, bank_transfer, wallet, cod\n";
        $type = 'success';
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $type = 'danger';
    }
}

$pageTitle = 'Update Payment Method Enum - Admin - ' . APP_NAME;
ob_start();
?>
<div class="container-fluid">
    <h2 class="mb-4">Update Payment Method Enum</h2>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $type; ?> alert-dismissible fade show" role="alert">
            <pre style="white-space: pre-wrap;"><?php echo htmlspecialchars($message); ?></pre>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <p>This will update the <code>payment_method</code> column in the <code>shipments</code> table to ensure it includes all required values: <code>card</code>, <code>mobile_money</code>, <code>bank_transfer</code>, <code>wallet</code>, <code>cod</code>.</p>
            <form method="POST" action="">
                <button type="submit" name="run_migration" class="btn btn-primary">Update Payment Method Enum</button>
            </form>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin-layout.php';
?>

