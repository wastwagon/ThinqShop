<?php
/**
 * Quick Check: Are templates in the database?
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html><html><head><title>Check Templates</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;}";
echo "table{border-collapse:collapse;width:100%;background:white;margin:20px 0;}";
echo "th,td{padding:10px;border:1px solid #ddd;text-align:left;}";
echo "th{background:#f8f9fa;}";
echo "</style></head><body>";

echo "<h1>Email Templates Database Check</h1>";

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check if table exists
    echo "<h2>1. Table Check</h2>";
    try {
        $stmt = $conn->query("SHOW TABLES LIKE 'email_templates'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✅ email_templates table exists</p>";
        } else {
            echo "<p class='error'>❌ email_templates table does NOT exist</p>";
            echo "<p>Run the migration: <a href='run-migration.php'>run-migration.php</a></p>";
            echo "</body></html>";
            exit;
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error checking table: " . $e->getMessage() . "</p>";
        echo "</body></html>";
        exit;
    }
    
    // Count templates
    echo "<h2>2. Template Count</h2>";
    $stmt = $conn->query("SELECT COUNT(*) as count FROM email_templates");
    $count = $stmt->fetch()['count'];
    echo "<p>Total templates: <strong>$count</strong></p>";
    
    if ($count == 0) {
        echo "<p class='error'>❌ No templates found in database!</p>";
        echo "<p><a href='insert-default-templates.php' style='background:#0d6efd;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;'>Insert Default Templates</a></p>";
    } else {
        echo "<p class='success'>✅ Templates found!</p>";
    }
    
    // List all templates
    echo "<h2>3. All Templates</h2>";
    $stmt = $conn->query("SELECT id, template_key, template_name, is_active FROM email_templates ORDER BY template_name ASC");
    $templates = $stmt->fetchAll();
    
    if (empty($templates)) {
        echo "<p class='error'>❌ Query returned no results</p>";
    } else {
        echo "<table>";
        echo "<tr><th>ID</th><th>Template Key</th><th>Template Name</th><th>Active</th></tr>";
        foreach ($templates as $template) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($template['id']) . "</td>";
            echo "<td>" . htmlspecialchars($template['template_key']) . "</td>";
            echo "<td>" . htmlspecialchars($template['template_name']) . "</td>";
            echo "<td>" . ($template['is_active'] ? '<span class="success">Yes</span>' : '<span class="error">No</span>') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test the exact query used by the page
    echo "<h2>4. Test Page Query</h2>";
    try {
        $stmt = $conn->query("SELECT * FROM email_templates ORDER BY template_name ASC");
        $testTemplates = $stmt->fetchAll();
        echo "<p>Query executed successfully. Found: <strong>" . count($testTemplates) . "</strong> templates</p>";
        
        if (count($testTemplates) > 0) {
            echo "<p class='success'>✅ The page should be able to load templates!</p>";
            echo "<p><a href='admin/settings/email-templates.php'>Go to Email Templates Page</a></p>";
        } else {
            echo "<p class='error'>❌ Query returns empty array - templates need to be inserted</p>";
            echo "<p><a href='insert-default-templates.php'>Insert Default Templates</a></p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Query failed: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Fatal Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
?>





