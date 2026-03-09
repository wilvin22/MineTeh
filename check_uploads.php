<?php
echo "<!DOCTYPE html><html><head><title>Check Uploads Folder</title></head><body>";
echo "<h1>Uploads Folder Check</h1>";

$uploads_dir = __DIR__ . '/uploads';

echo "<p><strong>Uploads directory path:</strong> $uploads_dir</p>";
echo "<p><strong>Directory exists:</strong> " . (is_dir($uploads_dir) ? 'YES' : 'NO') . "</p>";

if (is_dir($uploads_dir)) {
    echo "<p><strong>Directory is readable:</strong> " . (is_readable($uploads_dir) ? 'YES' : 'NO') . "</p>";
    
    $files = scandir($uploads_dir);
    $image_files = array_filter($files, function($f) use ($uploads_dir) {
        return is_file($uploads_dir . '/' . $f) && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $f);
    });
    
    echo "<p><strong>Total image files:</strong> " . count($image_files) . "</p>";
    
    if (!empty($image_files)) {
        echo "<h2>Image Files Found:</h2>";
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>Filename</th><th>Size</th><th>Direct Link</th><th>Preview</th></tr>";
        
        foreach ($image_files as $file) {
            $filepath = $uploads_dir . '/' . $file;
            $filesize = filesize($filepath);
            $url = 'https://' . $_SERVER['HTTP_HOST'] . '/uploads/' . $file;
            
            echo "<tr>";
            echo "<td>$file</td>";
            echo "<td>" . number_format($filesize) . " bytes</td>";
            echo "<td><a href='$url' target='_blank'>Open</a></td>";
            echo "<td><img src='$url' style='max-width: 100px; max-height: 100px;' onerror=\"this.alt='FAILED';\"></td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p style='color: red;'><strong>NO IMAGE FILES FOUND!</strong></p>";
        echo "<p>The uploads folder exists but is empty. You need to upload your image files to this folder.</p>";
    }
} else {
    echo "<p style='color: red;'><strong>UPLOADS FOLDER DOES NOT EXIST!</strong></p>";
    echo "<p>You need to create the 'uploads' folder and upload your images to it.</p>";
}

echo "</body></html>";
?>
