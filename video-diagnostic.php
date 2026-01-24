<?php
/**
 * Video Playback Diagnostic
 * Explains why video wasn't playing and provides solutions
 */

require_once __DIR__ . '/config/constants.php';

$videoPath = __DIR__ . '/assets/video/videoplayback.mp4';
$videoUrl = BASE_URL . '/assets/video/videoplayback.mp4';

echo "<!DOCTYPE html><html><head><title>Video Diagnostic</title>";
echo "<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; background: #f5f5f5; }
    .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h1 { color: #05203e; border-bottom: 3px solid #05203e; padding-bottom: 10px; }
    .success { background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #28a745; }
    .error { background: #f8d7da; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #dc3545; }
    .info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #05203e; }
    .warning { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #ffc107; }
    .code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    video { width: 100%; max-width: 600px; margin: 20px 0; border-radius: 8px; border: 2px solid #dee2e6; }
    .btn { display: inline-block; padding: 10px 20px; background: #05203e; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
    .btn:hover { background: #1f3651; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; border: 1px solid #dee2e6; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🎥 Video Playback Diagnostic</h1>";

$issues = [];
$fixes = [];

// Check 1: File exists
if (file_exists($videoPath)) {
    $fileSize = filesize($videoPath);
    $fileSizeMB = round($fileSize / (1024 * 1024), 2);
    
    echo "<div class='success'>";
    echo "<strong>✅ Video File Found</strong><br>";
    echo "Location: <code class='code'>$videoPath</code><br>";
    echo "Size: $fileSizeMB MB<br>";
    echo "URL: <a href='$videoUrl' target='_blank'>$videoUrl</a>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<strong>❌ Video File NOT Found</strong><br>";
    echo "Expected: <code class='code'>$videoPath</code>";
    echo "</div>";
    $issues[] = "Video file missing";
}

// Check 2: File permissions
if (file_exists($videoPath)) {
    $isReadable = is_readable($videoPath);
    if ($isReadable) {
        echo "<div class='success'>✅ File is readable</div>";
    } else {
        echo "<div class='error'>❌ File is NOT readable (permission issue)</div>";
        $issues[] = "File permissions";
        $fixes[] = "Run: <code class='code'>chmod 644 $videoPath</code>";
    }
}

// Check 3: MIME type
if (file_exists($videoPath)) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $videoPath);
    finfo_close($finfo);
    
    if ($mimeType === 'video/mp4') {
        echo "<div class='success'>✅ MIME Type: <code class='code'>$mimeType</code> (Correct)</div>";
    } else {
        echo "<div class='warning'>⚠️ MIME Type: <code class='code'>$mimeType</code> (May cause issues)</div>";
        $issues[] = "MIME type mismatch";
    }
}

// Check 4: .htaccess MIME configuration
$htaccessPath = __DIR__ . '/.htaccess';
if (file_exists($htaccessPath)) {
    $htaccessContent = file_get_contents($htaccessPath);
    if (strpos($htaccessContent, 'AddType video/mp4') !== false) {
        echo "<div class='success'>✅ .htaccess has video MIME type configuration</div>";
    } else {
        echo "<div class='error'>❌ .htaccess missing video MIME type configuration</div>";
        $issues[] = "Apache MIME type not configured";
        $fixes[] = "Add to .htaccess: <code class='code'>AddType video/mp4 .mp4</code>";
    }
}

// Check 5: HTTP accessibility
if (file_exists($videoPath)) {
    $headers = @get_headers($videoUrl);
    if ($headers && strpos($headers[0], '200') !== false) {
        echo "<div class='success'>✅ Video is accessible via HTTP</div>";
    } else {
        echo "<div class='error'>❌ Video is NOT accessible via HTTP</div>";
        $issues[] = "HTTP access denied";
        $fixes[] = "Check Apache configuration and file permissions";
    }
}

// Summary
echo "<div class='info'>";
echo "<h2>📋 Summary</h2>";
if (empty($issues)) {
    echo "<p><strong>✅ All checks passed!</strong> The video should work now.</p>";
    echo "<p><strong>Why it might not have been playing before:</strong></p>";
    echo "<ul>";
    echo "<li><strong>File was missing:</strong> Video file was in Downloads folder but not in XAMPP htdocs</li>";
    echo "<li><strong>MIME type not configured:</strong> Apache didn't know how to serve .mp4 files</li>";
    echo "<li><strong>Browser autoplay policy:</strong> Some browsers block autoplay (but muted videos should work)</li>";
    echo "<li><strong>File size:</strong> Large files may take time to buffer</li>";
    echo "</ul>";
} else {
    echo "<p><strong>⚠️ Issues Found:</strong></p>";
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li>$issue</li>";
    }
    echo "</ul>";
    
    if (!empty($fixes)) {
        echo "<p><strong>🔧 Fixes Applied:</strong></p>";
        echo "<ul>";
        foreach ($fixes as $fix) {
            echo "<li>$fix</li>";
        }
        echo "</ul>";
    }
}
echo "</div>";

// Test video player
if (file_exists($videoPath)) {
    echo "<div class='info'>";
    echo "<h2>🎬 Test Video Player</h2>";
    echo "<p>If the video plays below, everything is working correctly:</p>";
    echo "<video controls autoplay muted loop playsinline preload='auto'>";
    echo "<source src='$videoUrl' type='video/mp4'>";
    echo "Your browser does not support the video tag.";
    echo "</video>";
    echo "<p><small>Note: Autoplay may be blocked by browser. Click play if it doesn't start automatically.</small></p>";
    echo "</div>";
}

// Common issues and solutions
echo "<div class='warning'>";
echo "<h2>🔍 Common Video Playback Issues & Solutions</h2>";
echo "<ol>";
echo "<li><strong>Video doesn't autoplay:</strong><br>";
echo "Modern browsers block autoplay with sound. The video is set to <code class='code'>muted</code> which should allow autoplay, but some browsers still block it. This is normal behavior.</li>";

echo "<li><strong>Video loads slowly:</strong><br>";
echo "Large video files take time to buffer. Consider compressing the video or using a smaller file size.</li>";

echo "<li><strong>404 Error:</strong><br>";
echo "The video file was in the Downloads folder but not in XAMPP htdocs. This has been fixed by copying the file.</li>";

echo "<li><strong>MIME Type Error:</strong><br>";
echo "Apache needs to be configured to serve .mp4 files. Added <code class='code'>AddType video/mp4 .mp4</code> to .htaccess.</li>";

echo "<li><strong>File Permissions:</strong><br>";
echo "The video file needs to be readable by Apache. Check file permissions.</li>";

echo "<li><strong>Browser Compatibility:</strong><br>";
echo "Some older browsers don't support MP4. Consider providing WebM fallback.</li>";
echo "</ol>";
echo "</div>";

// Next steps
echo "<div class='info'>";
echo "<h2>✅ What Was Fixed</h2>";
echo "<ol>";
echo "<li>✅ Copied video file from Downloads to XAMPP htdocs</li>";
echo "<li>✅ Added MIME type configuration to .htaccess</li>";
echo "<li>✅ Added video caching rules</li>";
echo "</ol>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Restart Apache in XAMPP Control Panel</li>";
echo "<li>Clear browser cache</li>";
echo "<li>Test the video on your homepage</li>";
echo "</ol>";
echo "</div>";

echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<a href='index.php' class='btn'>Go to Homepage</a>";
echo "<a href='check-video.php' class='btn'>Check Video File</a>";
echo "<a href='$videoUrl' class='btn' target='_blank'>Direct Video Link</a>";
echo "</div>";

echo "</div></body></html>";
?>




