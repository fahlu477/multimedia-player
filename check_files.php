<?php
echo "<h2>File Structure Check</h2>";

$baseDir = dirname(__FILE__);
echo "<p><strong>Base Directory:</strong> $baseDir</p>";

// Check uploads directory
$uploadsDir = $baseDir . '/uploads/videos/';
echo "<p><strong>Uploads Directory:</strong> $uploadsDir</p>";
echo "<p><strong>Uploads Directory Exists:</strong> " . (is_dir($uploadsDir) ? 'YES' : 'NO') . "</p>";

if (is_dir($uploadsDir)) {
    echo "<h3>Files in uploads/videos/:</h3>";
    $files = scandir($uploadsDir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $filePath = $uploadsDir . $file;
            $fileSize = filesize($filePath);
            echo "<p>📁 $file (Size: " . number_format($fileSize) . " bytes)</p>";
        }
    }
}

// Check if video is accessible via web
echo "<h3>Web Accessibility Test</h3>";
$testVideoPath = '/multimedia-player/uploads/videos/upload knitting.mp4';
$fullUrl = 'http://' . $_SERVER['HTTP_HOST'] . $testVideoPath;

echo "<p><strong>Test URL:</strong> <a href='$fullUrl' target='_blank'>$fullUrl</a></p>";

// Check with cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $fullUrl);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Response Code:</strong> $httpCode</p>";
echo "<p><strong>Status:</strong> " . ($httpCode == 200 ? '✅ Accessible' : '❌ Not Accessible') . "</p>";

// Check .htaccess
$htaccessPath = $baseDir . '/.htaccess';
echo "<h3>.htaccess Check</h3>";
echo "<p><strong>.htaccess exists:</strong> " . (file_exists($htaccessPath) ? 'YES' : 'NO') . "</p>";

if (file_exists($htaccessPath)) {
    echo "<p><strong>.htaccess content:</strong></p>";
    echo "<pre>" . htmlspecialchars(file_get_contents($htaccessPath)) . "</pre>";
}

// Suggest .htaccess content if not exists
if (!file_exists($htaccessPath)) {
    echo "<h4>Suggested .htaccess content:</h4>";
    echo "<pre>";
    echo "# Allow video files\n";
    echo "AddType video/mp4 .mp4\n";
    echo "AddType video/webm .webm\n";
    echo "AddType video/ogg .ogg\n";
    echo "\n";
    echo "# Enable CORS for video files\n";
    echo "&lt;FilesMatch \"\.(mp4|webm|ogg)$\"&gt;\n";
    echo "    Header set Access-Control-Allow-Origin \"*\"\n";
    echo "&lt;/FilesMatch&gt;\n";
    echo "</pre>";
}
?>
