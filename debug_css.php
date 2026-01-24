<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Info</h1>";
echo "Current Script: " . __FILE__ . "<br>";
echo "Current Dir: " . __DIR__ . "<br>";
echo "Server Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";

$cssPath = __DIR__ . '/assets/css/user-dashboard.css';
echo "Checking CSS path: $cssPath<br>";

if (file_exists($cssPath)) {
    echo "CSS File EXISTS. Size: " . filesize($cssPath) . " bytes.<br>";
    echo "Permissions: " . substr(sprintf('%o', fileperms($cssPath)), -4) . "<br>";
    echo "<a href='/assets/css/user-dashboard.css'>Link to CSS</a>";
} else {
    echo "CSS File NOT FOUND.<br>";
}

echo "<h2>Directory Listing of assets/css:</h2>";
$files = scandir(__DIR__ . '/assets/css');
echo "<pre>";
print_r($files);
echo "</pre>";

?>
