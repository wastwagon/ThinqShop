<?php require 'config/database.php'; $db = new Database(); $conn = $db->getConnection(); $stmt = $conn->query('SELECT name, slug FROM categories'); while($row = $stmt->fetch()) { echo $row['name'] . ' -> ' . $row['slug'] . "
"; }
