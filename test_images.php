<?php
require_once 'config.php';
require_once 'database/supabase.php';

$supabase = new SupabaseClient();

echo "<!DOCTYPE html><html><head><title>Image Test</title></head><body>";
echo "<h1>Image Path Test</h1>";

// Test config - SHOW THIS FIRST
echo "<h2>Configuration (MOST IMPORTANT)</h2>";
echo "<p><strong>HTTP_HOST:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET') . "</p>";
echo "<p><strong>ENVIRONMENT:</strong> " . ENVIRONMENT . "</p>";
echo "<p><strong>BASE_URL:</strong> " . BASE_URL . "</p>";
echo "<p style='color: red;'><strong>Expected BASE_URL:</strong> https://mineteh.infinityfreeapp.com (NO /MineTeh at the end!)</p>";

if (strpos(BASE_URL, '/MineTeh') !== false) {
    echo "<p style='background: #ffcccc; padding: 20px; border: 2px solid red;'>";
    echo "<strong>ERROR:</strong> BASE_URL still contains '/MineTeh'! The config.php file was not uploaded correctly or is cached.";
    echo "</p>";
}

// Check if uploads folder exists
echo "<h2>Uploads Folder Check</h2>";
$uploads_path = __DIR__ . '/uploads';
echo "<p><strong>Uploads path:</strong> $uploads_path</p>";
echo "<p><strong>Folder exists:</strong> " . (is_dir($uploads_path) ? 'YES' : 'NO') . "</p>";

if (is_dir($uploads_path)) {
    $files = scandir($uploads_path);
    $image_files = array_filter($files, function($f) use ($uploads_path) {
        return is_file($uploads_path . '/' . $f) && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $f);
    });
    echo "<p><strong>Image files found:</strong> " . count($image_files) . "</p>";
    if (!empty($image_files)) {
        echo "<ul>";
        foreach ($image_files as $file) {
            echo "<li>$file</li>";
        }
        echo "</ul>";
    }
}

// Get images from database
echo "<h2>Database Images</h2>";
$images = $supabase->select('listing_images', '*');

if ($images && !empty($images)) {
    echo "<p><strong>Total images in database:</strong> " . count($images) . "</p>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>Image ID</th><th>Listing ID</th><th>Stored Path</th><th>getImageUrl() Result</th><th>Preview</th></tr>";
    
    foreach ($images as $image) {
        $stored_path = $image['image_path'];
        $absolute_url = getImageUrl($stored_path);
        
        echo "<tr>";
        echo "<td>" . $image['image_id'] . "</td>";
        echo "<td>" . $image['listing_id'] . "</td>";
        echo "<td>" . htmlspecialchars($stored_path) . "</td>";
        echo "<td>" . htmlspecialchars($absolute_url) . "</td>";
        echo "<td><img src='" . htmlspecialchars($absolute_url) . "' style='max-width: 150px; max-height: 150px;' onerror=\"this.alt='FAILED'; this.style.border='2px solid red';\"></td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>No images found in database</p>";
}

// Test getImageUrl function
echo "<h2>getImageUrl() Function Test</h2>";
$test_paths = [
    '../uploads/test.jpg',
    'uploads/test.jpg',
    '/uploads/test.jpg',
    'test.jpg',
    ''
];

echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>Input Path</th><th>Output URL</th></tr>";
foreach ($test_paths as $path) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($path) . "</td>";
    echo "<td>" . htmlspecialchars(getImageUrl($path)) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "</body></html>";
?>
