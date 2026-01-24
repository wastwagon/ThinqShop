<?php
/**
 * Video File Checker
 * Check if video file exists and is accessible
 */

$videoPath = __DIR__ . '/assets/video/videoplayback.mp4';
$videoUrl = BASE_URL . '/assets/video/videoplayback.mp4';

echo "<!DOCTYPE html><html><head><title>Video File Check</title>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h1 { color: #05203e; border-bottom: 3px solid #05203e; padding-bottom: 10px; }
    .success { background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #28a745; }
    .error { background: #f8d7da; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #dc3545; }
    .info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #05203e; }
    .code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    video { width: 100%; max-width: 600px; margin: 20px 0; border-radius: 8px; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🎥 Video File Check</h1>";

// Check if file exists
if (file_exists($videoPath)) {
    $fileSize = filesize($videoPath);
    $fileSizeMB = round($fileSize / (1024 * 1024), 2);
    $lastModified = date('Y-m-d H:i:s', filemtime($videoPath));
    $isReadable = is_readable($videoPath);
    
    echo "<div class='success'>";
    echo "<strong>✅ Video File Found!</strong><br>";
    echo "<strong>Path:</strong> <code class='code'>$videoPath</code><br>";
    echo "<strong>Size:</strong> " . number_format($fileSize) . " bytes ($fileSizeMB MB)<br>";
    echo "<strong>Last Modified:</strong> $lastModified<br>";
    echo "<strong>Readable:</strong> " . ($isReadable ? "✅ Yes" : "❌ No") . "<br>";
    echo "</div>";
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $videoPath);
    finfo_close($finfo);
    
    echo "<div class='info'>";
    echo "<strong>MIME Type:</strong> <code class='code'>$mimeType</code><br>";
    echo "<strong>Expected:</strong> <code class='code'>video/mp4</code><br>";
    echo "</div>";
    
    // Test video URL
    echo "<div class='info'>";
    echo "<h3>Video URL Test:</h3>";
    echo "<strong>URL:</strong> <a href='$videoUrl' target='_blank'>$videoUrl</a><br>";
    
    // Check if URL is accessible
    $headers = @get_headers($videoUrl);
    if ($headers && strpos($headers[0], '200') !== false) {
        echo "<strong>Status:</strong> ✅ Accessible via HTTP<br>";
    } else {
        echo "<strong>Status:</strong> ❌ Not accessible via HTTP<br>";
        echo "<strong>Possible Issues:</strong><br>";
        echo "<ul>";
        echo "<li>Apache may not be serving .mp4 files</li>";
        echo "<li>File permissions may be incorrect</li>";
        echo "<li>MIME type not configured in Apache</li>";
        echo "</ul>";
    }
    echo "</div>";
    
    // Try to play video
    echo "<div class='info'>";
    echo "<h3>Video Player Test:</h3>";
    echo "<video controls autoplay muted loop playsinline>";
    echo "<source src='$videoUrl' type='video/mp4'>";
    echo "Your browser does not support the video tag.";
    echo "</video>";
    echo "<p><small>If video doesn't play, check browser console for errors.</small></p>";
    echo "</div>";
    
    // Common issues
    echo "<div class='info'>";
    echo "<h3>🔍 Common Video Playback Issues:</h3>";
    echo "<ol>";
    echo "<li><strong>MIME Type:</strong> Apache needs to serve .mp4 files with correct MIME type</li>";
    echo "<li><strong>File Size:</strong> Large files may take time to load</li>";
    echo "<li><strong>Browser Support:</strong> Some browsers require specific codecs</li>";
    echo "<li><strong>Autoplay Policy:</strong> Browsers block autoplay with sound (muted works)</li>";
    echo "<li><strong>File Corruption:</strong> Video file may be corrupted</li>";
    echo "</ol>";
    echo "</div>";
    
} else {
    echo "<div class='error'>";
    echo "<strong>❌ Video File NOT Found!</strong><br>";
    echo "<strong>Expected Path:</strong> <code class='code'>$videoPath</code><br>";
    echo "<strong>URL:</strong> <code class='code'>$videoUrl</code><br>";
    echo "</div>";
    
    // Check if directory exists
    $videoDir = dirname($videoPath);
    if (is_dir($videoDir)) {
        echo "<div class='info'>";
        echo "<strong>✅ Video directory exists:</strong> <code class='code'>$videoDir</code><br>";
        echo "<strong>Files in directory:</strong><br>";
        $files = scandir($videoDir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                echo "- $file<br>";
            }
        }
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<strong>❌ Video directory does not exist:</strong> <code class='code'>$videoDir</code><br>";
        echo "</div>";
    }
}

// Check Apache MIME configuration
echo "<div class='info'>";
echo "<h3>📋 Apache MIME Type Configuration:</h3>";
echo "<p>Apache needs to be configured to serve .mp4 files. Check your <code class='code'>httpd.conf</code> or <code class='code'>.htaccess</code> file:</p>";
echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto;'>";
echo "AddType video/mp4 .mp4\n";
echo "AddType video/webm .webm\n";
echo "AddType video/ogg .ogv\n";
echo "</pre>";
echo "</div>";

echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<a href='index.php' style='display: inline-block; padding: 10px 20px; background: #05203e; color: white; text-decoration: none; border-radius: 5px;'>Go to Homepage</a>";
echo "</div>";

echo "</div></body></html>";
?>




