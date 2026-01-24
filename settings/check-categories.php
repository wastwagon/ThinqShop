<?php
/**
 * Debug script to check categories in database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "<h2>All Categories in Database</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Name</th><th>Slug</th><th>Parent ID</th><th>Parent Name</th><th>Active</th><th>Created</th></tr>";

$stmt = $conn->query("
    SELECT c.*, 
           (SELECT name FROM categories WHERE id = c.parent_id) as parent_name
    FROM categories c
    ORDER BY c.id DESC
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['id']) . "</td>";
    echo "<td><strong>" . htmlspecialchars($row['name']) . "</strong></td>";
    echo "<td>" . htmlspecialchars($row['slug']) . "</td>";
    echo "<td>" . ($row['parent_id'] ? htmlspecialchars($row['parent_id']) : 'NULL') . "</td>";
    echo "<td>" . ($row['parent_name'] ? htmlspecialchars($row['parent_name']) : '—') . "</td>";
    echo "<td>" . ($row['is_active'] ? 'Yes' : 'No') . "</td>";
    echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>Search for 'Game' or 'Console':</h3>";
$stmt = $conn->prepare("SELECT * FROM categories WHERE name LIKE ? OR slug LIKE ?");
$searchTerm = '%game%';
$stmt->execute([$searchTerm, $searchTerm]);
$results = $stmt->fetchAll();

if (empty($results)) {
    echo "<p>No categories found matching 'game' or 'console'</p>";
} else {
    echo "<ul>";
    foreach ($results as $cat) {
        echo "<li>ID: {$cat['id']}, Name: {$cat['name']}, Parent ID: " . ($cat['parent_id'] ?? 'NULL') . "</li>";
    }
    echo "</ul>";
}



