<?php
/**
 * Comprehensive Email Templates Diagnostic Tool
 * This script verifies template existence, counts, and queries
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html><html><head><title>Email Templates Diagnostic</title>";
echo "<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; background: #f5f5f5; }
    .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 3px solid #0d6efd; padding-bottom: 10px; }
    h2 { color: #555; margin-top: 30px; border-bottom: 2px solid #ddd; padding-bottom: 8px; }
    .success { color: #198754; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    .warning { color: #ffc107; font-weight: bold; }
    .info { color: #0dcaf0; font-weight: bold; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; background: white; }
    th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
    th { background: #f8f9fa; font-weight: bold; color: #333; }
    tr:nth-child(even) { background: #f8f9fa; }
    .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
    .badge-success { background: #d1e7dd; color: #0f5132; }
    .badge-danger { background: #f8d7da; color: #842029; }
    .badge-warning { background: #fff3cd; color: #664d03; }
    .code-block { background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #0d6efd; margin: 10px 0; font-family: 'Courier New', monospace; overflow-x: auto; }
    .action-btn { display: inline-block; padding: 10px 20px; background: #0d6efd; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px 10px 0; }
    .action-btn:hover { background: #0b5ed7; }
    .action-btn-success { background: #198754; }
    .action-btn-success:hover { background: #157347; }
    .section { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🔍 Email Templates Diagnostic Tool</h1>";

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // ============================================
    // 1. TABLE EXISTENCE CHECK
    // ============================================
    echo "<h2>1. Database Table Check</h2>";
    echo "<div class='section'>";
    
    try {
        $stmt = $conn->query("SHOW TABLES LIKE 'email_templates'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✅ email_templates table exists</p>";
            
            // Get table structure
            $stmt = $conn->query("DESCRIBE email_templates");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<p><strong>Table Structure:</strong></p>";
            echo "<table>";
            echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>❌ email_templates table does NOT exist</p>";
            echo "<p><a href='run-migration.php' class='action-btn'>Run Migration</a></p>";
            echo "</div></div></body></html>";
            exit;
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error checking table: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div></div></body></html>";
        exit;
    }
    echo "</div>";
    
    // ============================================
    // 2. TEMPLATE COUNT
    // ============================================
    echo "<h2>2. Template Count</h2>";
    echo "<div class='section'>";
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM email_templates");
    $count = $stmt->fetch()['count'];
    echo "<p><strong>Total templates in database:</strong> <span class='info'>$count</span></p>";
    
    $expectedCount = 18; // 5 original + 13 new
    if ($count < $expectedCount) {
        echo "<p class='warning'>⚠️ Expected at least $expectedCount templates, but found only $count</p>";
        echo "<p><a href='insert-default-templates.php' class='action-btn action-btn-success'>Insert Missing Templates</a></p>";
    } else {
        echo "<p class='success'>✅ Template count looks good!</p>";
    }
    
    // Count by status
    $stmt = $conn->query("SELECT is_active, COUNT(*) as count FROM email_templates GROUP BY is_active");
    $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p><strong>By Status:</strong></p>";
    echo "<ul>";
    foreach ($statusCounts as $status) {
        $statusText = $status['is_active'] ? 'Active' : 'Inactive';
        echo "<li>$statusText: " . $status['count'] . "</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    // ============================================
    // 3. ALL TEMPLATES LIST
    // ============================================
    echo "<h2>3. All Templates in Database</h2>";
    echo "<div class='section'>";
    
    $stmt = $conn->query("SELECT id, template_key, template_name, is_active, created_at, updated_at FROM email_templates ORDER BY template_name ASC");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($templates)) {
        echo "<p class='error'>❌ No templates found in database!</p>";
        echo "<p><a href='insert-default-templates.php' class='action-btn action-btn-success'>Insert Default Templates</a></p>";
    } else {
        echo "<p><strong>Found " . count($templates) . " templates:</strong></p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Template Key</th><th>Template Name</th><th>Status</th><th>Created</th><th>Updated</th></tr>";
        foreach ($templates as $template) {
            $statusBadge = $template['is_active'] ? 
                '<span class="badge badge-success">Active</span>' : 
                '<span class="badge badge-danger">Inactive</span>';
            echo "<tr>";
            echo "<td>" . htmlspecialchars($template['id']) . "</td>";
            echo "<td><code>" . htmlspecialchars($template['template_key']) . "</code></td>";
            echo "<td>" . htmlspecialchars($template['template_name']) . "</td>";
            echo "<td>$statusBadge</td>";
            echo "<td>" . htmlspecialchars($template['created_at']) . "</td>";
            echo "<td>" . htmlspecialchars($template['updated_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    // ============================================
    // 4. EXPECTED TEMPLATES CHECK
    // ============================================
    echo "<h2>4. Expected Templates Verification</h2>";
    echo "<div class='section'>";
    
    $expectedTemplates = [
        'user_registration',
        'email_verified',
        'order_confirmation',
        'order_status_update',
        'order_shipped',
        'order_delivered',
        'transfer_confirmation',
        'transfer_status_update',
        'transfer_completed',
        'shipment_created',
        'shipment_status_update',
        'shipment_delivered',
        'activity_summary',
        'ticket_created',
        'ticket_reply',
        'new_order_admin',
        'new_transfer_admin',
        'new_shipment_admin'
    ];
    
    $stmt = $conn->query("SELECT template_key FROM email_templates");
    $existingKeys = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p><strong>Checking for expected templates:</strong></p>";
    echo "<table>";
    echo "<tr><th>Template Key</th><th>Status</th><th>Template Name</th></tr>";
    
    $missingCount = 0;
    foreach ($expectedTemplates as $key) {
        if (in_array($key, $existingKeys)) {
            $stmt = $conn->prepare("SELECT template_name FROM email_templates WHERE template_key = ?");
            $stmt->execute([$key]);
            $name = $stmt->fetchColumn();
            echo "<tr>";
            echo "<td><code>" . htmlspecialchars($key) . "</code></td>";
            echo "<td><span class='badge badge-success'>✅ Found</span></td>";
            echo "<td>" . htmlspecialchars($name) . "</td>";
            echo "</tr>";
        } else {
            $missingCount++;
            echo "<tr>";
            echo "<td><code>" . htmlspecialchars($key) . "</code></td>";
            echo "<td><span class='badge badge-danger'>❌ Missing</span></td>";
            echo "<td>-</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    
    if ($missingCount > 0) {
        echo "<p class='error'>❌ $missingCount expected templates are missing!</p>";
        echo "<p><a href='insert-default-templates.php' class='action-btn action-btn-success'>Insert Missing Templates</a></p>";
    } else {
        echo "<p class='success'>✅ All expected templates are present!</p>";
    }
    echo "</div>";
    
    // ============================================
    // 5. PAGE QUERY TEST
    // ============================================
    echo "<h2>5. Page Query Test</h2>";
    echo "<div class='section'>";
    
    echo "<p><strong>Testing the exact query used by the Email Templates page:</strong></p>";
    echo "<div class='code-block'>SELECT * FROM email_templates ORDER BY template_name ASC</div>";
    
    try {
        $stmt = $conn->query("SELECT * FROM email_templates ORDER BY template_name ASC");
        $pageTemplates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p class='success'>✅ Query executed successfully</p>";
        echo "<p><strong>Results:</strong> " . count($pageTemplates) . " templates returned</p>";
        
        if (count($pageTemplates) > 0) {
            echo "<p class='success'>✅ The page should be able to load " . count($pageTemplates) . " templates</p>";
            echo "<p><strong>First 5 templates (as they would appear on the page):</strong></p>";
            echo "<ul>";
            foreach (array_slice($pageTemplates, 0, 5) as $template) {
                echo "<li>" . htmlspecialchars($template['template_name']) . " (<code>" . htmlspecialchars($template['template_key']) . "</code>)</li>";
            }
            echo "</ul>";
        } else {
            echo "<p class='error'>❌ Query returns empty array - templates need to be inserted</p>";
            echo "<p><a href='insert-default-templates.php' class='action-btn action-btn-success'>Insert Default Templates</a></p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Query failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    echo "</div>";
    
    // ============================================
    // 6. RECOMMENDATIONS
    // ============================================
    echo "<h2>6. Recommendations</h2>";
    echo "<div class='section'>";
    
    if ($count < $expectedCount) {
        echo "<p class='warning'><strong>⚠️ Action Required:</strong></p>";
        echo "<ol>";
        echo "<li>Run the template insertion script: <a href='insert-default-templates.php' class='action-btn action-btn-success'>Insert Default Templates</a></li>";
        echo "<li>After insertion, refresh this diagnostic page to verify</li>";
        echo "<li>Then refresh the Email Templates admin page</li>";
        echo "</ol>";
    } else {
        echo "<p class='success'><strong>✅ All templates are present in the database!</strong></p>";
        echo "<p>If you're still not seeing them on the admin page:</p>";
        echo "<ol>";
        echo "<li>Clear your browser cache</li>";
        echo "<li>Hard refresh the Email Templates page (Ctrl+F5 or Cmd+Shift+R)</li>";
        echo "<li>Check if there are any JavaScript errors in the browser console</li>";
        echo "<li>Verify the admin page is using the correct database connection</li>";
        echo "</ol>";
    }
    echo "</div>";
    
    // ============================================
    // 7. QUICK ACTIONS
    // ============================================
    echo "<h2>7. Quick Actions</h2>";
    echo "<div class='section'>";
    echo "<p>";
    echo "<a href='insert-default-templates.php' class='action-btn action-btn-success'>Insert/Update All Templates</a>";
    echo "<a href='admin/settings/email-templates.php' class='action-btn'>Go to Email Templates Page</a>";
    echo "<a href='diagnose-templates.php' class='action-btn'>Refresh This Diagnostic</a>";
    echo "</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section'>";
    echo "<p class='error'>❌ Fatal Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "</div></body></html>";
?>





