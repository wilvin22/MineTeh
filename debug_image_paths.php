<?php
/**
 * Debug script to check and fix image paths
 */
require_once 'database/supabase.php';

$supabase = new SupabaseClient();

// Get all images from database
$images = $supabase->select('listing_images', '*');

echo "<!DOCTYPE html><html><head><title>Image Path Debug</title></head><body>";
echo "<h1>Image Paths in Database</h1>";

if ($images && !empty($images)) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Image ID</th><th>Listing ID</th><th>Stored Path</th><th>File Exists?</th><th>Corrected Path</th></tr>";
    
    foreach ($images as $image) {
        $stored_path = $image['image_path'];
        $image_id = $image['image_id'];
        $listing_id = $image['listing_id'];
        
        // Check if file exists with stored path
        $file_exists = file_exists($stored_path) ? 'YES' : 'NO';
        
        // Try to determine correct path
        $filename = basename($stored_path);
        $correct_path = "uploads/" . $filename;
        $correct_exists = file_exists($correct_path) ? 'YES' : 'NO';
        
        echo "<tr>";
        echo "<td>$image_id</td>";
        echo "<td>$listing_id</td>";
        echo "<td>$stored_path</td>";
        echo "<td>$file_exists</td>";
        echo "<td>$correct_path (exists: $correct_exists)</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h2>Image Preview Test</h2>";
    foreach ($images as $image) {
        $stored_path = $image['image_path'];
        $filename = basename($stored_path);
        
        echo "<div style='margin: 20px; border: 1px solid #ccc; padding: 10px;'>";
        echo "<p><strong>Stored path:</strong> $stored_path</p>";
        echo "<p><strong>Trying to display:</strong></p>";
        echo "<img src='$stored_path' style='max-width: 200px; border: 1px solid red;' onerror=\"this.style.border='3px solid red'; this.alt='FAILED: $stored_path'\">";
        echo "<p><strong>Alternative path:</strong> uploads/$filename</p>";
        echo "<img src='uploads/$filename' style='max-width: 200px; border: 1px solid green;' onerror=\"this.style.border='3px solid red'; this.alt='FAILED: uploads/$filename'\">";
        echo "</div>";
    }
} else {
    echo "<p>No images found in database</p>";
}

echo "</body></html>";
?>
