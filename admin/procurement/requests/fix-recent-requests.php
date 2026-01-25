<?php
/**
 * Fix Recent Procurement Requests - Link Files
 * This script updates requests #12 and #13 with their uploaded files
 */

require_once __DIR__ . '/../../../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$uploadPath = '/Applications/XAMPP/xamppfiles/htdocs/ThinQShopping/assets/images/uploads/';

$conn->beginTransaction();

try {
    // Request #12 - Product Branding (4 files uploaded at 21:15:06)
    $request12Files = [
        'profile_691255da67b146.77721482.png',
        'profile_691255da67da76.24088413.png',
        'profile_691255da67eb78.16129300.webp',
        'profile_691255da67faf3.57623254.webp'
    ];
    
    // Verify files exist
    $validFiles12 = [];
    foreach ($request12Files as $file) {
        $filePath = $uploadPath . $file;
        if (file_exists($filePath)) {
            $validFiles12[] = $file;
        } else {
            echo "Warning: File not found: {$file}\n";
        }
    }
    
    if (!empty($validFiles12)) {
        $files12Json = json_encode($validFiles12);
        $stmt = $conn->prepare('UPDATE procurement_requests SET reference_images = ? WHERE id = 12');
        $stmt->execute([$files12Json]);
        echo "✓ Request #12 updated with " . count($validFiles12) . " files\n";
    } else {
        echo "✗ Request #12: No valid files found\n";
    }
    
    // Request #13 - Products Purchase (1 file uploaded at 21:19:42)
    $request13Files = [
        'profile_691256eeab8611.63175538.webp'
    ];
    
    // Verify file exists
    $validFiles13 = [];
    foreach ($request13Files as $file) {
        $filePath = $uploadPath . $file;
        if (file_exists($filePath)) {
            $validFiles13[] = $file;
        } else {
            echo "Warning: File not found: {$file}\n";
        }
    }
    
    if (!empty($validFiles13)) {
        $files13Json = json_encode($validFiles13);
        $stmt = $conn->prepare('UPDATE procurement_requests SET reference_images = ? WHERE id = 13');
        $stmt->execute([$files13Json]);
        echo "✓ Request #13 updated with " . count($validFiles13) . " files\n";
    } else {
        echo "✗ Request #13: No valid files found\n";
    }
    
    $conn->commit();
    echo "\n✓ Transaction committed successfully!\n\n";
    
    // Verification
    echo "Verification:\n";
    echo str_repeat("=", 60) . "\n";
    $stmt = $conn->query('SELECT id, request_number, reference_images FROM procurement_requests WHERE id IN (12, 13) ORDER BY id');
    $requests = $stmt->fetchAll();
    
    foreach ($requests as $req) {
        echo "Request #{$req['id']} - {$req['request_number']}\n";
        if (!empty($req['reference_images'])) {
            $files = json_decode($req['reference_images'], true);
            echo "  Files (" . count($files) . "):\n";
            foreach ($files as $file) {
                $filePath = $uploadPath . $file;
                $exists = file_exists($filePath) ? '✓ EXISTS' : '✗ MISSING';
                echo "    - {$file} ({$exists})\n";
            }
        } else {
            echo "  Files: NULL\n";
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    $conn->rollBack();
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}


